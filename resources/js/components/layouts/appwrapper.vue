<template>
    <div class="w-full sm:pb-0 pb-8" :class="{'flex flex-col items-center':!$store.state.isAuthenticated}">
        <nav class="flex bg-white shadow-md w-full h-16" :class="[!$store.state.isAuthenticated ? 'mb-16' : 'fixed z-10']">
            <div class="flex justify-start items-center w-5/12 ml-16" v-if="$store.state.isAuthenticated">
                <router-link to="/" class="text-gray-700 text-sm uppercase ml-5 sm:px-4 px-6 py-6">Trade</router-link>
                <router-link to="/settlement" class="text-gray-700 text-sm uppercase ml-5 sm:px-4 px-6 py-6">Settlement</router-link>
                <router-link to="/open-orders" class="text-gray-700 text-sm uppercase ml-5 sm:px-4 px-6 py-6">Open Orders</router-link>
                <router-link to="/settings/general" class="text-gray-700 text-sm uppercase ml-5 sm:px-4 px-6 py-6">Settings</router-link>
            </div>
            <div class="flex justify-center items-center" :class="[!$store.state.isAuthenticated ? 'w-full' : 'w-2/12']">
                <img :src="logo" class="w-12 mt-2">
            </div>
            <div class="flex justify-end items-center w-5/12 mr-16"  v-if="$store.state.isAuthenticated">
                <a class="text-gray-700 text-sm uppercase ml-5" href="#" role="button">
                    {{$store.state.authUser.name}}
                </a>
                <a class="text-gray-700 text-sm uppercase ml-5 mr-5" href="#" role="button" @click="logout">Logout</a>
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

export default {
    data() {
        return {
            logo: Logo
        }
    },
    methods: {
        logout() {
            let token = Cookies.get('access_token')
            axios.get('/v1/auth/logout', { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                this.$store.commit('SET_IS_AUTHENTICATED', false)
                this.$store.commit('SET_AUTH_USER', '')
                Cookies.remove('access_token')
                this.$router.push('/login')
            })
            .catch(err => {
                console.log(err)
            })
        }
    }
}
</script>

<style lang="scss">
    .router-link-exact-active {
        background-color: #ED8936;
        color: #ffffff;
        font-weight: bold;
    }
</style>
