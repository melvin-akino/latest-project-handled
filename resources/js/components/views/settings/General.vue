<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="mb-6">
                <label class="text-sm">Price Format</label>
                <div class="relative w-1/3">
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="generalSettingsForm.price_format">
                        <option v-for="priceFormat in priceFormats" :key="priceFormat.id"  :value="priceFormat.id" :selected="priceFormat.id === generalSettingsForm.price_format">{{priceFormat.value}}</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
            </div>
            <div class="mb-6">
                <label class="text-sm">Time Zone</label>
                <div class="flex justify-between items-center">
                    <div class="relative w-4/12 mr-6">
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="generalSettingsForm.timezone">
                            <option v-for="timezone in timezones" :key="timezone.id" :value="timezone.id" :selected="timezone.id === generalSettingsForm.timezone">GMT {{timezone.timezone}} {{timezone.name}}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <p class="w-7/12 text-xs">This converts all the event times to your selected time zone where possible. </p>
                </div>
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
            generalSettingsForm: {
                price_format: this.$store.state.userConfig.general.price_format,
                timezone: this.$store.state.userConfig.general.timezone
            },
            priceFormats: [],
            timezones: []
        }
    },
    head: {
        title() {
            return {
                inner: 'Settings - General'
            }
        }
    },
    mounted() {
        this.getTimezones()
        this.priceFormats = this.$store.state.userConfig.general.price_formats
    },
    methods: {
        getTimezones() {
            axios.get('/v1/timezones')
            .then(response => this.timezones = response.data.data)
            .catch(err => console.log(err))
        },
        saveChanges() {
            let token = Cookies.get('access_token')
            let data = {
                price_format: this.generalSettingsForm.price_format,
                timezone: this.generalSettingsForm.timezone
            }

            axios.post('/v1/user/settings/general', data, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                Swal.fire({
                    icon: 'success',
                    text: response.data.message
                })
            })
            .catch(err => {
                console.log(err)
            })
        }
    }
}
</script>
