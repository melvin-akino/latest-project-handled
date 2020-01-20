<template>
    <div class="h-full m-12">
        <p class="text-3xl mb-12">Notifications & Sounds</p>
        <form @submit.prevent="saveChanges">
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="1" v-model="notificationsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    Bet placement confirmation
                </label>
            </div>
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="2" v-model="notificationsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    Show website notifications
                </label>
            </div>
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="3" v-model="notificationsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    Popup Event Notifications
                </label>
            </div>
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="4" v-model="notificationsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    Popup Order Notifications
                </label>
            </div>
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="5" v-model="notificationsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    Bet Event Sounds
                </label>
            </div>
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="6" v-model="notificationsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    Play Order Status Sounds
                </label>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-md uppercase px-12 py-4">Save Changes</button>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    data() {
        return {
            notificationsCheckedSettings:[]
        }
    },
    head:{
        title() {
            return {
                inner: 'Settings - Notifications and Sounds'
            }
        }
    },
    methods:{
        saveChanges() {

        }
    }
}
</script>
