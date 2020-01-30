import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex)

import auth from './auth'
import settings from './settings'

const store = new Vuex.Store({
    modules: {
        auth,
        settings
    }
})

export default store
