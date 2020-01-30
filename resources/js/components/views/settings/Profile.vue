<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="mb-6 flex">
                <div class="w-1/2 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">First Name</label>
                    <input type="text" id="firstname" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('firstname')}" v-model="profileSettingsForm.firstname">
                    <span v-if="profileSettingsFormError.hasOwnProperty('firstname')" class="text-xs text-red-600">{{profileSettingsFormError.firstname[0]}}</span>
                </div>
                <div class="w-1/2 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Last Name</label>
                    <input type="text" id="lastname" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('lastname')}" v-model="profileSettingsForm.lastname">
                    <span v-if="profileSettingsFormError.hasOwnProperty('lastname')" class="text-xs text-red-600">{{profileSettingsFormError.lastname[0]}}</span>
                </div>
            </div>
            <div class="mb-6">
                <label class="block capitalize text-gray-700 text-sm">Address</label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('address')}" id="address" v-model="profileSettingsForm.address"></textarea>
                <span v-if="profileSettingsFormError.hasOwnProperty('address')" class="text-xs text-red-600">{{profileSettingsFormError.address[0]}}</span>
            </div>
            <div class="mb-6 flex">
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Country</label>
                    <div class="relative">
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('country_id')}" id="country" v-model="profileSettingsForm.country_id" @change="selectCountry">
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
                    <input type="text" id="state" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('state')}" v-model="profileSettingsForm.state
                    ">
                    <span v-if="profileSettingsFormError.hasOwnProperty('state')" class="text-xs text-red-600">{{profileSettingsFormError.state[0]}}</span>
                </div>
                <div class="w-1/3">
                    <label class="block capitalize text-gray-700 text-sm">City</label>
                    <input type="text" id="city" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('city')}" v-model="profileSettingsForm.city">
                    <span v-if="profileSettingsFormError.hasOwnProperty('city')" class="text-xs text-red-600">{{profileSettingsFormError.city[0]}}</span>
                </div>
            </div>
            <div class="mb-6 flex">
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Post Code</label>
                    <input type="text" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('postcode')}" id="postcode" v-model="profileSettingsForm.postcode">
                    <span v-if="profileSettingsFormError.hasOwnProperty('postcode')" class="text-xs text-red-600">{{profileSettingsFormError.postcode[0]}}</span>
                </div>
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Phone Country Code</label>
                    <div class="relative">
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('phone_country_code')}" id="phone_country_code" v-model="profileSettingsForm.phone_country_code">
                            <option :value="null" disabled>Select Phone Country Code</option>
                            <option v-for="phonecode in phonecodes" :key="phonecode.id">{{phonecode.phonecode}}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <span v-if="profileSettingsFormError.hasOwnProperty('phone_country_code')" class="text-xs text-red-600">{{profileSettingsFormError.phone_country_code[0]}}</span>
                </div>
                <div class="w-1/3">
                    <label class="block capitalize text-gray-700 text-sm">Phone</label>
                    <input type="text" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('phone')}" id="phone" v-model="profileSettingsForm.phone">
                    <span v-if="profileSettingsFormError.hasOwnProperty('phone')" class="text-xs text-red-600">{{profileSettingsFormError.phone[0]}}</span>
                </div>
            </div>
            <div class="mb-6 flex">
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Currency</label>
                    <div class="relative">
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" :class="{'border-red-600': profileSettingsFormError.hasOwnProperty('currency_id')}" id="currency_id" v-model="profileSettingsForm.currency_id">
                            <option v-for="currency in currencies" :key="currency.id" :value="currency.id" :selected="currency.id === profileSettingsForm.currency_id">{{currency.currency}}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <span v-if="profileSettingsFormError.hasOwnProperty('currency_id')" class="text-xs text-red-600">{{profileSettingsFormError.currency_id[0]}}</span>
                </div>
                <div class="w-2/3 mr-6"></div>
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
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-4 py-2">Change Password</button>
            </div>
        </form>
    </div>
</template>

<script>
import Cookies from 'js-cookie'
import Swal from 'sweetalert2'

export default {
    data() {
        return {
            profileSettingsForm: {
                firstname: this.$store.state.auth.authUser.firstname,
                lastname: this.$store.state.auth.authUser.lastname,
                address: this.$store.state.auth.authUser.address,
                country_id: this.$store.state.auth.authUser.country_id,
                state: this.$store.state.auth.authUser.state,
                city: this.$store.state.auth.authUser.city,
                postcode: this.$store.state.auth.authUser.postcode,
                phone_country_code: this.$store.state.auth.authUser.phone_country_code,
                phone: this.$store.state.auth.authUser.phone,
                currency_id:this.$store.state.auth.authUser.currency_id
            },
            changePasswordForm: {
                old_password: '',
                password: '',
                password_confirmation: ''
            },
            countries: [],
            phonecodes: [],
            currencies: [
                { id: 1, currency: 'CNY' },
                { id: 2, currency: 'USD' }
            ],
            profileSettingsFormError: {}
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
        this.countries = this.$store.state.settings.settingsData.country
        this.phonecodes = this.$store.state.settings.settingsData.country.filter(country => country.phonecode.length != 0).map(country => {
            return { id: country.id, phonecode: country.phonecode }
        })
    },
    methods: {
        selectCountry() {
            this.profileSettingsForm.phone_country_code = this.countries.filter(country => country.id === this.profileSettingsForm.country_id).map(country => country.phonecode).join()
        },
        saveChanges() {
            let token = Cookies.get('access_token')
            let data = {
                firstname: this.profileSettingsForm.firstname,
                lastname: this.profileSettingsForm.lastname,
                address: this.profileSettingsForm.address,
                country_id: this.profileSettingsForm.country_id,
                state: this.profileSettingsForm.state,
                city: this.profileSettingsForm.city,
                postcode: `${this.profileSettingsForm.postcode}`,
                phone_country_code: this.profileSettingsForm.phone_country_code,
                phone: this.profileSettingsForm.phone,
                currency_id: this.profileSettingsForm.currency_id
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
                this.profileSettingsFormError = err.response.data.errors
                Swal.fire({
                    icon: 'error',
                    text: err.response.data.message
                })
            })
        },
        changePassword() {
            let token = Cookies.get('access_token')
            let data = {
                old_password: this.changePasswordForm.old_password,
                password: this.changePasswordForm.password,
                password_confirmation: this.changePasswordForm.password_confirmation
            }

            axios.post('/v1/user/settings/change-password', data, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                this.profileSettingsFormError = {}
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
                    .then(() => {
                        this.changePasswordForm.old_password = ''
                        this.changePasswordForm.password = ''
                        this.changePasswordForm.password_confirmation = ''
                    })
                }
            })
            .catch(err => {
                this.profileSettingsFormError = err.response.data.errors
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
