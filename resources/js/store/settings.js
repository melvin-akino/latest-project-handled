import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex)

const state = {
    settingsData: {}
}

const mutations = {
    getSettingsData: (state, data) => {
        state.settingsData = data
    }
}

export default {
    state, mutations, namespaced: true
}
