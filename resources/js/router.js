import Vue from 'vue'
import VueRouter from 'vue-router'
Vue.use(VueRouter)

// Components
import ExampleComponent from './components/ExampleComponent'

const routes = [
    {
        path:'/',
        component:ExampleComponent
    }
];

const router = new VueRouter({routes})
export default router
