<template>
    <div class="register">
        <div class="mx-auto sm:bg-white sm:shadow-lg md:w-160 sm:w-120 xs:w-100 w-full h-auto sm:px-12 px-4 sm:pt-8 pt-6 pb-4 mt-6">
            <form method="POST" v-if="!isRegisterSuccessful">
                <div class="step1" v-if="step === 1">
                    <h3 class="block text-gray-700 text-lg mb-2 font-bold uppercase">Register - Step 1 of 3</h3>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="name">
                        Username
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.name.$error}" id="name" type="text" placeholder="e.g. iampogi" v-model="$v.registerForm.step1.name.$model">
                        <span v-if="$v.registerForm.step1.name.$dirty && !$v.registerForm.step1.name.required" class="text-red-600 text-sm">Please type a username.</span>
                        <span v-if="$v.registerForm.step1.name.$dirty && !$v.registerForm.step1.name.alphaNum" class="text-red-600 text-sm">Username should only contain alphanumeric characters.</span>
                        <span v-if="$v.registerForm.step1.name.$dirty && !$v.registerForm.step1.name.minLength" class="text-red-600 text-sm">Username must have a minimum of 6 characters.</span>
                        <span v-if="$v.registerForm.step1.name.$dirty && !$v.registerForm.step1.name.maxLength" class="text-red-600 text-sm">Username must have a maximum of 32 characters.</span>
                    </div>
                    <div class="flex justify-evenly">
                        <div class="mb-2 mr-3 w-full">
                            <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="firstname">
                            First Name
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.firstname.$error}" id="firstname" type="text" placeholder="e.g. Tony, Steve" v-model="$v.registerForm.step1.firstname.$model">
                            <span v-if="$v.registerForm.step1.firstname.$dirty && !$v.registerForm.step1.firstname.required" class="text-red-600 text-sm">Please type your first name.</span>
                        </div>
                        <div class="mb-2 w-full">
                            <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="lastname">
                            Last Name
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.lastname.$error}" id="lastname" type="text" placeholder="e.g. Stark, Rogers" v-model="$v.registerForm.step1.lastname.$model">
                            <span v-if="$v.registerForm.step1.lastname.$dirty && !$v.registerForm.step1.lastname.required" class="text-red-600 text-sm">Please type your last name.</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="email">
                        Email
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.email.$error}" id="email" type="text" placeholder="e.g. iampogi@pogi.com" v-model="$v.registerForm.step1.email.$model">
                        <span v-if="$v.registerForm.step1.email.$dirty && !$v.registerForm.step1.email.required" class="text-red-600 text-sm">Please type an email.</span>
                        <span v-if="$v.registerForm.step1.email.$dirty && !$v.registerForm.step1.email.email" class="text-red-600 text-sm">Please type a valid email.</span>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="password">
                        Password
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.password.$error}" id="password" type="password" placeholder="Password" v-model="$v.registerForm.step1.password.$model">
                        <span v-if="$v.registerForm.step1.password.$dirty && !$v.registerForm.step1.password.required" class="text-red-600 text-sm">Please type a password.</span>
                        <span v-if="$v.registerForm.step1.password.$dirty && !$v.registerForm.step1.password.minLength" class="text-red-600 text-sm">Password must have a minimum of 6 characters.</span>
                        <span v-if="$v.registerForm.step1.password.$dirty && !$v.registerForm.step1.password.maxLength" class="text-red-600 text-sm">Password must have a maximum of 32 characters.</span>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="password_confirmation">
                        Confirm Password
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.password_confirmation.$error}" id="password_confirmation" type="password" placeholder="Confirm Password" v-model="$v.registerForm.step1.password_confirmation.$model">
                        <span v-if="$v.registerForm.step1.password_confirmation.$dirty && !$v.registerForm.step1.password_confirmation.sameAs" class="text-red-600 text-sm">Password does not match.</span>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="password_confirmation">
                        Birthdate (optional)
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" id="birthdate" type="date" placeholder="Birthdate" v-model="registerForm.step1.birthdate">
                    </div>
                </div>

                <div class="step2" v-if="step === 2">
                    <h3 class="block text-gray-700 text-lg mb-2 font-bold uppercase">Register - Step 2 of 3</h3>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="address">
                        Address
                        </label>
                        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.address.$error}" id="address" v-model="$v.registerForm.step2.address.$model"></textarea>
                        <span v-if="$v.registerForm.step2.address.$dirty && !$v.registerForm.step2.address.required" class="text-red-600 text-sm">Address is required.</span>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="country">
                        Country
                        </label>
                        <div class="relative">
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.country.$error}" id="country" v-model="$v.registerForm.step2.country.$model">
                                <option :value="null" disabled>Select Country</option>
                                <option value="1">Philippines</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                        <span v-if="$v.registerForm.step2.country.$dirty && !$v.registerForm.step2.country.required" class="text-red-600 text-sm">Country is required.</span>
                    </div>
                    <div class="flex justify-evenly">
                        <div class="mb-2 mr-3 w-full">
                            <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="state">
                            State
                            </label>
                            <div class="relative">
                                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.state.$error}" id="state" v-model="$v.registerForm.step2.state.$model">
                                    <option :value="null" disabled>Select State</option>
                                    <option value="1">NCR</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                            <span v-if="$v.registerForm.step2.state.$dirty && !$v.registerForm.step2.state.required" class="text-red-600 text-sm">State is required.</span>
                        </div>
                        <div class="mb-2 w-full">
                            <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="city">
                            City
                            </label>
                            <div class="relative">
                                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.city.$error}" id="city" v-model="$v.registerForm.step2.city.$model">
                                    <option :value="null" disabled>Select City</option>
                                    <option value="1">Pasig City</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                            <span v-if="$v.registerForm.step2.city.$dirty && !$v.registerForm.step2.city.required" class="text-red-600 text-sm">City is required.</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="postcode">
                        Post Code
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.postcode.$error}" id="postcode" type="text" placeholder="Post Code" v-model="$v.registerForm.step2.postcode.$model">
                        <span v-if="$v.registerForm.step2.postcode.$dirty && !$v.registerForm.step2.postcode.required" class="text-red-600 text-sm">Postcode is required.</span>
                    </div>
                    <div class="flex justify-evenly">
                        <div class="mb-2 mr-3 w-full">
                            <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="phone_country_code">
                            Phone Country Code
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.phone_country_code.$error}" id="phone_country_code" type="number" placeholder="Phone Country Code" v-model="$v.registerForm.step2.phone_country_code.$model">
                            <span v-if="$v.registerForm.step2.phone_country_code.$dirty && !$v.registerForm.step2.phone_country_code.required" class="text-red-600 text-sm">Phone country code is required.</span>
                            <span v-if="$v.registerForm.step2.phone_country_code.$dirty && !$v.registerForm.step2.phone_country_code.numeric" class="text-red-600 text-sm">Phone country code should be numeric.</span>
                        </div>
                        <div class="mb-2 w-full">
                            <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="phone">
                            Phone
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.phone.$error}" id="phone" type="number" placeholder="Phone" v-model="$v.registerForm.step2.phone.$model">
                            <span v-if="$v.registerForm.step2.phone.$dirty && !$v.registerForm.step2.phone.required" class="text-red-600 text-sm">Phone is required.</span>
                            <span v-if="$v.registerForm.step2.phone.$dirty && !$v.registerForm.step2.phone.numeric" class="text-red-600 text-sm">Phone should be numeric.</span>
                        </div>
                    </div>
                </div>

                <div class="step3" v-if="step === 3">
                    <h3 class="block text-gray-700 text-lg mb-2 font-bold uppercase">Register - Step 3 of 3</h3>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="odds_type">
                            Odds Type
                        </label>
                        <div class="relative">
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step3.odds_type.$error}" id="odds_type" v-model="$v.registerForm.step3.odds_type.$model">
                                <option :value="null" disabled>Select Odds Type</option>
                                <option value="1">Asian</option>
                                <option value="2">European</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                        <span v-if="$v.registerForm.step3.odds_type.$dirty && !$v.registerForm.step3.odds_type.required" class="text-red-600 text-sm">Odds type is required.</span>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="currency_id">
                        Currency
                        </label>
                        <div class="relative">
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step3.currency_id.$error}" id="currency_id" v-model="$v.registerForm.step3.currency_id.$model">
                                <option :value="null" disabled>Select Currency</option>
                                <option value="1">CNY</option>
                                <option value="2">USD</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                        <span v-if="$v.registerForm.step3.currency_id.$dirty && !$v.registerForm.step3.currency_id.required" class="text-red-600 text-sm">Currency is required.</span>
                    </div>
                </div>

                <div class="flex flex-col">
                    <p class="text-sm text-red-600" v-for="registerError in registerErrors" :key="registerError">{{registerError}}</p>
                </div>

                <div class="mb-2 flex justify-end mt-3">
                    <button type="button" v-if="step != 1" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2 hover:bg-orange-500 focus:outline-none"  @click.prevent="prevStep">Previous</button>
                    <button type="button" v-if="step != totalSteps" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2  ml-2 hover:bg-orange-500 focus:outline-none" @click.prevent="nextStep">Next</button>
                    <button type="submit" v-if="step === totalSteps" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2 ml-2 hover:bg-orange-500 focus:outline-none" @click.prevent="register">Create Account</button>
                </div>
            </form>

            <div class="h-20 w-full mr-12 text-gray-700 font-bold mb-2 uppercase flex flex-col justify-center items-center rounded-lg" v-if="isRegisterSuccessful">
                <div class="text-green-400 text-2xl">{{successfulRegisterMessage}}!</div>
                <div><router-link to="/login" class="underline">Login Here</router-link></div>
            </div>
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
export default {
  name:'Register',
  data() {
    return {
      step: 1,
      totalSteps:3,
      registerForm: {
        step1: {
          name: '',
          firstname:'',
          lastname:'',
          email: '',
          password: '',
          password_confirmation: '',
          birthdate:''
        },
        step2: {
          address:'',
          country: null,
          state: null,
          city: null,
          postcode:'',
          phone: '',
          phone_country_code: null
        },
        step3: {
          odds_type: null,
          currency_id: null
        }
      },
      successfulRegisterMessage:'',
      isRegisterSuccessful: false,
      registerErrors: []
    }
  },
  head:{
    title() {
        return {
            inner: 'Register'
        }
    }
  },
  validations: {
    registerForm: {
      step1: {
        name: {required, minLength:minLength(6), maxLength:maxLength(32), alphaNum},
        firstname:{required},
        lastname:{required},
        email: { required, email },
        password: {required, minLength: minLength(6), maxLength:maxLength(32)},
        password_confirmation: { sameAs: sameAs('password') }
      },
      step2: {
        address: {required},
        country: {required},
        state: {required},
        city: {required},
        postcode: {required},
        phone: {required, numeric},
        phone_country_code: {required, numeric}
      },
      step3: {
        odds_type: {required},
        currency_id: {required}
      }
    }
  },
  computed:{
    checkIfCurrentStepIsInvalid() {
      return this.$v.registerForm[`step${this.step}`].$invalid
    }
  },
  methods: {
    triggerValidationErrors() {
        if(this.$v.registerForm.step1.$invalid) {
          this.$v.registerForm.step1.name.$touch()
          this.$v.registerForm.step1.firstname.$touch()
          this.$v.registerForm.step1.lastname.$touch()
          this.$v.registerForm.step1.email.$touch()
          this.$v.registerForm.step1.password.$touch()
          this.$v.registerForm.step1.password_confirmation.$touch()
        } else if(this.$v.registerForm.step2.$invalid) {
          this.$v.registerForm.step2.address.$touch()
          this.$v.registerForm.step2.country.$touch()
          this.$v.registerForm.step2.state.$touch()
          this.$v.registerForm.step2.city.$touch()
          this.$v.registerForm.step2.postcode.$touch()
          this.$v.registerForm.step2.phone_country_code.$touch()
          this.$v.registerForm.step2.phone.$touch()
        } else if(this.$v.registerForm.step3.$invalid){
            this.$v.registerForm.step3.odds_type.$touch()
            this.$v.registerForm.step3.currency_id.$touch()
        }
    },
    prevStep() {
      this.step--
      this.registerErrors = []
    },
    nextStep() {
      if(!this.checkIfCurrentStepIsInvalid) {
        this.step++
      } else {
        this.triggerValidationErrors()
      }
      this.registerErrors = []
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
          country: this.registerForm.step2.country,
          state: this.registerForm.step2.state,
          city: this.registerForm.step2.city,
          postcode: this.registerForm.step2.postcode,
          phone_country_code: this.registerForm.step2.phone_country_code,
          phone: this.registerForm.step2.phone,
          odds_type: this.registerForm.step3.odds_type,
          currency_id: this.registerForm.step3.currency_id,
        }

        axios.post('/v1/auth/register', data)
        .then((response) => {
          this.isRegisterSuccessful = true
          this.successfulRegisterMessage = response.data.message
        })
        .catch(err => {
            Object.values(err.response.data.errors).forEach(errorType => {
                errorType.forEach(error => {
                    this.registerErrors.push(error)
                })
            })
        })
      } else {
        this.triggerValidationErrors()
      }
    }
  }
}
</script>
