import Cookies from 'js-cookie'
const token = Cookies.get('access_token')

const state = {
    settingsData: {},
    generalSettingsConfig: {}
}

const mutations = {
    FETCH_ONLOAD_SETTINGS_DATA: (state, data) => {
        state.settingsData = data
    },
    FETCH_GENERAL_SETTINGS_CONFIG: (state, config) => {
        state.generalSettingsConfig = config
    }

}

const actions = {
    getGeneralSettingsConfig: ({commit}) => {
        return new Promise((resolve, reject) => {
            axios.get('v1/user/settings/general', { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                resolve(response.data.data)
                commit('FETCH_GENERAL_SETTINGS_CONFIG', response.data.data)
            })
            .catch(err => {
                reject(err)
            })
        })
    }
}

export default {
    state, mutations, actions, namespaced: true
}
