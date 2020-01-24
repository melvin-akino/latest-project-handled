<template>
    <div class="mt-12 mb-6">
        <p class="mb-6 text-sm">The types of bet that are shown in the table of the trading screen</p>
        <form @submit.prevent="saveChanges">
            <div class="flex flex-col justify-center">
                <div class="betColumnSportsTab mb-6">
                    <button type="button" class="p-5 ml-1 bg-white border border-blue-500 border-t-0 border-l-0 border-r-0 border-b text-sm" v-for="sport in sportsGroup" :key="sport" @click="filterSport(sport)">{{sport}}</button>
                </div>
                <div class="w-1/4">
                    <div class="mb-12" v-for="column in columnsToDisplay" :key="column.id">
                        <label class="text-sm relative flex items-center">
                            <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="column.id" v-model="disabledBetColumns">
                            <span class="absolute shadow shadow-lg w-6 h-6 rounded-full" :class="[!disabledBetColumns.includes(column.id) ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                            {{column.type}}
                        </label>
                    </div>
                </div>
            </div>
            <div v-for="groupedBySportsBetColumns in groupedBySportsBetColumns.soccer" :key="groupedBySportsBetColumns">{{groupedBySportsBetColumns}}</div>
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
        groupedBySportsBetColumns() {
            return this.betColumns.reduce((sport, data) => {
                sport[data.sport] = (sport[data.sport] || []).concat(data)
                return sport
            }, {})
        },
        sportsGroup() {
            return Object.keys(this.groupedBySportsBetColumns)
        },

    },
    mounted() {
        this.betColumns = this.$store.state.userSportsOddTypes
        this.disabledBetColumns = this.$store.state.userConfig['bet-columns'].disabled_columns
        this.columnsToDisplay = this.groupedBySportsBetColumns['Soccer']
    },
    methods: {
        filterSport(sport) {
            this.columnsToDisplay = []
            this.groupedBySportsBetColumns[sport].map(groupedColumns => {
                this.columnsToDisplay.push(groupedColumns)
            })
        },
        saveChanges() {
            let token = Cookies.get('access_token')
            let data = this.betColumns.map(betColumn => {
                return {
                    sport_odd_type_id: betColumn.id,
                    active: this.disabledBetColumns.includes(betColumn.id) ? false : true
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
                console.log(err)
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
