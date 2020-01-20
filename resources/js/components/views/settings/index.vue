<template>
    <div class="settings flex container mx-auto">
        <div class="mt-3 mr-16 fixed">
            <p class="text-4xl">Trade Settings</p>
            <div class="flex flex-col mt-3">
                <router-link to="/settings/general" class="p-3 bg-white border border-gray-400">General</router-link>
                <router-link to="/settings/profile" class="p-3 bg-white border border-gray-400">Profile</router-link>
                <router-link to="/settings/trade-page" class="p-3 bg-white border border-gray-400">Trade Page</router-link>
                <router-link to="/settings/bet-slip" class="p-3 bg-white border border-gray-400">Bet Slip</router-link>
                <router-link to="/settings/bookies" class="p-3 bg-white border border-gray-400">Bookies</router-link>
                <router-link to="/settings/bet-columns" class="p-3 bg-white border border-gray-400">Bet Columns</router-link>
                <router-link to="/settings/notifications-and-sounds" class="p-3 bg-white border border-gray-400">Notifications & Sounds</router-link>
            </div>
        </div>
        <div class="w-3/4 ml-64">
            <router-view></router-view>
        </div>
    </div>
</template>

<script>
export default {
    head:{
        title() {
            return {
                inner: 'Settings'
            }
        }
    },
}
</script>

<style>

</style>
