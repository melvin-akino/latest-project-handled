<template>
    <div class="forgot-password">
        <div class="mx-auto sm:bg-white sm:shadow-lg md:w-160 sm:w-120 xs:w-100 w-full h-auto sm:px-12 px-4 pt-12 pb-4 mt-6">
            <p class="text-gray-700 text-lg mb-2 font-bold uppercase">Forgot Password</p>
            <p class="text-green-500 text-sm mb-2 font-bold">{{emailMessage}}</p>
            <form method="POST" @submit.prevent="sendEmailToResetPassword">
                <div class="mb-4">
                    <label for="email" class="absolute bg-white block text-sm ml-3 mb-2 px-3 font-bold uppercase" :class="{ 'text-red-600': $v.email.$error, 'text-gray-700': !$v.email.$error }">Email</label>
                    <input class="shadow appearance-none border border-gray-400 rounded w-full mt-3 py-4 px-3 text-gray-700 leading-tight focus:outline-none text-sm" :class="{'border-red-600': $v.email.$error}" id="email" type="text" placeholder="Receive password reset link using your email." v-model="$v.email.$model" @keyup="clearMessages">
                    <span v-if="$v.email.$dirty && !$v.email.required" class="text-red-600 text-sm">Please type your email.</span>
                    <span v-if="$v.email.$dirty && !$v.email.email" class="text-red-600 text-sm">Please type a valid email.</span>
                </div>

                <div class="flex flex-col">
                    <p class="text-sm text-red-600" v-for="forgotPasswordError in forgotPasswordErrors" :key="forgotPasswordError">{{forgotPasswordError}}</p>
                </div>

                <div class="mb-4 flex sm:justify-end justify-center">
                    <button type="submit" class="bg-orange-400 mt-3 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2 hover:bg-orange-500 focus:outline-none" :disabled="isSending">
                        <span v-if="isSending">Sending...</span>
                        <span v-else>Send Password Reset Link</span>
                    </button>
                </div>
            </form>
        </div>
        <div class="mt-6">
            <div class="flex justify-center">
                <router-link to="/login" class="text-gray-700 text-xs font-bold mb-2 uppercase mr-6 hover:underline">Go back to login</router-link>
            </div>
        </div>
    </div>
</template>

<script>
import { required, email } from 'vuelidate/lib/validators'

export default {
    name: 'ForgotPassword',
    data() {
        return {
            email: '',
            emailMessage: '',
            isSending: false,
            forgotPasswordErrors: []
        }
    },
    head: {
        title() {
            return {
                inner: 'Forgot Password'
            }
        }
    },
    validations: {
        email: { required, email }
    },
    methods: {
        sendEmailToResetPassword() {
            this.emailMessage = ''
            if (!this.$v.email.$invalid) {
                this.isSending = true
                axios.post('/v1/auth/password/create', { email: this.email })
                .then(response => {
                    this.emailMessage = response.data.message
                    this.isSending = false
                })
                .catch(err => {
                    this.isSending = false
                    this.forgotPasswordErrors = []
                    if (err.response.status===422) {
                        Object.values(err.response.data.errors).forEach(errorType => {
                            errorType.forEach(error => {
                                this.forgotPasswordErrors.push(error)
                            })
                        })
                    } else if (err.response.status==404) {
                        this.forgotPasswordErrors.push(err.response.data.message)
                    }
                })
            } else {
                this.$v.email.$touch()
            }
        },
        clearMessages() {
            this.emailMessage = ''
            this.forgotPasswordErrors = []
        }
    }
}
</script>

<style lang="scss">
    input {
        background: #FFFFFF;
        border-style: solid;
    }

    .text-white {
        color: #FFFFFF !important;
    }
</style>
