import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex)

import auth from './auth'
import settings from './settings'
import trade from './trade'
import orders from './orders'

const store = new Vuex.Store({
    modules: {
        auth,
        settings,
        trade,
        orders
    }
})

export default store
