import Cookies from 'js-cookie'
const token = Cookies.get('mltoken')

const state = {
    settingsData: {},
    userSettingsConfig: {},
    defaultTimezone: '',
    defaultPriceFormat: '',
    disabledBetColumns: []
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
    },
    SET_DEFAULT_PRICE_FORMAT: (state, priceFormat) => {
        state.defaultPriceFormat = priceFormat
    },
    FETCH_DISABLED_COLUMNS: (state, columns) => {
        state.disabledBetColumns = columns
    }
}

const actions = {
    getUserSettingsConfig: ({dispatch}, type) => {
        return new Promise((resolve, reject) => {
            axios.get(`/v1/user/settings/${type}`, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                resolve(response.data.data)
            })
            .catch(err => {
                reject(err)
                dispatch('auth/checkIfTokenIsValid', err.response.status, { root: true })
            })
        })
    },
    async getDefaultGeneralSettings({dispatch, commit}) {
        try {
            let { timezone, price_format } = await dispatch('getUserSettingsConfig', 'general')
            let response = await axios.get('/v1/timezones')
            let defaultTimezone =  response.data.data.filter(zone => parseInt(zone.id) === parseInt(timezone))[0]
            commit('SET_DEFAULT_TIMEZONE', defaultTimezone)
            let defaultPriceFormat = state.settingsData['price-format'].filter(priceFormat => priceFormat.id == price_format)[0]
            commit('SET_DEFAULT_PRICE_FORMAT', defaultPriceFormat.alias)
            return { defaultTimezone,  defaultPriceFormat}
        } catch(err) {
            dispatch('auth/checkIfTokenIsValid', err.response.status, { root: true })
        }
    }
}

export default {
    state, mutations, actions, namespaced: true
}
