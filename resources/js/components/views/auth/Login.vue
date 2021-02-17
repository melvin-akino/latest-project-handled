<template>
    <div class="login">
        <div class="mx-auto sm:bg-white sm:shadow-lg md:w-160 sm:w-120 xs:w-100 w-full h-auto sm:px-12 px-4 sm:pt-12 pt-6 pb-4 mt-6">
            <h3 class="block text-gray-700 text-lg mb-2 font-bold uppercase">Login</h3>
            <form method="POST" @submit.prevent="login">
                <div class="mb-4">
                    <label class="absolute bg-white block text-sm ml-3 mb-2 px-3 font-bold uppercase" :class="{ 'text-red-600': $v.loginForm.email.$error, 'text-gray-700': !$v.loginForm.email.$error }" for="email">
                        <i class="far fa-user"></i> &nbsp; Email
                    </label>
                    <input class="shadow appearance-none border border-gray-400 rounded w-full mt-3 py-4 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.loginForm.email.$error}" id="email" type="text" v-model="$v.loginForm.email.$model" @keypress="clearLoginError">
                    <span v-if="$v.loginForm.email.$dirty && !$v.loginForm.email.required" class="text-red-600 text-sm">Please type your email.</span>
                    <span v-if="$v.loginForm.email.$dirty && !$v.loginForm.email.email" class="text-red-600 text-sm">Please type a valid email.</span>
                </div>
                <div class="mb-4">
                    <label class="absolute bg-white block text-sm ml-3 mb-2 px-3 font-bold uppercase" :class="{ 'text-red-600': $v.loginForm.password.$error, 'text-gray-700': !$v.loginForm.password.$error }" for="password">
                        <i class="fas fa-key"></i> &nbsp; Password
                    </label>
                    <input class="shadow appearance-none border border-gray-400 rounded w-full mt-3 py-4 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.loginForm.password.$error}" id="password" type="password" v-model="$v.loginForm.password.$model" @keypress="clearLoginError">
                    <span v-if="$v.loginForm.password.$dirty && !$v.loginForm.password.required" class="text-red-600 text-sm">Please type your password.</span>
                </div>
                <div class="mb-2">
                    <label class="block text-gray-700 text-sm mb-2 font-bold uppercase">
                        <input class="mr-2 leading-tight" type="checkbox" v-model="loginForm.remember_me">
                        <span class="text-sm uppercase">Remember Me</span>
                    </label>
                </div>
                <div class="mb-4 flex" :class="[hasLoginErrors ? 'justify-between' : 'justify-end']">
                    <span class="text-sm text-red-600" v-if="hasLoginErrors" v-html="loginError"></span>
                    <button type="submit" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2 hover:bg-orange-500 focus:outline-none">
                        <span v-if="this.isLoggingIn">Logging In</span>
                        <span v-else>Login</span>
                    </button>
                </div>
            </form>
        </div>
        <div class="sm:mt-6 mt-4">
            <div class="mb-1 flex justify-center sm:px-0 px-5">
                <router-link to="/forgot-password" class="inline-block text-gray-700 text-xs font-bold mb-2 uppercase mr-6 hover:underline">Forgot Password</router-link>
                <a href="#" class="inline-block text-gray-700 text-xs font-bold mb-2 uppercase hover:underline">Contact Us</a>
            </div>
            <div class="mb-1 flex justify-center sm:px-0 px-5">
                <small class="text-gray-700 text-xs font-bold mb-2 uppercase">Please contact support if you want to create an account on multiline.io</small>
            </div>
            <div class="hidden sm:flex justify-center sm:px-0 px-5">
                <small class="text-gray-700 text-xs font-bold mb-2 uppercase"><i class="far fa-question-circle"></i> Best viewed on Google Chrome, Firefox or Safari</small>
            </div>
        </div>
    </div>
</template>

<script>
import { required, email } from 'vuelidate/lib/validators'
import Cookies from 'js-cookie'
import Swal from 'sweetalert2'

export default {
    name: 'Login',
    data() {
        return {
            loginForm: {
                email: '',
                password: '',
                remember_me: false
            },
            hasLoginErrors: false,
            loginError: '',
            isLoggingIn: false
        }
    },
    head: {
        title() {
            return {
                inner: 'Login'
            }
        }
    },
    validations: {
        loginForm: {
            email: { required, email },
            password: { required }
        }
    },
    mounted() {
        if(this.$store.state.auth.isResetPasswordTokenInvalid) {
            Swal.fire({
                icon: 'error',
                text: this.$store.state.auth.resetPasswordInvalidTokenError
            })
            this.$store.commit('auth/SET_RESET_PASSWORD_EMAIL', '')
            this.$store.commit('auth/SET_IS_RESET_PASSWORD_TOKEN_INVALID', false)
            this.$store.commit('auth/SET_RESET_PASSWORD_INVALID_TOKEN_ERROR', '')
        }

        if(Cookies.get('new_access')) {
            Swal.fire({
                icon: 'error',
                text: 'Your account has been logged out due to multiple login.'
            })
            .then(response => {
                Cookies.remove('new_access')
            })
        }
    },
    methods: {
        async login() {
            if (!this.$v.loginForm.$invalid) {
                this.isLoggingIn = true
                try {
                    const response = await axios.post('/v1/auth/login', { email: this.loginForm.email, password: this.loginForm.password })
                    const user = await axios.get('/v1/user', { headers: { 'Authorization': `Bearer ${response.data.access_token}` } })
                    if(this.loginForm.remember_me) {
                        await Cookies.set('mltoken', response.data.access_token, { expires: new Date(response.data.expires_at) })
                        await Cookies.set('display_name', user.data.data.name, { expires: new Date(response.data.expires_at) })
                    } else {
                        await Cookies.set('mltoken', response.data.access_token)
                        await Cookies.set('display_name', user.data.data.name)
                    }
                    location.reload('/')
                    setTimeout(() => {
                        this.$store.commit('auth/SET_IS_AUTHENTICATED', true)
                    }, 2000)
                } catch(err) {
                    this.isLoggingIn = false
                    this.hasLoginErrors = true
                    this.loginError = err.response.data.message
                }
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

<style lang="scss">
    input {
        background: #FFFFFF;
        border-style: solid;
    }
        input:-webkit-autofill{
            transition: background-color 5000s ease-in-out 0s;

            -webkit-text-fill-color: #444444 !important;
        }

    .text-white {
        color: #FFFFFF !important;
    }
</style>
