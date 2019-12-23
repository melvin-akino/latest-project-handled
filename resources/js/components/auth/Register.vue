<template>
    <div class="register">
        <div class="mx-auto bg-white shadow-lg w-160 h-auto px-12 pt-12 pb-4 mt-6">
            <form method="POST" @submit.prevent="register">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="name">
                        Name
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.name.$error}" id="name" type="text" placeholder="e.g. Tony Stark, Steve Rogers" v-model="$v.registerForm.name.$model">
                    <span v-if="$v.registerForm.name.$dirty && !$v.registerForm.name.required" class="text-red-600 text-sm">Please type your name.</span>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="email">
                        Email
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.email.$error}" id="email" type="text" placeholder="e.g. iampogi@pogi.com" v-model="$v.registerForm.email.$model">
                    <span v-if="$v.registerForm.email.$dirty && !$v.registerForm.email.required" class="text-red-600 text-sm">Please type an email.</span>
                    <span v-if="$v.registerForm.email.$dirty && !$v.registerForm.email.email" class="text-red-600 text-sm">Please type a valid email.</span>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="password">
                        Password
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.password.$error}" id="password" type="password" placeholder="Password" v-model="$v.registerForm.password.$model">
                    <span v-if="$v.registerForm.password.$dirty && !$v.registerForm.password.required" class="text-red-600 text-sm">Please type a password.</span>
                    <span v-if="$v.registerForm.password.$dirty && !$v.registerForm.password.minLength" class="text-red-600 text-sm">Password must have a minimum of 8 characters.</span>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="password_confirm">
                        Confirm Password
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.password_confirmation.$error}" id="password_confirm" type="password" placeholder="Confirm Password" v-model="$v.registerForm.password_confirmation.$model">
                    <span v-if="$v.registerForm.password_confirmation.$dirty && !$v.registerForm.password_confirmation.sameAs" class="text-red-600 text-sm">Password does not match.</span>
                </div>
                <div class="mb-4 flex justify-end">
                    <button type="submit" class="bg-orange-400 text-white rounded-full font-bold text-sm uppercase px-12 py-5 hover:bg-orange-500 focus:outline-none">Create Account</button>
                </div>
            </form>
        </div>
        <div class="mt-6">
            <div class="flex justify-center">
                <small class="text-gray-700 text-xs font-bold mb-2 uppercase">Already have an account? <a :href="loginRoute" class="hover:underline">Login</a></small>
            </div>
        </div>
    </div>
</template>

<script>
import { required, minLength, sameAs, email } from 'vuelidate/lib/validators'
export default {
    name:'Register',
    data() {
        return {
            registerForm: {
                name:'',
                email:'',
                password:'',
                password_confirmation:''
            },
            loginRoute:`${process.env.MIX_APP_URL}/login`
        }
    },
    created() {
        document.title = 'Register - Multiline'
    },
    validations:{
        registerForm:{
            name:{required},
            email:{required, email},
            password:{required, minLength:minLength(8)},
            password_confirmation:{sameAs:sameAs('password')}
        }
    },
    methods:{
        register() {
            if(!this.$v.registerForm.$invalid) {
                axios.post('/register', { name:this.registerForm.name, email:this.registerForm.email, password:this.registerForm.password, password_confirmation:this.registerForm.password_confirmation })
                .then(response => window.location.href = '/')
                .catch(err => {
                    console.log(err)
                })
            } else {
                this.$v.registerForm.name.$touch()
                this.$v.registerForm.email.$touch()
                this.$v.registerForm.password.$touch()
                this.$v.registerForm.password_confirmation.$touch()
            }
        }
    }
}
</script>
