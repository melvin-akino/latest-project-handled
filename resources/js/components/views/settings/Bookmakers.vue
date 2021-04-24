<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="mb-12" v-for="bookie in bookies" :key="bookie.id">
                <label class="text-sm relative flex items-center">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :disabled="bookie.is_primary == true" :value="bookie.id" v-model="disabledBookies">
                    <span class="absolute shadow shadow-lg w-6 h-6 rounded-full" :class="[!disabledBookies.includes(bookie.id) ? 'on-switch bg-orange-500' : 'bg-white left-0']"></span>
                    <span>{{bookie.alias}}</span>
                    <span v-if="bookie.is_primary == true" class="py-1 px-2 ml-2 rounded bg-orange-500 text-white">PRIMARY</span>
                </label>
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
            bookies: [],
            disabledBookies: []
        }
    },
    head: {
        title() {
            return {
                inner: 'Settings - Bookies'
            }
        }
    },
    mounted() {
        this.getBookies()
        this.getUserConfig()
    },
    methods: {
        getBookies() {
            let token = Cookies.get('mltoken')

            axios.get('v1/bookies', { headers: { 'Authorization': `Bearer ${token}` }})
                .then(response => this.bookies = response.data.data)
                .catch(err => {
                    this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
                })
        },
        getUserConfig() {
            let token = Cookies.get('mltoken')

            axios.get('v1/user/settings/bookies', { headers: { 'Authorization': `Bearer ${token}` }})
                .then(response => this.disabledBookies = response.data.data.disabled_bookies)
                .catch(err => {
                    this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
                })
        },
        saveChanges() {
            if(this.bookies.length == this.disabledBookies.length) {
                Swal.fire({
                    icon: 'error',
                    text: 'At least one bookmaker should be enabled.'
                })
            } else {
                let token = Cookies.get('mltoken')
                let data = this.bookies.map(bookie => {
                    return {
                        provider_id: bookie.id,
                        active: this.disabledBookies.includes(bookie.id) ? false : true
                    }
                })

                axios.post('/v1/user/settings/bookies', data, { headers: { 'Authorization': `Bearer ${token}` } })
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
}
</script>

<style>
    .on-switch {
        left: 24px;
    }
</style>
