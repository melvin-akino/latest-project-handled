<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="notificationSettingsForm.bet_confirm" @change="toggleNotificationSettings(notificationSettingsForm.bet_confirm, 'bet_confirm')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[notificationSettingsForm.bet_confirm === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Bet placement confirmation</span>
                <p class="text-xs w-7/12 text-left">Show a confirmation popup every time you attempt to place an order. It will contain information about the bet(s) you are trying to place and bet placement conditions</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="notificationSettingsForm.site_notifications" @change="toggleNotificationSettings(notificationSettingsForm.site_notifications, 'site_notifications')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[notificationSettingsForm.site_notifications === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Show website notifications</span>
                <p class="text-xs w-7/12 text-left">Show new feature and platform update notifications. When off, you will only be shown essential announcements</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="notificationSettingsForm.popup_notifications" @change="toggleNotificationSettings(notificationSettingsForm.popup_notifications, 'popup_notifications')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[notificationSettingsForm.popup_notifications === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Popup Event Notifications</span>
                <p class="text-xs w-7/12 text-left">Select to show browser notifications for goals, red cards, kick-off and order status updates. This only displays notifications for the events in your favourites and the events you have positions on</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="notificationSettingsForm.order_notifications" @change="toggleNotificationSettings(notificationSettingsForm.order_notifications, 'order_notifications')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[notificationSettingsForm.order_notifications === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Popup Order Notifications</span>
                <p class="text-xs w-7/12 text-left">Select to show browser notifications when an order is done, settled or fails</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="notificationSettingsForm.event_sounds" @change="toggleNotificationSettings(notificationSettingsForm.event_sounds, 'event_sounds')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[notificationSettingsForm.event_sounds === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Bet Event Sounds</span>
                <p class="text-xs w-7/12 text-left">Play trade page sounds for goals, red cards and kick-off. This only happens for the events in your favourites or the events you have positions on</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="notificationSettingsForm.order_sounds" @change="toggleNotificationSettings(notificationSettingsForm.order_sounds, 'order_sounds')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[notificationSettingsForm.order_sounds === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Play Order Status Sounds</span>
                <p class="text-xs w-7/12 text-left">Play sounds when an order is done, settled or fails</p>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-4 py-2">Save Changes</button>
            </div>
        </form>
    </div>
</template>

<script>
import Cookies from 'js-cookie'
import Swal from 'sweetalert2'

export default {
    data() {
        return {
            notificationSettingsForm: {
                bet_confirm: null,
                site_notifications: null,
                popup_notifications: null,
                order_notifications: null,
                event_sounds: null,
                order_sounds: null
            }
        }
    },
    head: {
        title() {
            return {
                inner: 'Settings - Notifications and Sounds'
            }
        }
    },
    mounted() {
        this.getUserConfig()
    },
    methods: {
        getUserConfig() {
            let token = Cookies.get('mltoken')

            axios.get('v1/user/settings/notifications-and-sounds', { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                Object.keys(this.notificationSettingsForm).forEach(field => {
                    this.notificationSettingsForm[field] = response.data.data[field]
                })
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        },
        toggleNotificationSettings(isActive, key) {
            if (isActive === '1') {
                this.notificationSettingsForm[key] = '0'
            } else {
                this.notificationSettingsForm[key] = '1'
            }
        },
        saveChanges() {
            let token = Cookies.get('mltoken')
            let data = {
                bet_confirm: this.notificationSettingsForm.bet_confirm,
                site_notifications: this.notificationSettingsForm.site_notifications,
                popup_notifications: this.notificationSettingsForm.popup_notifications,
                order_notifications: this.notificationSettingsForm.order_notifications,
                event_sounds: this.notificationSettingsForm.event_sounds,
                order_sounds: this.notificationSettingsForm.order_sounds
            }

            axios.post('/v1/user/settings/notifications-and-sounds', data, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                Swal.fire({
                    icon: 'success',
                    text: response.data.message
                })
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        }
    }
}
</script>

<style>
    .on-switch {
        left: 24px;
    }
</style>
