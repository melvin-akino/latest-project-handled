<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" value="1" v-model="notificationsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-white"></span>
                </label>
                <span class="w-4/12 text-sm">Bet placement confirmation</span>
                <p class="text-xs w-7/12 text-left">Show a confirmation popup every time you attempt to place an order. It will contain information about the bet(s) you are trying to place and bet placement conditions</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" value="2" v-model="notificationsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-white"></span>
                </label>
                <span class="w-4/12 text-sm">Show website notifications</span>
                <p class="text-xs w-7/12 text-left">Show new feature and platform update notifications. When off, you will only be shown essential announcements</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" value="3" v-model="notificationsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-white"></span>
                </label>
                <span class="w-4/12 text-sm">Popup Event Notifications</span>
                <p class="text-xs w-7/12 text-left">Select to show browser notifications for goals, red cards, kick-off and order status updates. This only displays notifications for the events in your favourites and the events you have positions on</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" value="4" v-model="notificationsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-white"></span>
                </label>
                <span class="w-4/12 text-sm">Popup Order Notifications</span>
                <p class="text-xs w-7/12 text-left">Select to show browser notifications when an order is done, settled or fails</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" value="5" v-model="notificationsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-white"></span>
                </label>
                <span class="w-4/12 text-sm">Bet Event Sounds</span>
                <p class="text-xs w-7/12 text-left">Play trade page sounds for goals, red cards and kick-off. This only happens for the events in your favourites or the events you have positions on</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" value="6" v-model="notificationsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-white"></span>
                </label>
                <span class="w-4/12 text-sm">Play Order Status Sounds</span>
                <p class="text-xs w-7/12 text-left">Play sounds when an order is done, settled or fails</p>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-12 py-4">Save Changes</button>
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
