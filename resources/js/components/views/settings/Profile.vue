<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="mb-6 flex">
                <div class="w-1/2 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Username</label>
                    <input type="text" id="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :value="$store.state.authUser.name" disabled>
                </div>
                <div class="w-1/2">
                    <label class="block capitalize text-gray-700 text-sm">Email</label>
                    <input type="text" id="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :value="$store.state.authUser.email" disabled>
                </div>
            </div>
            <div class="mb-6 flex">
                <div class="w-1/2 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">First Name</label>
                    <input type="text" id="firstName" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.profileSettingsForm.firstname.$error}" v-model="$v.profileSettingsForm.firstname.$model">
                    <span v-if="$v.profileSettingsForm.firstname.$dirty && !$v.profileSettingsForm.firstname.required" class="text-xs text-red-600">Please type your first name.</span>
                </div>
                <div class="w-1/2">
                    <label class="block capitalize text-gray-700 text-sm">Last Name</label>
                    <input type="text" id="lastname" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.profileSettingsForm.lastname.$error}" v-model="$v.profileSettingsForm.lastname.$model">
                    <span v-if="$v.profileSettingsForm.lastname.$dirty && !$v.profileSettingsForm.lastname.required" class="text-xs text-red-600">Please type your last name.</span>
                </div>
            </div>
            <div class="mb-6">
                <label class="block capitalize text-gray-700 text-sm">Address</label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.profileSettingsForm.address.$error}" id="address" v-model="$v.profileSettingsForm.address.$model"></textarea>
                <span v-if="$v.profileSettingsForm.address.$dirty && !$v.profileSettingsForm.address.required" class="text-xs text-red-600">Address is required.</span>
            </div>
            <div class="mb-6 flex">
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Country</label>
                    <div class="relative">
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.profileSettingsForm.country.$error}" id="country" v-model="$v.profileSettingsForm.country.$model" @change="resetStateAndCity">
                            <option :value="null" disabled>Select Country</option>
                            <option v-for="country in countries" :key="country.id" :value="country.id" :selected="country.id === profileSettingsForm.country">{{country.country}}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <span v-if="$v.profileSettingsForm.country.$dirty && !$v.profileSettingsForm.country.required" class="text-xs text-red-600">Country is required.</span>
                    <span v-if="$v.profileSettingsForm.country.$dirty && !$v.profileSettingsForm.country.inCountriesArray" class="text-xs text-red-600">Country is invalid.</span>
                </div>
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">State</label>
                    <div class="relative">
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.profileSettingsForm.state.$error}" id="state" v-model="$v.profileSettingsForm.state.$model">
                            <option :value="null" disabled>Select State</option>
                            <option v-for="state in statesDropdown" :key="state.id" :value="state.id" :selected="state.id === profileSettingsForm.state">{{state.state}}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <span v-if="$v.profileSettingsForm.state.$dirty && !$v.profileSettingsForm.state.required" class="text-xs text-red-600">State is required.</span>
                    <span v-if="$v.profileSettingsForm.state.$dirty && !$v.profileSettingsForm.state.inStatesArray" class="text-xs text-red-600">State is invalid.</span>
                </div>
                <div class="w-1/3">
                    <label class="block capitalize text-gray-700 text-sm">City</label>
                    <div class="relative">
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.profileSettingsForm.city.$error}" id="city" v-model="$v.profileSettingsForm.city.$model">
                            <option :value="null" disabled>Select City</option>
                            <option v-for="city in citiesDropdown" :key="city.id" :value="city.id" :selected="city.id === profileSettingsForm.city">{{city.city}}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <span v-if="$v.profileSettingsForm.city.$dirty && !$v.profileSettingsForm.city.required" class="text-xs text-red-600">City is required.</span>
                    <span v-if="$v.profileSettingsForm.city.$dirty && !$v.profileSettingsForm.city.inCitiesArray" class="text-xs text-red-600">City is invalid.</span>
                </div>
            </div>
            <div class="mb-6 flex">
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Post Code</label>
                    <input type="text" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.profileSettingsForm.postcode.$error}" id="postcode" v-model="$v.profileSettingsForm.postcode.$model">
                    <span v-if="$v.profileSettingsForm.postcode.$dirty && !$v.profileSettingsForm.postcode.required" class="text-xs text-red-600">Postcode is required.</span>
                </div>
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Phone Country Code</label>
                    <input type="text" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.profileSettingsForm.phone_country_code.$error}" id="phone_country_code" v-model="$v.profileSettingsForm.phone_country_code.$model">
                    <span v-if="$v.profileSettingsForm.phone_country_code.$dirty && !$v.profileSettingsForm.phone_country_code.required" class="text-xs text-red-600">Phone country code is required.</span>
                    <span v-if="$v.profileSettingsForm.phone_country_code.$dirty && !$v.profileSettingsForm.phone_country_code.numeric" class="text-xs text-red-600">Phone country code should be numeric.</span>
                </div>
                <div class="w-1/3">
                    <label class="block capitalize text-gray-700 text-sm">Phone</label>
                    <input type="text" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.profileSettingsForm.phone.$error}" id="phone" v-model="$v.profileSettingsForm.phone.$model">
                    <span v-if="$v.profileSettingsForm.phone.$dirty && !$v.profileSettingsForm.phone.required" class="text-xs text-red-600">Phone is required.</span>
                    <span v-if="$v.profileSettingsForm.phone.$dirty && !$v.profileSettingsForm.phone.numeric" class="text-xs text-red-600">Phone should be numeric.</span>
                </div>
            </div>
            <div class="mb-6 flex">
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Currency</label>
                    <div class="relative">
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.profileSettingsForm.currency_id.$error}" id="currency_id" v-model="$v.profileSettingsForm.currency_id.$model">
                            <option :value="null" disabled>Select Currency</option>
                            <option v-for="currency in currencies" :key="currency.id" :value="currency.id" :selected="currency.id === profileSettingsForm.currency_id">{{currency.currency}}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <span v-if="$v.profileSettingsForm.currency_id.$dirty && !$v.profileSettingsForm.currency_id.required" class="text-xs text-red-600">Currency is required.</span>
                    <span v-if="$v.profileSettingsForm.currency_id.$dirty && !$v.profileSettingsForm.currency_id.inCurrenciesArray" class="text-xs text-red-600">Currency is invalid.</span>
                </div>
                <div class="w-2/3 mr-6"></div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-12 py-4">Save Changes</button>
            </div>
        </form>
        <hr class="mt-12">
        <p class="text-2xl mb-12 mt-10">Change Password</p>
        <form @submit.prevent="changePassword">
            <div class="mb-6">
                <div class="w-1/3">
                    <label class="block capitalize text-gray-700 text-sm">Current Password</label>
                    <input type="password" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.changePasswordForm.current_password.$error}" id="current_password" v-model="$v.changePasswordForm.current_password.$model">
                    <span v-if="$v.changePasswordForm.current_password.$dirty && !$v.changePasswordForm.current_password.required" class="text-xs text-red-600">Please type your password.</span>
                    <span v-if="$v.changePasswordForm.current_password.$dirty && !$v.changePasswordForm.current_password.minLength" class="text-xs text-red-600">Password should have a minimum of 6 characters.</span>
                    <span v-if="$v.changePasswordForm.current_password.$dirty && !$v.changePasswordForm.current_password.maxLength" class="text-xs text-red-600">Password should not exceed 32 characters.</span>
                </div>
            </div>
            <div class="mb-6">
                <div class="w-1/3">
                    <label class="block capitalize text-gray-700 text-sm">New Password</label>
                    <input type="password" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.changePasswordForm.new_password.$error}" id="new_password" v-model="$v.changePasswordForm.new_password.$model">
                    <span v-if="$v.changePasswordForm.new_password.$dirty && !$v.changePasswordForm.new_password.required" class="text-xs text-red-600">Please type a new password.</span>
                    <span v-if="$v.changePasswordForm.new_password.$dirty && !$v.changePasswordForm.new_password.minLength" class="text-xs text-red-600">Password should have a minimum of 6 characters.</span>
                    <span v-if="$v.changePasswordForm.new_password.$dirty && !$v.changePasswordForm.new_password.maxLength" class="text-xs text-red-600">Password should not exceed 32 characters.</span>
                </div>
            </div>
            <div class="mb-6">
                <div class="w-1/3">
                    <label class="block capitalize text-gray-700 text-sm">Confirm New Password</label>
                    <input type="password" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': $v.changePasswordForm.confirm_new_password.$error}" id="confirm_new_password" v-model="$v.changePasswordForm.confirm_new_password.$model">
                    <span v-if="$v.changePasswordForm.confirm_new_password.$dirty && !$v.changePasswordForm.confirm_new_password.sameAs" class="text-xs text-red-600">Passwords do not match.</span>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-12 py-4">Change Password</button>
            </div>
        </form>
    </div>
