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

import VueNativeSock from 'vue-native-websocket'
Vue.use(VueNativeSock, 'ws://localhost/ws', { reconnection: true })

const app = new Vue({
    el: '#app',
    router,
    store,
    components: {
        App
    }
});
