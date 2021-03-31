import Cookies from 'js-cookie'

const state = {
    authLayout: null,
    isAuthenticated: false,
    authUser: '',
    resetPasswordEmail: '',
    isResetPasswordTokenInvalid: false,
    resetPasswordInvalidTokenError: ''
}

const mutations = {
    SET_AUTH_LAYOUT: (state, data) => {
        state.authLayout = data
    },
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
        let allowedErrorStatuses = [400, 404, 422, 500, 504]
        if(!allowedErrorStatuses.includes(status)) {
            location.reload('/login')
            Cookies.remove('mltoken')
            Cookies.remove('display_name')
            setTimeout(() => {
                commit('SET_IS_AUTHENTICATED', false)
            }, 2000)
        }
    },
    logout({commit, dispatch}, {new_access}) {
        let token = Cookies.get('mltoken')

        return axios.post('/v1/auth/logout', null, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                location.reload('/login')

                Cookies.remove('mltoken')
                Cookies.remove('display_name')

                if(new_access) {
                    Cookies.set('new_access', true)
                }

                setTimeout(() => {
                    this.$store.commit('auth/SET_IS_AUTHENTICATED', false)
                }, 2000)
            })
            .catch(err => {
                dispatch('checkIfTokenIsValid', err.response.status)
            })
    }
}

export default {
    state, mutations, actions, namespaced: true
}
