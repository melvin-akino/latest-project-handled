<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="mb-6 flex">
                <div class="w-1/2 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Display Name</label>
                    <input type="text" id="displayname" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :value="profileSettingsForm.name" disabled>
                </div>
                <div class="w-1/2 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Email</label>
                    <input type="text" id="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :value="profileSettingsForm.email" disabled>
                </div>
            </div>
            <div class="mb-6 flex">
                <div class="w-1/2 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">First Name</label>
                    <input type="text" id="firstname" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('firstname')}" v-model.trim="profileSettingsForm.firstname">
                    <span v-if="profileSettingsFormError.hasOwnProperty('firstname')" class="text-xs text-red-600">{{profileSettingsFormError.firstname[0]}}</span>
                </div>
                <div class="w-1/2 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Last Name</label>
                    <input type="text" id="lastname" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('lastname')}" v-model.trim="profileSettingsForm.lastname">
                    <span v-if="profileSettingsFormError.hasOwnProperty('lastname')" class="text-xs text-red-600">{{profileSettingsFormError.lastname[0]}}</span>
                </div>
            </div>
            <div class="mb-6">
                <label class="block capitalize text-gray-700 text-sm">Address</label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('address')}" id="address" v-model.trim="profileSettingsForm.address"></textarea>
                <span v-if="profileSettingsFormError.hasOwnProperty('address')" class="text-xs text-red-600">{{profileSettingsFormError.address[0]}}</span>
            </div>
            <div class="mb-6 flex">
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Country</label>
                    <div class="relative">
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('country_id')}" id="country" v-model="profileSettingsForm.country_id">
                            <option v-for="country in countries" :key="country.id" :value="country.id" :selected="country.id === profileSettingsForm.country_id">{{country.country_name}}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <span v-if="profileSettingsFormError.hasOwnProperty('country_id')" class="text-xs text-red-600">{{profileSettingsFormError.country_id[0]}}</span>
                </div>
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">State</label>
                    <input type="text" id="state" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('state')}" v-model.trim="profileSettingsForm.state
                    ">
                    <span v-if="profileSettingsFormError.hasOwnProperty('state')" class="text-xs text-red-600">{{profileSettingsFormError.state[0]}}</span>
                </div>
                <div class="w-1/3">
                    <label class="block capitalize text-gray-700 text-sm">City</label>
                    <input type="text" id="city" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('city')}" v-model.trim="profileSettingsForm.city">
                    <span v-if="profileSettingsFormError.hasOwnProperty('city')" class="text-xs text-red-600">{{profileSettingsFormError.city[0]}}</span>
                </div>
            </div>
            <div class="mb-6 flex">
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Post Code</label>
                    <input type="text" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('postcode')}" id="postcode" v-model.trim="profileSettingsForm.postcode">
                    <span v-if="profileSettingsFormError.hasOwnProperty('postcode')" class="text-xs text-red-600">{{profileSettingsFormError.postcode[0]}}</span>
                </div>
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Phone</label>
                    <input type="text" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('phone')}" id="phone" v-model.trim="profileSettingsForm.phone">
                    <span v-if="profileSettingsFormError.hasOwnProperty('phone')" class="text-xs text-red-600">{{profileSettingsFormError.phone[0]}}</span>
                </div>
                <div class="w-1/3">
                    <label class="block capitalize text-gray-700 text-sm">Currency</label>
                    <input type="text" id="currency" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :value="userCurrency" disabled>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-4 py-2">Save Changes</button>
            </div>
        </form>
        <hr class="mt-12">
        <p class="text-2xl mb-12 mt-10">Change Password</p>
        <form @submit.prevent="changePassword">
            <div class="mb-6">
                <div class="w-1/3">
                    <label class="block capitalize text-gray-700 text-sm">Current Password</label>
                    <input type="password" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('old_password')}" id="old_password" v-model="changePasswordForm.old_password">
                    <span v-if="profileSettingsFormError.hasOwnProperty('old_password')" class="text-xs text-red-600">{{profileSettingsFormError.old_password[0]}}</span>
                </div>
            </div>
            <div class="mb-6">
                <div class="w-1/3">
                    <label class="block capitalize text-gray-700 text-sm">New Password</label>
                    <input type="password" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('password')}" id="password" v-model="changePasswordForm.password">
                    <span v-if="profileSettingsFormError.hasOwnProperty('password')" class="text-xs text-red-600">{{profileSettingsFormError.password[0]}}</span>
                </div>
            </div>
            <div class="mb-6">
                <div class="w-1/3">
                    <label class="block capitalize text-gray-700 text-sm">Confirm New Password</label>
                    <input type="password" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('password_confirmation')}" id="password_confirmation" v-model="changePasswordForm.password_confirmation">
                    <span v-if="profileSettingsFormError.hasOwnProperty('password_confirmation')" class="text-xs text-red-600">{{profileSettingsFormError.password_confirmation[0]}}</span>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-500 text-white text-sm uppercase px-4 py-2" :disabled="isChangingPassword">
                    <span v-if="isChangingPassword">Changing Password...</span>
                    <span v-else>Change Password</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import _ from 'lodash';
