import router from './router'
import Cookies from 'js-cookie'
import axios from 'axios'
import store from './store'

export default router.beforeEach((to, from, next) => {
    const authRoutes = ['/login', '/register', '/forgot-password', '/reset-password/:token']
    const token = Cookies.get('access_token')
    if (token) {
        axios.get('/v1/user', { headers: { 'Authorization': `Bearer ${token}` } })
        .then(response => {
            store.commit('SET_IS_AUTHENTICATED', true)
            store.commit('SET_AUTH_USER', response.data.data)

            if (authRoutes.includes(to.matched[0].path)) {
                next('/')
            } else {
                next()
            }
        })
        .catch(err => {
            console.log(err)
            Cookies.remove('access_token')
            store.commit('SET_IS_AUTHENTICATED', false)
            next('/login')
        })
    } else {
        if (authRoutes.includes(to.matched[0].path)) {
            if(to.matched[0].path === '/reset-password/:token') {
                axios.get(`/v1/auth/password/find/${to.params.token}`)
                .then(() => {
                    next()
                })
                .catch(err => {
                    next('/login')
                })
            } else {
                next()
            }
        } else {
            next('/login')
        }
    }
})
