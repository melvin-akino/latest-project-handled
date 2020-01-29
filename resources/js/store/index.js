import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex)

import settings from './settings'

const store = new Vuex.Store({
    state: {
        isAuthenticated: false,
        authUser: '',
    },
    mutations: {
        SET_IS_AUTHENTICATED: (state, data) => {
            state.isAuthenticated = data
        },
        SET_AUTH_USER: (state, data) => {
            state.authUser = data
        }
    },
    modules: {
        settings
    } 
})

export default store
