<template>
    <div>
        <div class="bg-gray-200 flex flex-col items-center h-screen w-full sm:pb-0 pb-8" v-if="!$store.state.isAuthenticated">
            <img :src="logo" class="sm:w-64 w-40 sm:mt-12 mt-10">
            <slot></slot>
        </div>

        <div v-else>
            <nav class="flex justify-between bg-gray-200 shadow-md w-full h-16 pb-2">
                <div class="flex">
                    <img :src="logo" class="w-12 mt-2 ml-5">
                    <router-link to="/" class="text-gray-700 text-sm uppercase mt-6 ml-5">Trade</router-link>
                    <router-link to="/settlement" class="text-gray-700 text-sm uppercase mt-6 ml-5">Settlement</router-link>
                    <router-link to="/open-orders" class="text-gray-700 text-sm uppercase mt-6 ml-5">Open Orders</router-link>
                    <router-link to="/settings" class="text-gray-700 text-sm uppercase mt-6 ml-5">Settings</router-link>
                </div>

                <div class="flex">
                    <a class="text-gray-700 text-sm uppercase mt-6 ml-5" href="#" role="button">
                        <span class="caret"></span>
                    </a>
                    <a class="text-gray-700 text-sm uppercase mt-6 ml-5 mr-5" href="#" role="button" @click="logout">Logout</a>
                </div>
            </nav>
            <main class="py-4">
                <slot></slot>
            </main>
        </div>
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
            axios.get('/auth/logout', { headers:{ 'Authorization': `Bearer ${token}`}})
            .then(response => {
                this.$store.commit('SET_IS_AUTHENTICATED', false)
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
