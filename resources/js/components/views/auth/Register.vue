<template>
    <div class="register">
        <div class="mx-auto sm:bg-white sm:shadow-lg md:w-160 sm:w-120 xs:w-100 w-full h-auto sm:px-12 px-4 sm:pt-8 pt-6 pb-4 mt-6">
            <form method="POST" v-if="!isRegisterSuccessful">
                <div class="step1" v-if="step === 1">
                    <h3 class="block text-gray-700 text-lg mb-2 font-bold uppercase">Register - Account Information</h3>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="name">
                        Name
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.name.$error}" id="name" type="text" placeholder="e.g. Tony Stark, Steve Rogers" v-model="$v.registerForm.step1.name.$model">
                        <span v-if="$v.registerForm.step1.name.$dirty && !$v.registerForm.step1.name.required" class="text-red-600 text-sm">Please type your name.</span>
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
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="password_confirmation">
                        Confirm Password
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step1.password_confirmation.$error}" id="password_confirmation" type="password" placeholder="Confirm Password" v-model="$v.registerForm.step1.password_confirmation.$model">
                        <span v-if="$v.registerForm.step1.password_confirmation.$dirty && !$v.registerForm.step1.password_confirmation.sameAs" class="text-red-600 text-sm">Password does not match.</span>
                    </div>
                    <!-- <div class="mb-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="password_confirmation">
                        Birthdate (optional)
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" id="birthdate" type="date" placeholder="Birthdate" v-model="registerForm.step1.birthdate">
                    </div> -->
                </div>

                <div class="step2" v-if="step === 2">
                    <h3 class="block text-gray-700 text-lg mb-2 font-bold uppercase">Register - Contact Information (Part 1)</h3>
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
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.country.$error}" id="country" v-model="$v.registerForm.step2.country.$model">
                        <option value="1" selected>Philippines</option>
                        </select>
                        <span v-if="$v.registerForm.step2.country.$dirty && !$v.registerForm.step2.country.required" class="text-red-600 text-sm">Country is required.</span>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="state">
                        State
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.state.$error}" id="state" v-model="$v.registerForm.step2.state.$model">
                        <option value="1" selected>NCR</option>
                        </select>
                        <span v-if="$v.registerForm.step2.state.$dirty && !$v.registerForm.step2.state.required" class="text-red-600 text-sm">State is required.</span>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="city">
                        City
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step2.city.$error}" id="city" v-model="$v.registerForm.step2.city.$model">
                        <option value="1" selected>Pasig City</option>
                        </select>
                        <span v-if="$v.registerForm.step2.city.$dirty && !$v.registerForm.step2.city.required" class="text-red-600 text-sm">City is required.</span>
                    </div>
                </div>

                <div class="step3" v-if="step === 3">
                    <h3 class="block text-gray-700 text-lg mb-2 font-bold uppercase">Register - Contact Information (Part 2)</h3>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="postcode">
                        Post Code
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step3.postcode.$error}" id="postcode" type="number" placeholder="Post Code" v-model="$v.registerForm.step3.postcode.$model">
                        <span v-if="$v.registerForm.step3.postcode.$dirty && !$v.registerForm.step3.postcode.required" class="text-red-600 text-sm">Postcode is required.</span>
                        <span v-if="$v.registerForm.step3.postcode.$dirty && !$v.registerForm.step3.postcode.numeric" class="text-red-600 text-sm">Postcode should be numeric.</span>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="phone_country_code">
                        Phone Country Code
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step3.phone_country_code.$error}" id="phone_country_code" type="number" placeholder="Phone Country Code" v-model="$v.registerForm.step3.phone_country_code.$model">
                        <span v-if="$v.registerForm.step3.phone_country_code.$dirty && !$v.registerForm.step3.phone_country_code.required" class="text-red-600 text-sm">Phone country code is required.</span>
                        <span v-if="$v.registerForm.step3.phone_country_code.$dirty && !$v.registerForm.step3.phone_country_code.numeric" class="text-red-600 text-sm">Phone country code should be numeric.</span>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2 uppercase" for="phone">
                        Phone
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step3.phone.$error}" id="phone" type="number" placeholder="Phone" v-model="$v.registerForm.step3.phone.$model">
                        <span v-if="$v.registerForm.step3.phone.$dirty && !$v.registerForm.step3.phone.required" class="text-red-600 text-sm">Phone is required.</span>
                        <span v-if="$v.registerForm.step3.phone.$dirty && !$v.registerForm.step3.phone.numeric" class="text-red-600 text-sm">Phone should be numeric.</span>
                    </div>
                </div>

                <div class="step4" v-if="step === 4">
                    <h3 class="block text-gray-700 text-lg mb-2 font-bold uppercase">Register - Bet Settings</h3>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="odds_type">
                        Odds Type
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step4.odds_type.$error}" id="odds_type" v-model="$v.registerForm.step4.odds_type.$model">
                        <option value="1" selected>Test</option>
                        </select>
                        <span v-if="$v.registerForm.step4.odds_type.$dirty && !$v.registerForm.step4.odds_type.required" class="text-red-600 text-sm">Odds type is required.</span>
                    </div>
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase" for="currency_id">
                        Currency
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" :class="{'border-red-600': $v.registerForm.step4.currency_id.$error}" id="currency_id" v-model="$v.registerForm.step4.currency_id.$model">
                        <option value="1" selected>CNY</option>
                        <option value="2" selected>USD</option>
                        </select>
                        <span v-if="$v.registerForm.step4.currency_id.$dirty && !$v.registerForm.step4.currency_id.required" class="text-red-600 text-sm">Currency is required.</span>
                    </div>
                </div>

                <div class="mb-2 flex justify-end">
                    <button type="button" v-if="step != 1" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2 hover:bg-orange-500 focus:outline-none"  @click.prevent="prevStep">Previous</button>
                    <button type="button" v-if="step != totalSteps" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2  ml-5 hover:bg-orange-500 focus:outline-none" @click.prevent="nextStep">Next</button>
                    <button type="submit" v-if="step === totalSteps" class="bg-orange-400 text-white rounded-full font-bold sm:text-sm text-xs uppercase px-12 sm:py-5 py-2 ml-5 hover:bg-orange-500 focus:outline-none" @click.prevent="register">Create Account</button>
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
import { required, minLength, sameAs, email, numeric } from 'vuelidate/lib/validators'
export default {
  name:'Register',
  data() {
    return {
      step: 1,
      totalSteps:4,
      registerForm: {
        step1: {
          name: '',
          email: '',
          password: '',
          password_confirmation: '',
          birthdate:''
        },
        step2: {
          address:'',
          country: null,
          state: null,
          city: null
        },
        step3: {
          postcode:null,
          phone: '',
          phone_country_code: null
        },
        step4: {
          odds_type: null,
          currency_id: null
        }
      },
      successfulRegisterMessage:'',
      isRegisterSuccessful: false,
    }
  },
  created() {
      document.title = 'Register - Multiline'
  },
  validations: {
    registerForm: {
      step1: {
        name: { required },
        email: { required, email },
        password: { required, minLength: minLength(6) },
        password_confirmation: { sameAs: sameAs('password') }
      },
      step2: {
        address: {required},
        country: {required},
        state: {required},
        city: {required}
      },
      step3: {
        postcode: {required, numeric},
        phone: {required, numeric},
        phone_country_code: {required, numeric}
      },
      step4: {
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
          this.$v.registerForm.step1.email.$touch()
          this.$v.registerForm.step1.password.$touch()
          this.$v.registerForm.step1.password_confirmation.$touch()
        } else if(this.$v.registerForm.step2.$invalid) {
          this.$v.registerForm.step2.address.$touch()
          this.$v.registerForm.step2.country.$touch()
          this.$v.registerForm.step2.state.$touch()
          this.$v.registerForm.step2.city.$touch()
        } else if(this.$v.registerForm.step3.$invalid) {
          this.$v.registerForm.step3.postcode.$touch()
          this.$v.registerForm.step3.phone_country_code.$touch()
          this.$v.registerForm.step3.phone.$touch()
        } else {
          this.$v.registerForm.step4.odds_type.$touch()
          this.$v.registerForm.step4.currency_id.$touch()
        }
    },
    prevStep() {
      this.step--
    },
    nextStep() {
      if(!this.checkIfCurrentStepIsInvalid) {
        this.step++
      } else {
        this.triggerValidationErrors()
      }
    },
    register() {
      if (!this.$v.registerForm.$invalid) {
        let data = {
          name: this.registerForm.step1.name,
          email: this.registerForm.step1.email,
          password: this.registerForm.step1.password,
          password_confirmation: this.registerForm.step1.password_confirmation,
          birthdate: this.registerForm.step1.birthdate,
          address: this.registerForm.step2.address,
          country: this.registerForm.step2.country,
          state: this.registerForm.step2.state,
          city: this.registerForm.step2.city,
          postcode: this.registerForm.step3.postcode,
          phone_country_code: this.registerForm.step3.phone_country_code,
          phone: this.registerForm.step3.phone,
          odds_type: this.registerForm.step4.odds_type,
          currency_id: this.registerForm.step4.currency_id,
        }

        axios.post('/auth/register', data)
        .then((response) => {
          this.isRegisterSuccessful = true
          this.successfulRegisterMessage = response.data.message
        })
        .catch(e => console.log(err))
      } else {
        this.triggerValidationErrors()
      }
    }
  }
}
</script>
