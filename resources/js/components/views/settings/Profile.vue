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
                    <input type="text" id="firstName" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="$v.profileSettingsForm.firstname.$model">
                    <span v-if="$v.profileSettingsForm.firstname.$dirty && !$v.profileSettingsForm.firstname.required" class="text-xs text-red-600">Please type your first name.</span>
                </div>
                <div class="w-1/2">
                    <label class="block capitalize text-gray-700 text-sm">Last Name</label>
                    <input type="text" id="lastname" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="$v.profileSettingsForm.lastname.$model">
                    <span v-if="$v.profileSettingsForm.lastname.$dirty && !$v.profileSettingsForm.lastname.required" class="text-xs text-red-600">Please type your last name.</span>
                </div>
            </div>
            <div class="mb-6">
                <label class="block capitalize text-gray-700 text-sm">Address</label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" id="address" v-model="$v.profileSettingsForm.address.$model"></textarea>
                <span v-if="$v.profileSettingsForm.address.$dirty && !$v.profileSettingsForm.address.required" class="text-xs text-red-600">Please type your address.</span>
            </div>
            <div class="mb-6 flex">
              <div class="w-1/3 mr-6">
                <label class="block capitalize text-gray-700 text-sm">Country</label>
                <div class="relative">
                    <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" id="country" v-model="$v.profileSettingsForm.country.$model">
                        <option v-for="country in countries" :key="country.id" :value="country.id" :selected="country.id === $store.state.authUser.country">{{country.country}}</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
                <span v-if="$v.profileSettingsForm.country.$dirty && !$v.profileSettingsForm.country.required" class="text-xs text-red-600">Please select a country.</span>
              </div>
              <div class="w-1/3 mr-6">
                <label class="block capitalize text-gray-700 text-sm">State</label>
                <div class="relative">
                    <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" id="state" v-model="$v.profileSettingsForm.state.$model">
                        <option v-for="state in states" :key="state.id" :value="state.id" :selected="state.id === $store.state.authUser.state">{{state.state}}</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
                <span v-if="$v.profileSettingsForm.state.$dirty && !$v.profileSettingsForm.state.required" class="text-xs text-red-600">Please select a state.</span>
              </div>
              <div class="w-1/3">
                <label class="block capitalize text-gray-700 text-sm">City</label>
                <div class="relative">
                    <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" id="city" v-model="$v.profileSettingsForm.city.$model">
                         <option v-for="city in cities" :key="city.id" :value="city.id" :selected="city.id === $store.state.authUser.city">{{city.city}}</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
              </div>
            </div>
            <div class="mb-6 flex">
              <div class="w-1/3 mr-6">
                <label class="block capitalize text-gray-700 text-sm">Post Code</label>
                <input type="text" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" id="postcode" v-model="$v.profileSettingsForm.postcode.$model">
              </div>
              <div class="w-1/3 mr-6">
                <label class="block capitalize text-gray-700 text-sm">Phone Country Code</label>
                <input type="text" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" id="phone_country_code" v-model="$v.profileSettingsForm.phone_country_code.$model">
              </div>
              <div class="w-1/3">
                <label class="block capitalize text-gray-700 text-sm">Phone</label>
                <input type="text" class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" id="phone" v-model="$v.profileSettingsForm.phone.$model">
              </div>
            </div>
            <div class="mb-6 flex">
                <div class="w-1/3 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Currency</label>
                    <div class="relative">
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" id="currency_id" v-model="$v.profileSettingsForm.currency_id.$model">
                            <option v-for="currency in currencies" :key="currency.id" :value="currency.id" :selected="currency.id === $store.state.authUser.currency_id">{{currency.currency}}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
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
import {required, numeric, minLength, maxLength, sameAs} from 'vuelidate/lib/validators'
export default {
    data() {
        return {
            profileSettingsForm:{
                firstname: this.$store.state.authUser.firstname,
                lastname: this.$store.state.authUser.lastname,
                address: this.$store.state.authUser.address,
                country: this.$store.state.authUser.country,
                state: this.$store.state.authUser.country,
                city: this.$store.state.authUser.city,
                postcode: this.$store.state.authUser.postcode,
                phone_country_code: this.$store.state.authUser.phone_country_code,
                phone: this.$store.state.authUser.phone,
                odds_type:this.$store.state.authUser.odds_type,
                currency_id:this.$store.state.authUser.currency_id
            },
            changePasswordForm:{
                current_password:'',
                new_password:'',
                confirm_new_password:''
            },
            // mock data these should come from an API requests
            countries:[
                { id:1, country:'Philippines' },
                { id:2, country: 'USA' }
            ],
            states:[
                { id:1, state:'NCR' },
                { id:2, state:'California' }
            ],
            cities:[
                { id:1, city:'Pasig City' },
                { id:2, city:'Los Angeles' }
            ],
            currencies:[
                { id:1, currency:'CNY' },
                { id:2, currency:'USD' }
            ],

        }
    },
    head:{
        title() {
            return {
                inner: 'Settings - Profile'
            }
        }
    },
    mounted() {
        // console.log(this.countries.some(country => country.id === 1))
    },
    validations: {
        profileSettingsForm:{
            firstname: {required},
            lastname: {required},
            address: {required},
            country: {required},
            state: {required},
            city: {required},
            postcode: {required},
            phone_country_code: {required, numeric},
            phone: {required, numeric},
            currency_id:{required}
        },
        changePasswordForm: {
            current_password:{required, minLength:minLength(6), maxLength:maxLength(32)},
            new_password:{required, minLength:minLength(6), maxLength:maxLength(32)},
            confirm_new_password:{sameAs:sameAs('new_password')}
        }
    },
    methods:{
        saveChanges() {

        },
        changePassword() {
          if(!this.$v.changePasswordForm.$invalid) {
              /* API request here */
          } else {
            this.$v.changePasswordForm.current_password.$touch()
            this.$v.changePasswordForm.new_password.$touch()
            this.$v.changePasswordForm.confirm_new_password.$touch()
          }
        }
    }
}
</script>

<style>
  hr {
    border-bottom:1px solid #aaaaaa;
    height:0.5px;
  }
</style>