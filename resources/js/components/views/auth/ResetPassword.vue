<template>
    <div class="reset-password">
        <div class="mx-auto sm:bg-white sm:shadow-lg md:w-160 sm:w-120 xs:w-100 w-full h-auto sm:px-12 px-4 pt-12 pb-4 mt-6">
            <form method="POST" @submit.prevent="resetPassword" v-if="!isResetPasswordSuccess">
                <p class="text-gray-700 text-lg mb-2 font-bold uppercase">Reset Password</p>
                <div class="mb-4">
                    <label for="email" class="absolute bg-white block text-sm ml-3 mb-2 px-3 font-bold uppercase">Email</label>
                    <input id="email" type="email" class="shadow appearance-none border border-gray-400 rounded w-full mt-3 py-4 px-3 text-gray-700 leading-tight focus:outline-none hover:cursor-not-allowed" v-model="$v.resetPasswordForm.email.$model" disabled>
                    <span v-if="$v.resetPasswordForm.email.$dirty && !$v.resetPasswordForm.email.required" class="text-red-600 text-sm">Email is required.</span>
                    <span v-if="$v.resetPasswordForm.email.$dirty && !$v.resetPasswordForm.email.email" class="text-red-600 text-sm">Email must be valid.</span>
                </div>

                <div class="mb-4">
                    <label for="password" class="absolute bg-white block text-sm ml-3 mb-2 px-3 font-bold uppercase"
                        :class="{ 'text-red-600': $v.resetPasswordForm.password.$error, 'text-black': !$v.resetPasswordForm.password.$error }">New Password</label>
                    <input id="password" type="password" class="shadow appearance-none border border-gray-400 rounded w-full mt-3 py-4 px-3 text-gray-700 leading-tight focus:outline-none"
                        :class="{ 'border-red-600': $v.resetPasswordForm.password.$error, 'border-gray-400': !$v.resetPasswordForm.password.$error }"
                        v-model="$v.resetPasswordForm.password.$model"
                        @keyup="clearPasswordResetFormError">
                    <span v-if="$v.resetPasswordForm.password.$dirty && !$v.resetPasswordForm.password.required" class="text-red-600 text-sm">Please type a new password.</span>
                    <span v-if="$v.resetPasswordForm.password.$dirty && !$v.resetPasswordForm.password.minLength" class="text-red-600 text-sm">Password must have a minimum of 6 characters.</span>
                    <span v-if="$v.resetPasswordForm.password.$dirty && !$v.resetPasswordForm.password.maxLength" class="text-red-600 text-sm">Password must have a maximum of 32 characters.</span>
                    <span v-if="$v.resetPasswordForm.password.$dirty && !$v.resetPasswordForm.password.alphaNum" class="text-red-600 text-sm">Password should only contain alphanumeric characters.</span>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="absolute bg-white block text-sm ml-3 mb-2 px-3 font-bold uppercase"
                        :class="{ 'text-red-600': $v.resetPasswordForm.password_confirmation.$error, 'text-black': !$v.resetPasswordForm.password_confirmation.$error }">Confirm New Password</label>
                    <input id="password_confirmation" type="password" class="shadow appearance-none border border-gray-400 rounded w-full mt-3 py-4 px-3 text-gray-700 leading-tight focus:outline-none"
                        :class="{ 'border-red-600': $v.resetPasswordForm.password_confirmation.$error, 'border-gray-400': !$v.resetPasswordForm.password_confirmation.$error }"
                        v-model="$v.resetPasswordForm.password_confirmation.$model"
                        @keyup="clearPasswordResetFormError">
                    <span v-if="$v.resetPasswordForm.password_confirmation.$dirty && !$v.resetPasswordForm.password_confirmation.sameAs" class="text-red-600 text-sm">Passwords do not match.</span>
                </div>

                <div class="mb-4 flex justify-between">
                    <span class="text-sm text-red-600">{{resetPasswordFormError}}</span>
                    <button type="submit" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2 hover:bg-orange-500 focus:outline-none">
                        <span v-if="isResettingPassword">Changing Password...</span>
                        <span v-else>Reset Password</span>
                    </button>
                </div>
            </form>
            <div class="h-20 w-full mr-12 text-gray-700 font-bold mb-2 uppercase flex flex-col justify-center items-center rounded-lg" v-if="isResetPasswordSuccess">
                <div class="text-green-400 text-2xl">Your password has been changed!</div>
                <div><router-link to="/login" class="underline">Login Here</router-link></div>
            </div>
        </div>
    </div>
</template>

<script>
import {required, email, sameAs, minLength, maxLength, alphaNum } from 'vuelidate/lib/validators'

export default {
    name: 'ResetPassword',
    data() {
        return {
            resetPasswordForm: {
                email: this.$store.state.auth.resetPasswordEmail,
                password: '',
                password_confirmation: '',
            },
            resetPasswordFormError: '',
            isResettingPassword: false,
            isResetPasswordSuccess: false
        }
    },
    head: {
        title() {
            return {
                inner: 'Reset Password'
            }
        }
    },
    validations: {
        resetPasswordForm: {
            email: { required, email },
            password: { required, minLength:minLength(6), maxLength:maxLength(32), alphaNum },
            password_confirmation: { sameAs:sameAs('password') }
        }
    },
    methods: {
        resetPassword() {
            if (!this.$v.resetPasswordForm.$invalid) {
                this.isResettingPassword = true
                axios.post('/v1/auth/password/reset/', { token: this.$route.params.token, email: this.resetPasswordForm.email, password: this.resetPasswordForm.password, password_confirmation: this.resetPasswordForm.password_confirmation })
                .then(response => {
                    this.isResetPasswordSuccess = true
                    this.isResettingPassword = false
                })
                .catch(err => {
                    this.resetPasswordFormError = err.response.data.message
                    this.isResettingPassword = false
                })
            } else {
                this.$v.resetPasswordForm.password.$touch()
                this.$v.resetPasswordForm.password_confirmation.$touch()
            }
        },
        clearPasswordResetFormError() {
            this.resetPasswordFormError = ''
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
