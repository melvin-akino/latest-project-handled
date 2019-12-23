<template>
    <div class="login">
        <div class="mx-auto bg-white shadow-lg w-160 h-auto px-12 pt-12 pb-4 mt-6">
            <form method="POST" @submit.prevent="login">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="email">
                        <i class="far fa-user"></i> &nbsp; Email
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.loginForm.email.$error}" id="email" type="text" placeholder="Email" v-model="$v.loginForm.email.$model" @keyup="clearLoginError">
                    <span v-if="$v.loginForm.email.$dirty && !$v.loginForm.email.required" class="text-red-600 text-sm">Please type your email.</span>
                    <span v-if="$v.loginForm.email.$dirty && !$v.loginForm.email.email" class="text-red-600 text-sm">Please type a valid email.</span>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="password">
                        <i class="fas fa-key"></i> &nbsp; Password
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.loginForm.password.$error}" id="password" type="password" placeholder="Password" v-model="$v.loginForm.password.$model" @keyup="clearLoginError">
                    <span v-if="$v.loginForm.password.$dirty && !$v.loginForm.password.required" class="text-red-600 text-sm">Please type your password.</span>
                </div>
                <div class="mb-2">
                    <label class="block text-gray-700 text-sm mb-2 font-bold uppercase">
                        <input class="mr-2 leading-tight" type="checkbox">
                        <span class="text-sm uppercase">Remember Me</span>
                    </label>
                </div>
                <div class="mb-4 flex justify-between">
                    <span class="text-sm text-red-600">{{loginError}}</span>
                    <button type="submit" class="bg-orange-400 text-white rounded-full font-bold text-sm uppercase px-12 py-5 hover:bg-orange-500 focus:outline-none">Login</button>
                </div>
            </form>
        </div>
        <div class="mt-6">
            <div class="mb-1 flex justify-center">
                <a href="#" class="inline-block text-gray-700 text-xs font-bold mb-2 uppercase mr-6 hover:underline">Forgot Password</a>
                <a href="#" class="inline-block text-gray-700 text-xs font-bold mb-2 uppercase hover:underline">Contact Us</a>
            </div>
            <div class="mb-1 flex justify-center">
                <small class="text-gray-700 text-xs font-bold mb-2 uppercase">Don't have an account yet? <a :href="registerRoute" class="hover:underline">Register Here</a></small>
            </div>
            <div class="flex justify-center">
                <small class="text-gray-700 text-xs font-bold mb-2 uppercase"><i class="far fa-question-circle"></i> Best viewed on Google Chrome, Firefox or Safari</small>
            </div>
        </div>
    </div>
</template>

<script>
import { required, email } from 'vuelidate/lib/validators'
export default {
    name:'Login',
    data() {
        return {
            loginForm: {
                email:'',
                password:''
            },
            loginError:'',
            registerRoute:`${process.env.MIX_APP_URL}/register`
        }
    },
    created() {
        document.title = 'Login - Multiline'
    },
    validations:{
        loginForm:{
            email:{required, email},
            password:{required}
        }
    },
    methods:{
        login() {
            if(!this.$v.loginForm.$invalid) {
                axios.post('/login', { email:this.loginForm.email, password:this.loginForm.password })
                .then(response => window.location.href = '/')
                .catch(err => {
                    console.log(err)
                    this.loginError = 'Invalid email or password.'
                })
            } else {
                this.$v.loginForm.email.$touch()
                this.$v.loginForm.password.$touch()
            }
        },
        clearLoginError() {
            this.loginError = ''
        }
    }
}
</script>
