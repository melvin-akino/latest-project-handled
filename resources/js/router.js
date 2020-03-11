import Vue from 'vue'
import VueRouter from 'vue-router'
Vue.use(VueRouter)

const routes = [
    {
        path: '/login',
        component: () => import('./components/views/auth/Login')
    },
    {
        path: '/register',
        component:() => import('./components/views/auth/Register')
    },
    {
        path: '/forgot-password',
        component: () => import('./components/views/auth/ForgotPassword')
    },
    {
        path: '/reset-password/:token',
        component: () => import('./components/views/auth/ResetPassword')
    },
    {
        path: '/',
        component: () => import('./components/views/trade')
    },
    {
        path: '/bet-slip/:market_id/:team/:uid/:column',
        component: () => import('./components/views/trade/BetSlip'),
        meta: { popup: true }
    },
    {
        path: '/odds-history/:market_id',
        component: () => import('./components/views/trade/OddsHistory'),
        meta: { popup: true }
    },
    {
        path: '/orders',
        component: () => import('./components/views/orders')
    },
    {
        path: '/settings',
        component: () => import('./components/views/settings'),
        children: [
            {
                path: '',
                redirect: '/settings/general'
            },
            {
                path: 'general',
                component: () => import('./components/views/settings/General')
            },
            {
                path: 'profile',
                component: () => import('./components/views/settings/Profile')
            },
            {
                path: 'trade-page',
                component: () => import('./components/views/settings/TradePage')
            },
            {
                path: 'bet-slip',
                component: () => import('./components/views/settings/BetSlip')
            },
            {
                path: 'bookmakers',
                component: () => import('./components/views/settings/Bookmakers')
            },
            {
                path: 'market-types',
                component: () => import('./components/views/settings/MarketTypes')
            },
            {
                path: 'notifications-and-sounds',
                component: () => import('./components/views/settings/NotificationsAndSounds')
            }
        ]
    },
];

const router = new VueRouter({routes})
export default router
