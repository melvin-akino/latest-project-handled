import Cookies from 'js-cookie'

const state = {
    isAuthenticated: false,
    authUser: '',
    resetPasswordEmail: '',
    isResetPasswordTokenInvalid: false,
    resetPasswordInvalidTokenError: ''
}

const mutations = {
    SET_IS_AUTHENTICATED: (state, data) => {
        state.isAuthenticated = data
    },
    SET_RESET_PASSWORD_EMAIL: (state, data) => {
        state.resetPasswordEmail = data
    },
    SET_IS_RESET_PASSWORD_TOKEN_INVALID: (state, data) => {
        state.isResetPasswordTokenInvalid = data
    },
    SET_RESET_PASSWORD_INVALID_TOKEN_ERROR: (state, data) => {
        state.resetPasswordInvalidTokenError = data
    }
}

const actions = {
    checkIfTokenIsValid: ({commit}, status) => {
        let allowedStatuses = [200, 422]
        if(!allowedStatuses.includes(status)) {
            location.reload('/login')
            Cookies.remove('access_token')
            Cookies.remove('display_name')
            setTimeout(() => {
                commit('auth/SET_IS_AUTHENTICATED', false)
            }, 2000)
        }
    }
}

export default {
    state, mutations, actions, namespaced: true
}
