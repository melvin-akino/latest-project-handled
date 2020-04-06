import Vue from 'vue'
require('./bootstrap')

import router from './router'
import navigationGuards from './navigationGuards'
import store from './store'
import App from './components/App'

//Global Libraries
import '@fortawesome/fontawesome-free/css/all.css';
import '@fortawesome/fontawesome-free/js/all.js';

import Vuelidate from 'vuelidate'
Vue.use(Vuelidate)

import VueHead from 'vue-head'
Vue.use(VueHead)

import {ClientTable} from 'vue-tables-2'
Vue.use(ClientTable, {}, false);

import Cookies from 'js-cookie'
const token = Cookies.get('mltoken')

import VueNativeSock from 'vue-native-websocket'
if(Cookies.get('mltoken')) {
    Vue.use(VueNativeSock, `${process.env.MIX_WEBSOCKET_URL}?token=${token}`, { reconnection: true, reconnectionDelay: 500 })
}

const app = new Vue({
    el: '#app',
    router,
    store,
    components: {
        App
    }
});
