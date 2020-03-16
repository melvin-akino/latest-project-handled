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
                dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
            })
        })
    },
    async getDefaultTimezone({dispatch, commit}) {
        try {
            let { timezone } = await dispatch('getUserSettingsConfig', 'general')
            let response = await axios.get('/v1/timezones')
            let defaultTimezone =  response.data.data.filter(zone => parseInt(zone.id) === parseInt(timezone))[0]
            return defaultTimezone
        } catch(err) {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
        }
    },
    async getDefaultPriceFormat({dispatch, state}) {
        try {
            let { price_format } = await dispatch('getUserSettingsConfig', 'general')
            let defaultPriceFormat = state.settingsData['price-format'].filter(priceFormat => priceFormat.id == price_format)[0]
            return defaultPriceFormat.alias
        } catch(err) {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
        }
    }
}

export default {
    state, mutations, actions, namespaced: true
}
