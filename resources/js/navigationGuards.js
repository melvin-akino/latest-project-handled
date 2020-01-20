import router from './router'
import Cookies from 'js-cookie'
import axios from 'axios'
import store from './store'

export default router.beforeEach((to, from, next) => {
    const authRoutes = ['/login', '/register', '/forgot-password', '/reset-password/:token/:email']
    const token = Cookies.get('access_token')
    if(token) {
        axios.get('/v1/auth/user', {headers:{'Authorization': `Bearer ${token}`}})
        .then(response => {
            store.commit('SET_IS_AUTHENTICATED', true)
            store.commit('SET_AUTH_USER', response.data)
            if(authRoutes.includes(to.matched[0].path)) {
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
        if(authRoutes.includes(to.matched[0].path)) {
            next()
        } else {
            next('/login')
        }
    }
})
