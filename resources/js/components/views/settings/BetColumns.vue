<template>
    <div class="mt-12 mb-12">
        <p class="mb-12 text-sm">The types of bet that are shown in the table of the trading screen</p>
        <form @submit.prevent="saveChanges">
            <div class="flex justify-evenly items-center">
                <div class="soccer w-1/4">
                    <p class="text-xl mb-4">{{soccerOddsConfiguration[0].sport}}</p>
                    <div class="mb-12" v-for="soccerOddsConfiguration in soccerOddsConfiguration" :key="soccerOddsConfiguration.sport_odd_type_id">
                        <label class="text-sm relative flex items-center">
                            <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" @change="soccerOddsConfiguration.active = !soccerOddsConfiguration.active">
                            <span class="absolute shadow shadow-lg w-6 h-6 rounded-full" :class="[soccerOddsConfiguration.active ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                            {{soccerOddsConfiguration.type}}
                        </label>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-12 py-4">Save Changes</button>
            </div>
        </form>
    </div>
</template>

<script>
import Cookies from 'js-cookie'
export default {
    data() {
        return {
            oddsConfiguration:[]
        }
    },
    head:{
        title() {
            return {
                inner: 'Settings - Bet Columns'
            }
        }
    },
    computed: {
        soccerOddsConfiguration() {
            return this.oddsConfiguration.filter(oddsConfiguration => oddsConfiguration.sport_id === 1)
        }
    },
    mounted() {
        this.getOddsConfiguration()
    },
    methods:{
        getOddsConfiguration() {
            let token = Cookies.get('access_token')
            axios.get('/v1/user/configuration/odds', { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => this.oddsConfiguration = response.data.data)
            .catch(err => console.log(err))
        },
        saveChanges() {
            /* save settings here */
        }
    }
}
</script>

<style>
    .on-switch {
        left: 24px;
    }
</style>
