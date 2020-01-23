<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="mb-12" v-for="bookie in bookies" :key="bookie.id">
                <label class="text-sm relative flex items-center">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="bookie.id" v-model="disabledBookies">
                    <span class="absolute shadow shadow-lg w-6 h-6 rounded-full" :class="[!disabledBookies.includes(bookie.id) ? 'on-switch bg-orange-500' : 'bg-white left-0']"></span>
                    <div><span class="font-bold">{{bookie.alias}}</span> ({{bookie.name}})</div>
                </label>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-12 py-4">Save Changes</button>
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
            bookies:[],
            disabledBookies:[]
        }
    },
    head:{
        title() {
            return {
                inner: 'Settings - Bookies'
            }
        }
    },
    mounted() {
        this.bookies = this.$store.state.userProviders
        this.disabledBookies = this.$store.state.userConfig.bookies.disabled_bookies
    },
    methods:{
        saveChanges() {
            let token = Cookies.get('access_token')
            let data = this.bookies.map(bookie => {
                return {
                    provider_id: bookie.id,
                    active: this.disabledBookies.includes(bookie.id) ? false : true
                }
            })

            axios.post('/v1/user/settings/bookies', data, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                Swal.fire({
                    icon:'success',
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

<style>
    .on-switch{
        left:24px;
    }
</style>
