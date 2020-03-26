<template>
    <div class="w-full sm:pb-0 pb-8" :class="{'flex flex-col items-center':!$store.state.auth.isAuthenticated}">
        <nav class="flex bg-white shadow-md w-full h-16 fixed z-20" v-if="$store.state.auth.isAuthenticated">
            <div class="flex justify-start items-center w-1/2 ml-16">
                <img :src="logo" class="w-12 mt-2">
                <router-link to="/" class="text-sm uppercase ml-5 sm:px-4 px-6 hover:bg-orange-500 hover:text-white navlink">Trade</router-link>
                <router-link to="/orders" class="text-sm uppercase ml-1 sm:px-4 px-6 hover:bg-orange-500 hover:text-white navlink">My Orders</router-link>
                <form class="w-1/2 xl:w-1/3 px-4" @submit.prevent="searchLeaguesOrTeams">
                    <div class="flex items-center">
                        <input type="text" class="appearance-none bg-transparent border-b border-gray-800 w-full text-sm text-gray-700 mr-1 py-1 leading-tight focus:outline-none" placeholder="Search Leagues or Teams" v-model="searchKeyword">
                        <button class="text-gray-700" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            <div class="flex justify-end items-center w-1/2 mr-16">
                <p class="text-gray-600 text-sm capitalize">{{time}} | GMT {{defaultTimezone.timezone}} {{defaultTimezone.name}}</p>
                <div class="username relative inline-block sm:px-4 px-6 navlink">
                    <a href="#" class="text-gray-700 text-sm uppercase ml-5 mr-5">{{display_name}} <span class="text-xs text-gray-700 font-normal"><i class="fas fa-chevron-down"></i></span></a>
                    <div class="absolute mt-5 bg-gray-800 py-1 shadow-xl w-48 dropdown" v-if="!isLoggingOut">
                        <a class="text-white text-sm uppercase pl-6 pb-1 block hover:bg-orange-500" href="#" role="button">Balances</a>
                        <router-link to="/settings" class="text-white text-sm uppercase pl-6 pb-1 block hover:bg-orange-500">Settings</router-link>
                        <a class="text-white text-sm uppercase pl-6 pb-1 block hover:bg-orange-500" href="#" role="button">Help</a>
                        <a class="text-white text-sm uppercase pl-6 pb-1 block hover:bg-orange-500" href="#" role="button">Feedback</a>
                        <a class="text-white text-sm uppercase pl-6 pb-1 block hover:bg-orange-500 logout" href="#" role="button" @click="logout">Logout</a>
                    </div>
                </div>
            </div>
        </nav>
        <div v-if="!$store.state.auth.isAuthenticated">
            <img :src="logo" class="w-48 mt-2">
        </div>
        <main class="pt-16">
            <slot></slot>
        </main>
    </div>
</template>

<script>
import Logo from '../../../assets/images/icon.png'
import axios from 'axios'
import Cookies from 'js-cookie'
import moment from 'moment-timezone'
import { mapState } from 'vuex'

export default {
    data() {
        return {
            logo: Logo,
            isLoggingOut: false,
            display_name: '',
            time: '',
            searchKeyword: ''
        }
    },
    computed: {
        ...mapState('settings', ['defaultTimezone'])
    },
    mounted() {
        this.getDefaultTimezone()
        this.display_name = Cookies.get('display_name')
    },
    methods: {
        async getDefaultTimezone() {
            try {
                if (this.$store.state.auth.isAuthenticated) {
                    if (!this.defaultTimezone) {
                        this.$store.dispatch('settings/getDefaultTimezone')
                        .then(response => {
                            this.$store.commit('settings/SET_DEFAULT_TIMEZONE', response)
                            setInterval(() => {
                                this.time = moment().tz(this.defaultTimezone.name).format('hh:mm:ss')
                            }, 1000)
                        })
                    }
                } else {
                    this.$store.commit('settings/SET_DEFAULT_TIMEZONE', '')
                    this.timezone = {}
                    this.time = ''
                }
            } catch(err) {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            }
        },
        logout() {
            let token = Cookies.get('mltoken')

            axios.post('/v1/auth/logout', null, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                location.reload('/login')
                Cookies.remove('mltoken')
                Cookies.remove('display_name')
                setTimeout(() => {
                    this.$store.commit('auth/SET_IS_AUTHENTICATED', false)
                }, 2000)
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
            this.isLoggingOut = true
        },
        searchLeaguesOrTeams() {

        }
    }
}
</script>

<style lang="scss">
    .router-link-exact-active {
        background-color: #ED8936;
        color: #ffffff;
    }

    .navlink {
        padding-top: 22px;
        padding-bottom: 21px;
        transition: all 0.3s ease-in-out;
    }

    .dropdown {
        display: none;
    }

    .username {
        &:hover .dropdown {
            display: block;
        }
    }
</style>
