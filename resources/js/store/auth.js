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
    SET_AUTH_USER: (state, data) => {
        state.authUser = data
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

export default {
    state, mutations, namespaced: true
}
