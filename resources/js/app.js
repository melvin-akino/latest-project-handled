import Vue from 'vue'
require('./bootstrap')

import router from './router'
import App from './components/App'

//Global Libraries
import Vuelidate from 'vuelidate'
Vue.use(Vuelidate)

const app = new Vue({
    el: '#app',
    router,
    components:{
        App
    }
});
