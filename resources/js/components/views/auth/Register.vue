<template>
    <div class="register">
        <div class="mx-auto sm:bg-white sm:shadow-lg md:w-160 sm:w-120 xs:w-100 w-full h-auto sm:px-12 px-4 sm:pt-8 pt-6 pb-4 mt-6">
            <form method="POST">
                <div class="step1" v-if="step === 1">
                    <h3 class="block text-gray-700 text-lg mb-2 font-bold uppercase">Register - Step 1 of 3</h3>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="name">Display Name</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.name.$error || registerErrors.hasOwnProperty('name')}" id="name" type="text" placeholder="e.g. iampogi" v-model.trim="$v.registerForm.step1.name.$model" @keypress="removeError('name')">
                        <span v-if="$v.registerForm.step1.name.$dirty && !$v.registerForm.step1.name.required" class="text-red-600 text-sm">Please type a display name.</span>
                        <span v-if="$v.registerForm.step1.name.$dirty && !$v.registerForm.step1.name.alphaNum" class="text-red-600 text-sm">Username should only contain alphanumeric characters.</span>
                        <span v-if="$v.registerForm.step1.name.$dirty && !$v.registerForm.step1.name.minLength" class="text-red-600 text-sm">Username must have a minimum of 6 characters.</span>
                        <span v-if="$v.registerForm.step1.name.$dirty && !$v.registerForm.step1.name.maxLength" class="text-red-600 text-sm">Username must have a maximum of 32 characters.</span>
                        <span v-if="registerErrors.hasOwnProperty('name')" class="text-red-600 text-sm">{{registerErrors.name[0]}}</span>
                    </div>
                    <div class="flex justify-evenly">
                        <div class="mb-2 mr-3 w-1/2">
                            <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="firstname">First Name</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.firstname.$error}" id="firstname" type="text" placeholder="e.g. Tony, Steve" v-model.trim="$v.registerForm.step1.firstname.$model">
                            <span v-if="$v.registerForm.step1.firstname.$dirty && !$v.registerForm.step1.firstname.required" class="text-red-600 text-sm">Please type your first name.</span>
                            <span v-if="$v.registerForm.step1.firstname.$dirty && !$v.registerForm.step1.firstname.maxLength" class="text-red-600 text-sm">First name must have a maximum of 32 characters.</span>
                        </div>
                        <div class="mb-2 w-1/2">
                            <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="lastname">Last Name</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.lastname.$error}" id="lastname" type="text" placeholder="e.g. Stark, Rogers" v-model.trim="$v.registerForm.step1.lastname.$model">
                            <span v-if="$v.registerForm.step1.lastname.$dirty && !$v.registerForm.step1.lastname.required" class="text-red-600 text-sm">Please type your last name.</span>
                            <span v-if="$v.registerForm.step1.firstname.$dirty && !$v.registerForm.step1.lastname.maxLength" class="text-red-600 text-sm">Last name must have a maximum of 32 characters.</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="email">Email</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.email.$error || registerErrors.hasOwnProperty('email')}" id="email" type="text" placeholder="e.g. iampogi@pogi.com" v-model.trim="$v.registerForm.step1.email.$model" @keypress="removeError('email')">
                        <span v-if="$v.registerForm.step1.email.$dirty && !$v.registerForm.step1.email.required" class="text-red-600 text-sm">Please type an email.</span>
                        <span v-if="$v.registerForm.step1.email.$dirty && !$v.registerForm.step1.email.email" class="text-red-600 text-sm">Please type a valid email.</span>
                        <span v-if="$v.registerForm.step1.email.$dirty && !$v.registerForm.step1.email.maxLength" class="text-red-600 text-sm">Email must have a maximum of 100 chracters.</span>
                        <span v-if="registerErrors.hasOwnProperty('email')" class="text-red-600 text-sm">{{registerErrors.email[0]}}</span>
                    </div>
                    <div class="flex justify-evenly">
                        <div class="mb-2 mr-3 w-1/2">
                            <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="password">Password</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.password.$error}" id="password" type="password" placeholder="Password" v-model="$v.registerForm.step1.password.$model">
                            <span v-if="$v.registerForm.step1.password.$dirty && !$v.registerForm.step1.password.required" class="text-red-600 text-sm">Please type a password.</span>
                            <span v-if="$v.registerForm.step1.password.$dirty && !$v.registerForm.step1.password.minLength" class="text-red-600 text-sm">Password must have a minimum of 6 characters.</span>
                            <span v-if="$v.registerForm.step1.password.$dirty && !$v.registerForm.step1.password.maxLength" class="text-red-600 text-sm">Password must have a maximum of 32 characters.</span>
                            <span v-if="$v.registerForm.step1.password.$dirty && !$v.registerForm.step1.password.alphaNum" class="text-red-600 text-sm">Password should only contain alphanumeric characters.</span>
                        </div>
                        <div class="mb-2 w-1/2">
                            <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="password_confirmation">Confirm Password</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.password_confirmation.$error}" id="password_confirmation" type="password" placeholder="Confirm Password" v-model="$v.registerForm.step1.password_confirmation.$model">
                            <span v-if="$v.registerForm.step1.password_confirmation.$dirty && !$v.registerForm.step1.password_confirmation.sameAs" class="text-red-600 text-sm">Password does not match.</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="password_confirmation">Birthdate (optional)</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" id="birthdate" type="date" placeholder="Birthdate" v-model="registerForm.step1.birthdate">
                    </div>
                </div>

                <div class="step2" v-if="step === 2">
                    <h3 class="block text-gray-700 text-lg mb-2 font-bold uppercase">Register - Step 2 of 3</h3>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="address">Address</label>
                        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.address.$error}" id="address" v-model.trim="$v.registerForm.step2.address.$model"></textarea>
                        <span v-if="$v.registerForm.step2.address.$dirty && !$v.registerForm.step2.address.required" class="text-red-600 text-sm">Address is required.</span>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="country">Country</label>
                        <div class="relative">
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.country_id.$error || registerErrors.hasOwnProperty('country_id')}" id="country" v-model="$v.registerForm.step2.country_id.$model" @change="removeError('country_id')">
                                <option :value="null" disabled>Select Country</option>
                                <option v-for="country in countries" :key="country.id" :value="country.id">{{country.country_name}}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                        <span v-if="$v.registerForm.step2.country_id.$dirty && !$v.registerForm.step2.country_id.required" class="text-red-600 text-sm">Country is required.</span>
                        <span v-if="$v.registerForm.step2.country_id.$dirty && !$v.registerForm.step2.country_id.numeric" class="text-red-600 text-sm">Country ID should be numeric.</span>
                        <span v-if="registerErrors.hasOwnProperty('country_id')" class="text-red-600 text-sm">{{registerErrors.country_id[0]}}</span>
                    </div>
                    <div class="flex justify-evenly">
                        <div class="mb-2 mr-3 w-full">
                            <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="state">State</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.state.$error}" id="state" type="text" placeholder="State" v-model.trim="$v.registerForm.step2.state.$model">
                            <span v-if="$v.registerForm.step2.state.$dirty && !$v.registerForm.step2.state.required" class="text-red-600 text-sm">State is required.</span>
                            <span v-if="$v.registerForm.step2.state.$dirty && !$v.registerForm.step2.state.maxLength" class="text-red-600 text-sm">State must have a maximum of 100 characters.</span>
                        </div>
                        <div class="mb-2 w-full">
                            <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="city">City</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.city.$error}" id="city" type="text" placeholder="City" v-model.trim="$v.registerForm.step2.city.$model">
                            <span v-if="$v.registerForm.step2.city.$dirty && !$v.registerForm.step2.city.required" class="text-red-600 text-sm">City is required.</span>
                            <span v-if="$v.registerForm.step2.city.$dirty && !$v.registerForm.step2.city.maxLength" class="text-red-600 text-sm">City must have a maximum of 100 characters.</span>
                        </div>
                    </div>
                    <div class="flex justify-evenly">
                        <div class="mb-2 mr-3 w-full">
                            <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="postcode">Post Code</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.postcode.$error}" id="postcode" type="text" placeholder="Post Code" v-model.trim="$v.registerForm.step2.postcode.$model">
                            <span v-if="$v.registerForm.step2.postcode.$dirty && !$v.registerForm.step2.postcode.required" class="text-red-600 text-sm">Postcode is required.</span>
                            <span v-if="$v.registerForm.step2.postcode.$dirty && !$v.registerForm.step2.postcode.numeric" class="text-red-600 text-sm">Postcode should be numeric.</span>
                            <span v-if="$v.registerForm.step2.postcode.$dirty && !$v.registerForm.step2.postcode.minLength" class="text-red-600 text-sm">Postcode must have a minimum of 3 digits.</span>
                            <span v-if="$v.registerForm.step2.postcode.$dirty && !$v.registerForm.step2.postcode.maxLength" class="text-red-600 text-sm">Postcode must have a maximum of 6 digits.</span>
                        </div>
                        <div class="mb-2 w-full">
                            <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="phone">Phone</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.phone.$error}" id="phone" type="text" placeholder="Phone" v-model.trim="$v.registerForm.step2.phone.$model">
                            <span v-if="$v.registerForm.step2.phone.$dirty && !$v.registerForm.step2.phone.required" class="text-red-600 text-sm">Phone number is required.</span>
                            <span v-if="$v.registerForm.step2.phone.$dirty && !$v.registerForm.step2.phone.numeric" class="text-red-600 text-sm">Phone number should be numeric.</span>
                            <span v-if="$v.registerForm.step2.phone.$dirty && !$v.registerForm.step2.phone.minLength" class="text-red-600 text-sm">Phone number must have a minimum length of 6 digits.</span>
                            <span v-if="$v.registerForm.step2.phone.$dirty && !$v.registerForm.step2.phone.maxLength" class="text-red-600 text-sm">Phone number must have a maximum length of 32 digits.</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="currency_id">Currency</label>
                        <div class="relative">
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.currency_id.$error || registerErrors.hasOwnProperty('currency_id')}" id="currency_id" v-model="$v.registerForm.step2.currency_id.$model" @change="removeError('currency_id')">
                                <option :value="null" disabled>Select Currency</option>
                                <option v-for="currency in currencies" :key="currency.id" :value="currency.id">{{currency.currency}}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                        <span v-if="$v.registerForm.step2.currency_id.$dirty && !$v.registerForm.step2.currency_id.required" class="text-red-600 text-sm">Currency is required.</span>
                        <span v-if="$v.registerForm.step2.currency_id.$dirty && !$v.registerForm.step2.currency_id.numeric" class="text-red-600 text-sm">Currency ID should be numeric.</span>
                        <span v-if="registerErrors.hasOwnProperty('currency_id')" class="text-red-600 text-sm">{{registerErrors.currency_id[0]}}</span>
                    </div>
                </div>

                <div class="step3" v-if="step===3">
                    <h3 class="block text-gray-700 text-lg mb-2 font-bold uppercase">Register - Step 3 of 3</h3>
                    <p class="text-sm mt-4">By clicking register you are automatically accepting out platform's terms and conditions.</p>
                    <p class="text-sm">Please read through these documents carefully and understand them before proceeding.</p>
                    <div class="mb-12">
                        <ul class="list-disc ml-4 mt-4">
                            <li><a href="#" class="text-sm text-orange-500">Terms of Use</a></li>
                            <li><a href="#" class="text-sm text-orange-500">Privacy Policy</a></li>
                            <li><a href="#" class="text-sm text-orange-500">Responsible Gambling</a></li>
                        </ul>
                    </div>
                    <label class="block">
                        <input class="mr-2 leading-tight" type="checkbox" v-model="$v.registerForm.step3.agreeToTermsAndConditions.$model">
                        <span class="text-sm">Accept all these terms and conditions</span>
                    </label>
                    <span v-if="$v.registerForm.step3.agreeToTermsAndConditions.$dirty && !$v.registerForm.step3.agreeToTermsAndConditions.checked" class="text-red-600 text-sm">You must accept all terms and conditions.</span>
                </div>

                <div class="mb-2 flex justify-end mt-3">
                    <button type="button" v-if="step != 1" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2 hover:bg-orange-500 focus:outline-none"  @click.prevent="prevStep">Previous</button>
                    <button type="button" v-if="step != totalSteps" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2  ml-2 hover:bg-orange-500 focus:outline-none" @click.prevent="nextStep">Next</button>
                    <button type="submit" v-if="step === totalSteps" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2 ml-2 hover:bg-orange-500 focus:outline-none" @click.prevent="register" :disabled="isRegistering || isRegisterSuccessful">
                        <span v-if="isRegistering">Creating Account...</span>
                        <span v-else>Create Account</span>
                    </button>
                </div>
            </form>
        </div>
        <div class="mt-6">
            <div class="flex justify-center sm:pb-0 pb-12">
                <small class="text-gray-700 text-xs font-bold mb-2 uppercase">Already have an account? <router-link to="/login" class="hover:underline">Login</router-link></small>
            </div>
        </div>
    </div>