</template>

<script>
import Cookies from 'js-cookie'
import Swal from 'sweetalert2'
import {required, numeric, minLength, maxLength, sameAs} from 'vuelidate/lib/validators'
//Custom vuelidate validators
function inCountriesArray(value) {
    return this.countries.some(country => country.id === value)
}
function inStatesArray(value) {
    return this.states.some(state => state.id === value)
}
function inCitiesArray(value) {
    return this.cities.some(city => city.id === value)
}
function inCurrenciesArray(value) {
    return this.currencies.some(currency => currency.id === value)
}

export default {
    data() {
        return {
            profileSettingsForm: {
                firstname: this.$store.state.authUser.firstname,
                lastname: this.$store.state.authUser.lastname,
                address: this.$store.state.authUser.address,
                country: this.$store.state.authUser.country,
                state: this.$store.state.authUser.country,
                city: this.$store.state.authUser.city,
                postcode: this.$store.state.authUser.postcode,
                phone_country_code: this.$store.state.authUser.phone_country_code,
                phone: this.$store.state.authUser.phone,
                currency_id:this.$store.state.authUser.currency_id
            },
            changePasswordForm: {
                current_password: '',
                new_password: '',
                confirm_new_password: ''
            },
            // mock data these should come from API requests
            countries: [
                { id: 1, country: 'Philippines' },
                { id: 2, country: 'USA' }
            ],
            states: [
                { id: 1, state: 'NCR', country_id: 1 },
                { id: 2, state: 'California', country_id: 2 }
            ],
            cities: [
                { id: 1, city: 'Pasig City', state_id: 1, country_id: 1 },
                { id: 2, city: 'Los Angeles', state_id: 2, country_id: 2 },
                { id: 3, city: 'Makati City', state_id: 1, country_id: 1 },
                { id: 4, city: 'San Francisco', state_id: 2, country_id: 2 },
            ],
            currencies: [
                { id: 1, currency: 'CNY' },
                { id: 2, currency: 'USD' }
            ],
            profileFormSettingsError: []
        }
    },
    computed: {
        statesDropdown() {
            return this.states.filter(state => state.country_id === this.profileSettingsForm.country)
        },
        citiesDropdown() {
            return this.cities.filter(city => city.state_id === this.profileSettingsForm.state && city.country_id === this.profileSettingsForm.country)
        }
    },
    head: {
        title() {
            return {
                inner: 'Settings - Profile'
            }
        }
    },
    validations: {
        profileSettingsForm: {
            firstname: { required },
            lastname: { required },
            address: { required },
            country: { required, inCountriesArray },
            state: { required, inStatesArray },
            city: { required, inCitiesArray },
            postcode: { required },
            phone_country_code: { required, numeric },
            phone: { required, numeric },
            currency_id: { required, inCurrenciesArray }
        },
        changePasswordForm: {
            current_password: { required, minLength:minLength(6), maxLength:maxLength(32) },
            new_password: { required, minLength:minLength(6), maxLength:maxLength(32) },
            confirm_new_password: { sameAs:sameAs('new_password') }
        }
    },
    methods: {
        resetStateAndCity() {
            this.profileSettingsForm.state = null
            this.profileSettingsForm.city = null
        },
        saveChanges() {
            if (!this.$v.profileSettingsForm.$invalid) {
                let token = Cookies.get('access_token')
                let data = {
                    firstname: this.profileSettingsForm.firstname,
                    lastname: this.profileSettingsForm.lastname,
                    address: this.profileSettingsForm.address,
                    country: this.profileSettingsForm.country,
                    state: this.profileSettingsForm.state,
                    city: this.profileSettingsForm.city,
                    postcode: this.profileSettingsForm.postcode,
                    phone_country_code: this.profileSettingsForm.phone_country_code,
                    phone: this.profileSettingsForm.phone,
                    currency_id: this.profileSettingsForm.currency_id
                }

                axios.post('/v1/user/settings/profile', data, { headers: { 'Authorization': `Bearer ${token}` } })
                .then(response => {
                    Swal.fire({
                        icon: 'success',
                        text: response.data.message
                    })
                })
                .catch(err => {
                    Object.values(err.response.data.errors).forEach(errorType => {
                        errorType.forEach(error => {
                            this.profileFormSettingsError.push(error)
                        })
                    })
                    Swal.fire({
                        icon: 'error',
                        text: this.profileFormSettingsError.join(', ')
                    })
                })
            } else {
                Object.keys(this.profileSettingsForm).forEach(field => {
                    this.$v.profileSettingsForm[field].$touch()
                })
            }
        },
        changePassword() {
            if (!this.$v.changePasswordForm.$invalid) {
                let token = Cookies.get('access_token')
                let data = {
                    old_password: this.changePasswordForm.current_password,
                    password: this.changePasswordForm.new_password,
                    password_confirmation: this.changePasswordForm.confirm_new_password
                }

                axios.post('/v1/user/settings/change-password', data, { headers: { 'Authorization': `Bearer ${token}` } })
                .then(response => {
                    if (response.data.status_code === 400) {
                        Swal.fire({
                            icon: 'error',
                            text: response.data.message
                        })
                    } else {
                        Swal.fire({
                            icon: 'success',
                            text: response.data.message
                        })
                    }
                })
                .catch(err => {
                    Object.values(err.response.data.errors).forEach(errorType => {
                        errorType.forEach(error => {
                            this.profileFormSettingsError.push(error)
                        })
                    })
                    Swal.fire({
                        icon: 'error',
                        text: this.profileFormSettingsError.join(', ')
                    })
                })
            } else {
                Object.keys(this.changePasswordForm).forEach(field => {
                    this.$v.changePasswordForm[field].$touch()
                })
            }
        }
    }
}
</script>

<style>
    hr {
        border-bottom: 1px solid #aaaaaa;
        height: 0.5px;
    }
</style>
