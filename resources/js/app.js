import Vue from 'vue'
require('./bootstrap')

import router from './router'
import App from './components/App'
import Login from './components/auth/Login'
import Register from './components/auth/Register'
import ForgotPassword from './components/auth/ForgotPassword'
import ResetPassword from './components/auth/ResetPassword'

//Global Libraries
import Vuelidate from 'vuelidate'
Vue.use(Vuelidate)
import '@fortawesome/fontawesome-free/css/all.css';
import '@fortawesome/fontawesome-free/js/all.js';

const app = new Vue({
    el: '#app',
    router,
    components:{
        App,
        Login,
        Register,
        ForgotPassword,
        ResetPassword
    }
});
