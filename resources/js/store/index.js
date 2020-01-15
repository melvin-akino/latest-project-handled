import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex)

const store = new Vuex.Store({
    state: {
        isAuthenticated: false
    },
    mutations: {
        SET_IS_AUTHENTICATED: (state, data) => {
            state.isAuthenticated = data
        }
    }
})

export default store
