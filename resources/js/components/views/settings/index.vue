<template>
    <div class="settings flex container mx-auto">
        <div class="mr-16 fixed">
            <div class="flex flex-col mt-6">
                <router-link to="/settings/general" class="py-3 px-8 bg-white border border-gray-400 text-sm">General</router-link>
                <router-link to="/settings/profile" class="py-3 px-8 bg-white border border-gray-400 text-sm">Profile</router-link>
                <router-link to="/settings/trade-page" class="py-3 px-8 bg-white border border-gray-400 text-sm">Trade Page</router-link>
                <router-link to="/settings/bet-slip" class="py-3 px-8 bg-white border border-gray-400 text-sm">Bet Slip</router-link>
                <router-link to="/settings/bookmakers" class="py-3 px-8 bg-white border border-gray-400 text-sm">Bookmakers</router-link>
                <router-link to="/settings/market-types" class="py-3 px-8 bg-white border border-gray-400 text-sm">Market Types</router-link>
                <router-link to="/settings/notifications-and-sounds" class="py-3 px-8 bg-white border border-gray-400 text-sm">Notifications & Sounds</router-link>
            </div>
        </div>
        <div class="w-3/4 ml-64">
            <p class="text-2xl mt-6 mb-12 capitalize">{{titlePageUri}}</p>
            <router-view></router-view>
            <div class="shadow border border-gray-400 bg-white w-full p-5 mb-6">
                <label class="block font-bold capitalize text-gray-700 text-sm mr-5">Select Language</label>
                <div class="relative w-1/3 mt-4">
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" @change="changeLanguage" v-model="language">
                        <option v-for="language in languages" :key="language.id" :value="language.id">{{language.value}}</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
            </div>
            <div class="flex justify-evenly shadow border border-gray-400 bg-white w-full p-5 mb-6">
                <button class="bg-red-600 text-white text-sm uppercase px-2 w-1/4 mr-5 hover:bg-red-700" @click="resetToDefaultSettings">Reset to default</button>
                <p class="text-xs">Reset all your settings to platform default. You will lose saved history/bets views and all your trade and betslip options will be reverted to the basic ones.</p>
            </div>
        </div>
    </div>
</template>

<script>
import Cookies from 'js-cookie'
import Swal from 'sweetalert2'

export default {
    data() {
        return {
            languages: [],
            language: null
        }
    },
    head: {
        title() {
            return {
                inner: 'Settings'
            }
        }
    },
    computed: {
        titlePageUri(){
            let titlePageUri = this.$route.path.split('/')
            return titlePageUri[titlePageUri.length - 1].replace(/-/g, ' ')
        }
    },
    mounted() {
        this.getUserLanguage()
        this.languages = this.$store.state.settings.settingsData.language
    },
    methods: {
        getUserLanguage() {
            let token = Cookies.get('mltoken')

            axios.get('v1/user/settings/language', { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => this.language = response.data.data.language)
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        },
        saveChangedLanguage() {
            let token = Cookies.get('mltoken')

            axios.post('/v1/user/settings/language', { language: this.language }, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                Swal.fire({
                    icon: 'success',
                    text: response.data.message
                })
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        },
        changeLanguage() {
            Swal.fire({
                text: 'Are you sure you want to change language?',
                showCancelButton: true,
                confirmButtonText: 'Confirm',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#ed8936',
                reverseButtons: true,
            })
            .then(response => {
                if (response.value) {
                    this.saveChangedLanguage()
                } else {
                    this.getUserLanguage()
                }
            })
        },
        resetToDefaultSettings() {
            Swal.fire({
                text: 'Are you sure you want to reset to default settings?',
                showCancelButton: true,
                confirmButtonText: 'Yes, reset to default settings',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#ed8936',
                reverseButtons: true
            })
            .then(response => {
                if (response.value) {
                    let token = Cookies.get('mltoken')

                    axios.post('/v1/user/settings/reset', null, { headers: { 'Authorization': `Bearer ${token}` } })
                    .then(response => {
                        Swal.fire({
                            icon: 'success',
                            text: response.data.message
                        })
                        .then(() => {
                            location.reload()
                        })
                    })
                    .catch(err => {
                        this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
                    })
                }
            })
        }
    }
}
</script>

<style lang="scss">
    .settings {
      input:not([disabled]), select {
        background-color: #ffffff !important;
        border-style: solid !important;
      }
      select {
        appearance: none !important;
      }
      input:disabled {
        background: transparent !important;
      }
      button[type=submit] {
        color: #ffffff !important;
      }
      input[type=checkbox] {
        --bg-opacity: 1;
        background-color: #cbd5e0 !important;
        background-color: rgba(203,213,224,var(--bg-opacity)) !important;
      }
    }

    .swal2-styled.swal2-cancel {
        background-color: #ffffff;
        color: #aaa;
        border: solid #aaa 1px;
    }

    .swal2-styled.swal2-cancel:hover {
        background-color: #aaa;
        color: #ffffff;
    }
</style>
