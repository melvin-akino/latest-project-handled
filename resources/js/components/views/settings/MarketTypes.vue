<template>
    <div class="mt-12 mb-6">
        <p class="mb-6 text-sm">The types of bet that are shown in the table of the trading screen</p>
        <form @submit.prevent="saveChanges">
            <div class="flex flex-col justify-center">
                <div class="betColumnSportsTab mb-6">
                    <button type="button" class="p-5 ml-1 bg-white border border-blue-500 border-t-0 border-l-0 border-r-0 border-b text-sm" v-for="sport in sports" :key="sport.sport_id" @click="filterSport(sport.sport_id)">{{sport.sport}}</button>
                </div>
                <div class="w-1/4">
                    <div class="mb-12" v-for="column in columnsToDisplay" :key="column.sport_odd_type_id">
                        <label class="text-sm relative flex items-center">
                            <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="column.sport_odd_type_id" v-model="disabledBetColumns">
                            <span class="absolute shadow shadow-lg w-6 h-6 rounded-full" :class="[!disabledBetColumns.includes(column.sport_odd_type_id) ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                            {{column.name}}
                        </label>
                    </div>
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
            betColumns: [],
            disabledBetColumns: [],
            columnsToDisplay: []
        }
    },
    head: {
        title() {
            return {
                inner: 'Settings - Bet Columns'
            }
        }
    },
    computed: {
        sports() {
            return this.betColumns.map(column => {
                return { sport_id: column.sport_id, sport: column.sport }
            })
        }
    },
    mounted() {
        this.getBetColumns()
        this.getUserConfig()
    },
    methods: {
        filterSport(sport_id) {
            this.betColumns.filter(column => column.sport_id === sport_id).map(column => this.columnsToDisplay = column.odds)
        },
        getUserConfig() {
            let token = Cookies.get('mltoken')

            axios.get('v1/user/settings/bet-columns', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => this.disabledBetColumns = response.data.data.disabled_columns)
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        },
        getBetColumns() {
            let token = Cookies.get('mltoken')

            axios.get('v1/sports/odds', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.betColumns = response.data.data
                response.data.data.filter(column => column.sport_id === 1).map(column => this.columnsToDisplay = column.odds)
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        },
        saveChanges() {
            let token = Cookies.get('mltoken')
            let odds = this.betColumns.map(column => column.odds).reduce((prevOddGroup, nextOddGroup) => prevOddGroup.concat(nextOddGroup))
            let data = odds.map(odd => {
                return {
                    sport_odd_type_id: odd.sport_odd_type_id,
                    active: this.disabledBetColumns.includes(odd.sport_odd_type_id) ? false : true
                }
            })

            axios.post('/v1/user/settings/bet-columns', data, { headers: { 'Authorization': `Bearer ${token}` } })
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
