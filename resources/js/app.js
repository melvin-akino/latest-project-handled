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

import io from 'socket.io-client'
import VueSocketIO from 'vue-socket.io'
Vue.use(new VueSocketIO({
    connection:io('ws://localhost:5300', {transports: ['websocket'], 'path': '/ws', query: {
            token: "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI1IiwianRpIjoiMmYwZmYyMzNjMDcxMDhhM2I1MjQwZmFhYmRkMzMxODlkZTQ2M2YzMjQ1MTYwY2U5OGQ2MjY1YzdiMTIwYWY4MzM1NTA2OTZlMGY3MjBhOTYiLCJpYXQiOjE1ODE0Nzc0MDMsIm5iZiI6MTU4MTQ3NzQwMywiZXhwIjoxNjEzMDk5ODAzLCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.SxfQQJ5P6xABlVI8qwoZRr6efk25Z9P1BDcT1dcA5tdvHVrIZrFjIXGtZc6JU_ED0OpHYJB1XuLQ104MKlhb8juCSCqVXMu8DfbOeriz5TF37naiwPq4Bl1zT_4A91oKyXw218vmnhl3EpHc4YBXwpGTWo7aNx7PJP6bddhaOUJQs1nbIBbktc-rhDM-xfJBmm1DtWVGlZyWnSHYKE5LI7lkJGG5xgz9bYdaczFyTFnvn8tQwn0wVnpvceVHqhmM6qbCdKNz1xmsAdSG5OvwCIdoF_o-UXljA8ia0-qh9E2f4bmUKBLiHwsfJNlxAOGKdaUtkG_UE8-TGLWNqYGHj7mrhNI3BISqM6YBQCwMJqqTdMbwcA4neXhH-ZgyXSAkPA6ENQ-VC2m1x_-PeKYQfhFy8cMDgvLgWdWIuTOm1RH4ZfS-K0ndBRvqydE-WrBMXLakBNl_2HKLsJhnSZrHukCCKzIr9hMGWE2sO4jj4S73CSfuhmFQpZAkyimsmY7YgOBXK9T7jeVZhXmodEsWNJ6qJzU0O4vrmX5V_7EIhouhbK9zX7v1PD-oEzea4mexNWJ6eAKL_ubliGHPPIjKehn0E-T_gIfIH5ot67COEDe_yoYxXQ9RGcF5hsbc7o4o0CfARDCSRH_JxxXU7odvQ6Qdcc6ZxAiQCMhgORa_ykc",
        }})
}))

const app = new Vue({
    el: '#app',
    router,
    store,
    components: {
        App
    }
});
