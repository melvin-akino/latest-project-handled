import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex)

import axios from 'axios'
import Cookies from 'js-cookie'

import settings from './settings'

const store = new Vuex.Store({
    state: {
        isAuthenticated: false,
        authUser: '',
        userProviders: '',
        userSportsOddTypes: '',
        userConfig: '',
    },
    mutations: {
        SET_IS_AUTHENTICATED: (state, data) => {
            state.isAuthenticated = data
        },
        SET_AUTH_USER: (state, data) => {
            state.authUser = data
        },
        SET_USER_PROVIDERS: (state, data) => {
            state.userProviders = data
        },
        SET_USER_SPORTS_ODD_TYPES: (state, data) => {
            state.userSportsOddTypes = data
        },
        SET_USER_CONFIG: (state, data) => {
            state.userConfig = data
        },
        SET_DEFAULT_LANGUAGE: (state, data) => {
            state.userConfig.language.language = data
        }
    },
    actions: {
        fetchUserDataAfterReset(context) {
            let token = Cookies.get('access_token')

            axios.get('/v1/user', { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                context.commit('SET_USER_CONFIG', response.data.configuration)
            })
            .catch(err => {
                console.log(err)
            })
        }
    },
    modules: {
        settings
    } 
})

export default store
