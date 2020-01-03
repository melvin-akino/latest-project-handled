<template>
    <div class="reset-password">
        <div class="mx-auto sm:bg-white sm:shadow-lg md:w-160 sm:w-120 xs:w-100 w-full h-auto sm:px-12 px-4 pt-12 pb-4 mt-6">
            <p class="text-gray-700 text-lg mb-2 font-bold uppercase">Reset Password</p>
            <form method="POST" @submit.prevent="resetPassword">
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm mb-2 font-bold uppercase">Email</label>
                    <input id="email" type="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none hover:cursor-not-allowed" :value="email" disabled>
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm mb-2 font-bold uppercase">New Password</label>
                    <input id="password" type="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" v-model="$v.resetPasswordForm.password.$model">
                    <span v-if="$v.resetPasswordForm.password.$dirty && !$v.resetPasswordForm.password.required" class="text-red-600 text-sm">Please type a new password.</span>
                    <span v-if="$v.resetPasswordForm.password.$dirty && !$v.resetPasswordForm.password.minLength" class="text-red-600 text-sm">Password must have a minimum of 6 characters.</span>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="block text-gray-700 text-sm mb-2 font-bold uppercase">Confirm New Password</label>
                    <input id="password_confirmation" type="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" v-model="$v.resetPasswordForm.password_confirmation.$model">
                    <span v-if="$v.resetPasswordForm.password_confirmation.$dirty && !$v.resetPasswordForm.password_confirmation.sameAs" class="text-red-600 text-sm">Passwords do not match.</span>
                </div>

                <div class="mb-4 flex justify-end">
                    <button type="submit" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2 hover:bg-orange-500 focus:outline-none">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import {required, sameAs, minLength} from 'vuelidate/lib/validators'
export default {
    name:'ResetPassword',
    props:['email', 'token'],
    data() {
        return {
            resetPasswordForm:{
                password:'',
                password_confirmation:'',
            }
        }
    },
    created() {
        document.title = 'Reset Password - Multiline'
    },
    validations:{
        resetPasswordForm: {
            password:{required, minLength:minLength(6)},
            password_confirmation:{sameAs:sameAs('password')}
        }
    },
    methods:{
        resetPassword() {
            if(!this.$v.resetPasswordForm.$invalid) {
                axios.post('/password/reset',{ token:this.token, email:this.email, password: this.resetPasswordForm.password, password_confirmation:this.resetPasswordForm.password_confirmation})
                .then(response => window.location.href = '/')
                .catch(err => console.log(err))
            } else {
                this.$v.resetPasswordForm.password.$touch()
                this.$v.resetPasswordForm.password_confirmation.$touch()
            }
        }
    }
}
</script>
