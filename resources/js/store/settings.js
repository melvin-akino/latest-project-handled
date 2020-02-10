import Cookies from 'js-cookie'
const token = Cookies.get('access_token')

const state = {
    settingsData: {},
    userSettingsConfig: {},
    defaultTimezone: {}
}

const mutations = {
    FETCH_ONLOAD_SETTINGS_DATA: (state, data) => {
        state.settingsData = data
    },
    FETCH_USER_SETTINGS_CONFIG: (state, configs) => {
        state.userSettingsConfig = configs
    },
    SET_DEFAULT_TIMEZONE: (state, timezone) => {
        state.defaultTimezone = timezone
    }
}

const actions = {
    getUserSettingsConfig: (context, type) => {
        return new Promise((resolve, reject) => {
            axios.get(`/v1/user/settings/${type}`, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                resolve(response.data.data)
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
