import Vue from 'vue'
require('./bootstrap')

import router from './router'
import navigationGuards from './navigationGuards'
import store from './store'
import App from './components/App'

//Global Libraries
import Vuelidate from 'vuelidate'
Vue.use(Vuelidate)
import '@fortawesome/fontawesome-free/css/all.css';
import '@fortawesome/fontawesome-free/js/all.js';

const app = new Vue({
    el: '#app',
    router,
    store,
    components:{
        App
    }
});
