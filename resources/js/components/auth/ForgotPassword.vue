<template>
    <div class="forgot-password">
        <div class="mx-auto sm:bg-white sm:shadow-lg md:w-160 sm:w-120 xs:w-100 w-full h-auto sm:px-12 px-4 pt-12 pb-4 mt-6">
            <p class="text-gray-700 text-lg mb-2 font-bold uppercase">Forgot Password</p>
            <p class="text-green-500 text-sm mb-2 font-bold" v-if="emailMessage">We have e-mailed your password reset link!</p>
            <form method="POST" @submit.prevent="sendEmailToResetPassword">
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm mb-2 font-bold uppercase">Email</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none text-xs" :class="{'border-red-600': $v.email.$error}" id="email" type="text" placeholder="Receive password reset link using your email." v-model="$v.email.$model" @keyup="clearEmailMessage">
                    <span v-if="$v.email.$dirty && !$v.email.required" class="text-red-600 text-sm">Please type your email.</span>
                    <span v-if="$v.email.$dirty && !$v.email.email" class="text-red-600 text-sm">Please type a valid email.</span>
                </div>

                <div class="mb-4 flex sm:justify-end justify-center">
                    <button type="submit" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2 hover:bg-orange-500 focus:outline-none" :isSending="isSending">
                        <span v-if="isSending">Sending...</span>
                        <span v-else>Send Password Reset Link</span>
                    </button>
                </div>
            </form>
        </div>
        <div class="mt-6">
            <div class="flex justify-center">
                <a :href="loginRoute" class="text-gray-700 text-xs font-bold mb-2 uppercase mr-6 hover:underline">Go back to login</a>
            </div>
        </div>
    </div>
</template>

<script>
import { required, email } from 'vuelidate/lib/validators'
export default {
    name:'ForgotPassword',
    data() {
        return {
            email:'',
            emailMessage:false,
            isSending:false,
            loginRoute:`${process.env.MIX_APP_URL}/login`
        }
    },
    created() {
        document.title = 'Forgot Password - Multiline'
    },
    validations:{
        email:{required, email}
    },
    methods:{
        sendEmailToResetPassword() {
            if(!this.$v.email.$invalid) {
                this.isSending = true
                axios.post('/password/email', { email: this.email })
                .then(response => {
                    this.isSending = false
                    this.emailMessage = true
                })
                .catch(err => console.log(err))
            } else {
                this.$v.email.$touch()
            }
        },
        clearEmailMessage() {
            this.emailMessage = ''
        }
    }
}
</script>
