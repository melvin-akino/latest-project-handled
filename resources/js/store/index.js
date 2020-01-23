import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex)

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
        }
    }
})

export default store
