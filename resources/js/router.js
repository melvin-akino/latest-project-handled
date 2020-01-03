import Vue from 'vue'
import VueRouter from 'vue-router'
Vue.use(VueRouter)

// Components
import Trade from './components/views/Trade'
import Settlement from './components/views/Settlement'
import OpenOrders from './components/views/OpenOrders'
import Settings from './components/views/Settings'

const routes = [
    {
        path:'/',
        component:Trade
    },
    {
        path:'/settlement',
        component:Settlement
    },
    {
        path:'/open-orders',
        component:OpenOrders
    },
    {
        path:'/settings',
        component:Settings
    },
];

const router = new VueRouter({routes})
export default router
