<template>
    <div class="mt-12 mb-12">
        <p class="mb-12 text-sm">The types of bet that are shown in the table of the trading screen</p>
        <form @submit.prevent="saveChanges">
            <div class="flex justify-evenly items-center">
                <div class="soccer w-1/4">
                    <!-- <p class="text-xl mb-4">{{soccerBetColumns[0].sport}}</p> -->
                    <div class="mb-12" v-for="soccerBetColumn in soccerBetColumns" :key="soccerBetColumn.id">
                        <label class="text-sm relative flex items-center">
                            <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="soccerBetColumn.id" v-model="disabledBetColumns">
                            <span class="absolute shadow shadow-lg w-6 h-6 rounded-full" :class="[!disabledBetColumns.includes(soccerBetColumn.id) ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                            {{soccerBetColumn.type}}
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
import Swal from 'sweetalert2'
export default {
    data() {
        return {
            betColumns:[],
            disabledBetColumns: [],

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
        soccerBetColumns() {
            return this.betColumns.filter(betColumn => betColumn.sport_id === 1)
        }
    },
    mounted() {
        this.betColumns = this.$store.state.userSportsOddTypes
        this.disabledBetColumns = this.$store.state.userConfig["bet-columns"].disabled_columns
    },
    methods:{
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
    .on-switch {
        left: 24px;
    }
</style>