</template>

<script>
import { required, minLength, maxLength, alphaNum, sameAs, email, numeric } from 'vuelidate/lib/validators'
import Swal from 'sweetalert2'
import Cookies from 'js-cookie'

export default {
    name: 'Register',
    data() {
        return {
            step: 1,
            totalSteps: 3,
            registerForm: {
                step1: {
                    name: '',
                    firstname: '',
                    lastname: '',
                    email: '',
                    password: '',
                    password_confirmation: '',
                    birthdate: ''
                },
                step2: {
                    address: '',
                    country_id: null,
                    state: '',
                    city: '',
                    postcode: '',
                    phone: '',
                    currency_id: null
                },
                step3: {
                    agreeToTermsAndConditions: false
                }
            },
            registerErrors: {},
            countries: [],
            currencies: [
                { id: 1, currency: 'CNY' },
                // { id: 2, currency: 'USD' }
            ],
            isRegistering: false,
            isRegisterSuccessful: false
        }
    },
    head: {
        title() {
            return {
                inner: 'Register'
            }
        }
    },
    validations: {
        registerForm: {
            step1: {
                name: { required, minLength:minLength(6), maxLength:maxLength(32), alphaNum },
                firstname: { required, maxLength:maxLength(32) },
                lastname: { required, maxLength:maxLength(32) },
                email: { required, email, maxLength:maxLength(100) },
                password: { required, minLength: minLength(6), maxLength:maxLength(32), alphaNum },
                password_confirmation: { sameAs: sameAs('password') },
                birthdate: {  }
            },
            step2: {
                address: { required },
                country_id: { required, numeric },
                state:  { required, maxLength:maxLength(100) },
                city: { required, maxLength:maxLength(100) },
                postcode: { required, numeric, minLength:minLength(3), maxLength:maxLength(6) },
                phone: { required, numeric, minLength:minLength(6), maxLength:maxLength(32) },
                currency_id: { required, numeric }
            },
            step3: {
                agreeToTermsAndConditions: { checked: value => value === true  }
            }
        }
    },
    computed: {
        checkIfCurrentStepIsInvalid() {
            return this.$v.registerForm[`step${this.step}`].$invalid || Object.entries(this.registerErrors).length != 0
        }
    },
    mounted() {
        this.countries = this.$store.state.settings.settingsData.country
    },
    methods: {
        triggerValidationErrors() {
            if (this.$v.registerForm.step1.$invalid) {
                Object.keys(this.registerForm.step1).forEach(field => {
                    this.$v.registerForm.step1[field].$touch()
                })
            } else if (this.$v.registerForm.step2.$invalid) {
                Object.keys(this.registerForm.step2).forEach(field => {
                    this.$v.registerForm.step2[field].$touch()
                })
            } else if (this.$v.registerForm.step3.$invalid) {
                Object.keys(this.registerForm.step3).forEach(field => {
                    this.$v.registerForm.step3[field].$touch()
                })
            }
        },
        prevStep() {
            this.step--
        },
        nextStep() {
            if (!this.checkIfCurrentStepIsInvalid) {
                this.step++
            } else {
                this.triggerValidationErrors()
            }
        },
        async loginAfterRegister() {
            try {
                const response = await axios.post('/v1/auth/login', { email: this.registerForm.step1.email, password: this.registerForm.step1.password })
                const user = await axios.get('/v1/user', { headers: { 'Authorization': `Bearer ${response.data.access_token}` } })
                Cookies.set('display_name', user.data.data.name)
                Cookies.set('mltoken', response.data.access_token)
                await location.reload('/')
                setTimeout(() => {
                    this.$store.commit('auth/SET_IS_AUTHENTICATED', true)
                }, 2000)
            } catch(err) {
                console.log(err)
                location.reload('/login')
                setTimeout(() => {
                    this.$store.commit('auth/SET_IS_AUTHENTICATED', false)
                }, 2000)
            }
        },
        register() {
            if (!this.$v.registerForm.$invalid) {
                let data = {
                    name: this.registerForm.step1.name,
                    firstname: this.registerForm.step1.firstname,
                    lastname: this.registerForm.step1.lastname,
                    email: this.registerForm.step1.email,
                    password: this.registerForm.step1.password,
                    password_confirmation: this.registerForm.step1.password_confirmation,
                    birthdate: this.registerForm.step1.birthdate,
                    address: this.registerForm.step2.address,
                    country_id: this.registerForm.step2.country_id,
                    state: this.registerForm.step2.state,
                    city: this.registerForm.step2.city,
                    postcode: this.registerForm.step2.postcode,
                    phone: this.registerForm.step2.phone,
                    currency_id: this.registerForm.step2.currency_id,
                }
                this.isRegistering = true
                axios.post('/v1/auth/register', data)
                .then(response => {
                    this.isRegistering = false
                    this.isRegisterSuccessful = true
                    Swal.fire({
                        icon: 'success',
                        html: `${response.data.message} <br> Logging In...`,
                        timer: 3000,
                        allowOutsideClick: false,
                        showConfirmButton: false
                    })

                    setTimeout(() => {
                        this.loginAfterRegister()
                    }, 3000)
                })
                .catch(err => {
                    this.isRegistering = false
                    this.registerErrors = err.response.data.errors
                    let errorFields = Object.keys(this.registerErrors).map(field => {
                        return field
                    })
                    Object.keys(this.registerForm).map(step => {
                        if(this.registerForm[step].hasOwnProperty(errorFields[0])) {
                            this.step = parseInt(step.split('')[step.length - 1])
                        }
                    })
                })
            } else {
                this.triggerValidationErrors()
            }
        },
        removeError(field) {
            delete this.registerErrors[field]
        }
    }
}
</script>
