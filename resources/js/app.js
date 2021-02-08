import Vue from 'vue'
require('./bootstrap')

// NOTE: Remove this line when majority of issues are fixed
Vue.config.devtools = true

import router from './router'
import navigationGuards from './navigationGuards'
import store from './store'
import App from './components/App'

//Global Libraries
import '@fortawesome/fontawesome-free/css/all.css';
import '@fortawesome/fontawesome-free/js/all.js';
import '@mdi/font/css/materialdesignicons.min.css';

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
    Vue.use(VueNativeSock, `${process.env.MIX_WEBSOCKET_URL}?token=${token}`, { connectManually: true })
}

import Vuetify, { VApp, VBtn, VData, VDataFooter, VDataTable, VDatePicker, VIcon, VMain, VMenu, VPagination, VSelect, VSimpleTable, VTextField } from "vuetify/lib";
Vue.use(Vuetify, {
  components: { VApp, VBtn, VData, VDataFooter, VDataTable, VDatePicker, VIcon, VMain, VMenu, VPagination, VSelect, VSimpleTable, VTextField }
});
const vuetify = new Vuetify();

const app = new Vue({
    el: '#app',
    router,
    store,
    vuetify,
    components: {
        App,
    }
});
