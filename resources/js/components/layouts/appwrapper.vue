<template>
    <div class="w-full sm:pb-0 pb-8" :class="{'flex flex-col':!$store.state.auth.isAuthenticated, 'items-center': !$store.state.auth.isAuthenticated && $store.state.auth.authLayout}">
        <nav class="flex bg-white shadow-md w-full h-16 fixed z-20" v-if="$store.state.auth.isAuthenticated">
            <div class="flex justify-center w-1/6">
                <img :src="logo" class="w-12 mt-2" alt="Multiline Logo">
            </div>
            <div class="flex w-5/6">
                <div class="flex justify-start items-center w-1/2">
                    <router-link to="/" class="text-sm uppercase sm:px-4 px-6 hover:bg-orange-500 hover:text-white navlink">Trade</router-link>
                    <router-link to="/orders" class="text-sm uppercase ml-1 sm:px-4 px-6 hover:bg-orange-500 hover:text-white navlink">My Orders</router-link>
                    <router-link to="/history" class="text-sm uppercase ml-1 sm:px-4 px-6 hover:bg-orange-500 hover:text-white navlink">Bet History</router-link>
                    <Search></Search>
                </div>
                <div class="flex justify-end items-center w-1/2">
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
            </div>
        </nav>
        <div v-if="!$store.state.auth.isAuthenticated && $store.state.auth.authLayout">
            <img :src="logo" class="w-48 mt-2" alt="Multiline Logo">
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
import Search from '../views/trade/Search'

export default {
    components: {
        Search
    },
    data() {
        return {
            logo: Logo,
            isLoggingOut: false,
            display_name: '',
            time: '',
        }
    },
    computed: {
        ...mapState('settings', ['defaultTimezone'])
    },
    mounted() {
        this.getDefaultGeneralSettings()
        this.display_name = Cookies.get('display_name')
    },
    methods: {
        async getDefaultGeneralSettings() {
            try {
                if (this.$store.state.auth.isAuthenticated) {
                    if (!this.defaultTimezone) {
                        this.$store.dispatch('settings/getDefaultGeneralSettings')
                        .then(response => {
                            setInterval(() => {
                                this.time = moment().tz(this.defaultTimezone.name).format('HH:mm:ss')
                            }, 1000)
                        })
                    }
                } else {
                    this.$store.commit('settings/SET_DEFAULT_TIMEZONE', '')
                    this.timezone = {}
                    this.time = ''
                }
            } catch(err) {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            }
        },
        logout() {
            this.$store.dispatch('auth/logout', { new_access: false })
            this.isLoggingOut = true
        }
    }
}
</script>

<style lang="scss">
    .router-link-exact-active {
        background-color: #ED8936;
        color: #ffffff;
    }

    nav {
        .username .dropdown a {
            color: #FFFFFF !important;
        }
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
