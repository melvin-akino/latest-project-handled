<template>
    <div class="trade">
        <div class="flex">
            <Sports></Sports>
            <div class="flex flex-col bg-gray-800 w-3/12 h-screen xl:w-2/12 fixed sidebar">
                <div class="flex flex-col px-6 py-2">
                    <div class="flex justify-between">
                        <p class="text-white">Credit</p>
                        <p class="text-white">$ 1,000.00</p>
                    </div>
                    <div class="flex justify-between">
                        <p class="text-white">PL</p>
                        <p class="text-white">$ 500.00</p>
                    </div>
                    <div class="flex justify-between">
                        <p class="text-white">Open Orders</p>
                        <p class="text-white">$ 100.00</p>
                    </div>
                </div>
                <div class="show-watchlist">
                    <div class="text-white text-center bg-orange-500 py-1">Watchlist <i class="fas fa-play text-xs"></i></div>
                </div>
                <Leagues></Leagues>
            </div>

            <div class="w-3/12"></div>
            <div class="w-full h-full">
                <div class="flex flex-wrap justify-around items-center h-8 pr-10 bg-gray-800 text-white text-xs">
                    <p>Description</p>
                    <p>Sport</p>
                    <p>Score & Schedule</p>
                    <p v-for="column in columnsToDisplay" :key="column.sport_odd_type_id">{{column.type}}</p>
                    <button class="bg-orange-500 px-4 py-1 hover:bg-orange-600 fixed right-0 mr-1">Add <i class="fas fa-plus"></i></button>
                </div>
                <div class="gameScheds pt-4">
                    <Games gameSchedType="watchlist"></Games>
                    <Games gameSchedType="in-play"></Games>
                    <Games gameSchedType="today"></Games>
                    <Games gameSchedType="early"></Games>
                </div>
            </div>
        </div>
        <div class="betbar flex flex-col w-full bg-gray-900 left-0 bottom-0 fixed z-10 overflow-y-scroll" :class="[isBetBarOpen ? 'h-48': 'h-10']">
            <div class="text-center text-white h-10 pt-2 cursor-pointer" :class="{'border-b border-white': isBetBarOpen}" @click="isBetBarOpen = !isBetBarOpen">
                Recent Orders
                <span v-show="isBetBarOpen"><i class="fas fa-chevron-down"></i></span>
                <span v-show="!isBetBarOpen"><i class="fas fa-chevron-up"></i></span>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import Sports from './Sports'
import Leagues from './Leagues'
import Games from './Games'

export default {
    components: {
        Sports,
        Leagues,
        Games
    },
    data() {
        return {
            isBetBarOpen: false,
            betColumns: [],
            disabledBetColumns: [],
            filteredColumnsBySport: []
        }
    },
    head: {
        title() {
            return {
                inner: 'Trade'
            }
        }
    },
    computed: {
        ...mapState('trade', ['selectedSport']),
        columnsToDisplay() {
            let columns = this.filteredColumnsBySport.filter(column => {
                if (!this.disabledBetColumns.includes(column.sport_odd_type_id)) {
                    return column
                }
            })
            return columns
        }
    },
    mounted() {
        this.$store.dispatch('settings/getGeneralSettingsConfig')
        this.getUserConfigForBetColumns(this.selectedSport)
        this.getBetColumns(this.selectedSport)
    },
    methods: {
        getUserConfigForBetColumns() {
            let token = Cookies.get('access_token')

            axios.get('v1/user/settings/bet-columns', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => this.disabledBetColumns = response.data.data.disabled_columns)
            .catch(err => console.log(err))
        },
        getBetColumns() {
            let token = Cookies.get('access_token')

            axios.get('v1/sports/odds', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.betColumns = response.data.data
                response.data.data.filter(column => column.sport_id === this.selectedSport).map(column => this.filteredColumnsBySport = column.odds)
            })
            .catch(err => console.log(err))
        },
    }
}
</script>

<style lang="scss">
    .sidebar {
        margin-left: 63px;
    }

    .betbar {
        transition: all 0.3s;
    }
</style>
