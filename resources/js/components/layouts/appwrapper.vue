<template>
    <div class="w-full sm:pb-0 pb-8" :class="{'flex flex-col items-center':!$store.state.auth.isAuthenticated}">
        <nav class="flex bg-white shadow-md w-full h-16" :class="[!$store.state.auth.isAuthenticated ? 'mb-16' : 'fixed z-10']">
            <div class="flex justify-start items-center w-5/12 ml-16" v-if="$store.state.auth.isAuthenticated">
                <router-link to="/" class="text-sm uppercase ml-5 sm:px-4 px-6 hover:bg-orange-500 hover:text-white navlink">Trade</router-link>
                <router-link to="/settlement" class="text-sm uppercase ml-5 sm:px-4 px-6 hover:bg-orange-500 hover:text-white navlink">Settlement</router-link>
                <router-link to="/open-orders" class="text-sm uppercase ml-5 sm:px-4 px-6 hover:bg-orange-500 hover:text-white navlink">Open Orders</router-link>
                <router-link to="/analytics" class="text-sm uppercase ml-5 sm:px-4 px-6 hover:bg-orange-500 hover:text-white navlink">Analytics</router-link>
            </div>
            <div class="flex justify-center items-center" :class="[!$store.state.auth.isAuthenticated ? 'w-full' : 'w-2/12']">
                <img :src="logo" class="w-12 mt-2">
            </div>
            <div class="flex justify-end items-center w-5/12 mr-16" v-if="$store.state.auth.isAuthenticated">
                <div class="username relative inline-block sm:px-4 px-6 navlink">
                    <a href="#" class="text-gray-700 text-sm uppercase ml-5 mr-5">{{$store.state.auth.authUser.name}} <span class="text-xs text-gray-700 font-normal"><i class="fas fa-chevron-down"></i></span></a>
                    <div class="absolute mt-5 bg-gray-800 py-1 shadow-xl w-48 dropdown" v-if="!isLoggingOut">
                        <router-link to="/settings" class="text-white text-sm uppercase pl-6 pb-1 block hover:bg-orange-500">Settings</router-link>
                        <a class="text-white text-sm uppercase pl-6 block hover:bg-orange-500 logout" href="#" role="button" @click="logout">Logout</a>
                    </div>
                </div>
            </div>
        </nav>
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
            isLoggingOut: false
        }
    },
    mounted() {
        console.log(moment().tz().format())
    },
    computed: {
        ...mapState('settings', ['generalSettingsConfig'])
    },
    methods: {
        logout() {
            let token = Cookies.get('access_token')

            axios.post('/v1/auth/logout', null, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                location.reload('/login')
                this.$store.commit('auth/SET_AUTH_USER', '')
                Cookies.remove('access_token')
                setTimeout(() => {
                    this.$store.commit('auth/SET_IS_AUTHENTICATED', false)
                }, 2000)
            })
            .catch(err => {
                console.log(err)
            })
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
