import Vue from 'vue'
import VueRouter from 'vue-router'
Vue.use(VueRouter)

const routes = [
    {
        path:'/login',
        component:() => import('./components/views/auth/Login')
    },
    {
        path:'/register',
        component:() => import('./components/views/auth/Register')
    },
    {
        path:'/forgot-password',
        component:() => import('./components/views/auth/ForgotPassword')
    },
    {
        path:'/reset-password/:token/:email',
        component:() => import('./components/views/auth/ResetPassword')
    },
    {
        path:'/',
        component:() => import('./components/views/Trade')
    },
    {
        path:'/settlement',
        component:() => import('./components/views/Settlement')
    },
    {
        path:'/open-orders',
        component:() => import('./components/views/OpenOrders')
    },
    {
        path:'/settings',
        component:() => import('./components/views/Settings')
    },
];

const router = new VueRouter({routes})
export default router
