import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex)

import auth from './auth'
import settings from './settings'
import trade from './trade'

const store = new Vuex.Store({
    modules: {
        auth,
        settings,
        trade
    }
})

export default store
