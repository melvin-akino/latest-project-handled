import router from './router'
import Cookies from 'js-cookie'
import axios from 'axios'
import store from './store'
import bus from './eventBus'

export default router.beforeEach((to, from, next) => {
    const authRoutes = ['/login', '/forgot-password', '/reset-password/:token']
    const token = Cookies.get('mltoken')
    if (token) {
        store.commit('auth/SET_IS_AUTHENTICATED', true)
        if (authRoutes.includes(to.matched[0].path)) {
            store.commit('trade/SHOW_SEARCH', true)
            next('/')
        } else {
            if(to.path == '/') {
                store.commit('trade/SHOW_SEARCH', true)
            } else {
                store.commit('trade/SHOW_SEARCH', false)
            }
            next()
        }
    } else {
        if (authRoutes.includes(to.matched[0].path)) {
            store.commit('auth/SET_AUTH_LAYOUT', true)
            if (to.matched[0].path === '/reset-password/:token') {
                axios.get(`/v1/auth/password/find/${to.params.token}`)
                .then(response => {
                    store.commit('auth/SET_RESET_PASSWORD_EMAIL', response.data.message.email)
                    next()
                })
                .catch(err => {
                    store.commit('auth/SET_IS_RESET_PASSWORD_TOKEN_INVALID', true)
                    store.commit('auth/SET_RESET_PASSWORD_INVALID_TOKEN_ERROR', err.response.data.message)
                    next('/login')
                })
            } else {
                next()
            }
        } else {
            if(to.matched[0].path === '/bet-matrix') {
                store.commit('auth/SET_AUTH_LAYOUT', false)
                next()
            } else {
                store.commit('auth/SET_AUTH_LAYOUT', true)
                next('/login')
            }
        }
    }

    bus.$emit('CLEAR_SNACKBARS')
})