import Cookies from 'js-cookie'
import Swal from 'sweetalert2'

export default {
    data() {
        return {
            profileSettingsForm: {},
            changePasswordForm: {
                old_password: '',
                password: '',
                password_confirmation: ''
            },
            countries: [],
            currencies: [
                { id: 1, currency: 'CNY' },
                // { id: 2, currency: 'USD' }
            ],
            profileSettingsFormError: {},
            isChangingPassword: false
        }
    },
    computed: {
        userCurrency() {
            if(!_.isEmpty(this.profileSettingsForm)) {
                let currency = this.currencies.filter(currency => currency.id == this.profileSettingsForm.currency_id)[0]
                return currency.currency
            }
        }
    },
    head: {
        title() {
            return {
                inner: 'Settings - Profile'
            }
        }
    },
    mounted() {
        this.getUser()
        this.countries = this.$store.state.settings.settingsData.country
    },
    methods: {
        getUser() {
            let token = Cookies.get('mltoken')

            axios.get('v1/user', { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                this.profileSettingsForm = response.data.data
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        },
        saveChanges() {
            let token = Cookies.get('mltoken')
            let data = {
                firstname: this.profileSettingsForm.firstname,
                lastname: this.profileSettingsForm.lastname,
                address: this.profileSettingsForm.address,
                country_id: this.profileSettingsForm.country_id,
                state: this.profileSettingsForm.state,
                city: this.profileSettingsForm.city,
                postcode: `${this.profileSettingsForm.postcode}`,
                phone: this.profileSettingsForm.phone
            }

            axios.post('/v1/user/settings/profile', data, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                this.profileSettingsFormError = {}
                Swal.fire({
                    icon: 'success',
                    text: response.data.message
                })
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
                this.profileSettingsFormError = err.response.data.errors
                Swal.fire({
                    icon: 'error',
                    text: err.response.data.message
                })
            })
        },
        changePassword() {
            let token = Cookies.get('mltoken')
            let data = {
                old_password: this.changePasswordForm.old_password,
                password: this.changePasswordForm.password,
                password_confirmation: this.changePasswordForm.password_confirmation
            }

            this.isChangingPassword = true

            axios.post('/v1/user/settings/change-password', data, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                this.isChangingPassword = false
                this.profileSettingsFormError = {}
                Swal.fire({
                    icon: 'success',
                    text: response.data.message
                })
                .then(() => {
                    this.changePasswordForm.old_password = ''
                    this.changePasswordForm.password = ''
                    this.changePasswordForm.password_confirmation = ''
                })
            })
            .catch(err => {
                this.isChangingPassword = false
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
                if(err.response.status === 422) {
                    this.profileSettingsFormError = err.response.data.errors
                }
                Swal.fire({
                    icon: 'error',
                    text: err.response.data.message
                })
            })
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
