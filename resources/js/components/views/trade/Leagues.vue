<template>
    <div class="flex flex-col px-0 py-4">
        <div class="flex justify-around px-3">
            <a href="#" class="text-sm uppercase py-2 px-4 text-white leagueSchedule" :class="{'bg-orange-500 rounded-lg shadow-lg font-bold': selectedLeagueSchedMode === leagueSchedMode}" @click="selectLeagueSchedMode(leagueSchedMode)" v-for="(leagueSchedMode, index) in leagueSchedModes" :key="index">{{leagueSchedMode}} &nbsp; ({{leagues[leagueSchedMode].length}})</a>
        </div>

        <div class="flex flex-col pt-2 px-3">
            <a href="#" class="text-white text-xs capitalize my-1 py-1 px-4 w-full league" :class="{'bg-white rounded-lg shadow-lg font-bold text-gray-700': selectedLeagues.includes(index)}" @click="selectLeague(index)" v-for="(league, index) in displayedLeagues" :key="index">{{league.league}} &nbsp; ({{league.gameCount}})</a>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
    data() {
        return {
            leagueSchedModes: ['IN-PLAY', 'TODAY', 'EARLY'],
            selectedLeagueSchedMode: 'TODAY',
            leagues: this.$store.state.trade.leaguesData,
            displayedLeagues: [],
            selectedLeagues: []
        }
    },
    computed: {
        ...mapState('trade', ['selectedLeague'])
    },
    mounted() {
        this.filterLeaguesBySched(this.selectedLeagueSchedMode)
    },
    methods: {
        filterLeaguesBySched(schedMode) {
            this.displayedLeagues = this.leagues[schedMode].map(league => {
                return {
                    league: Object.keys(league).join(),
                    gameCount: Object.values(league).join()
                }
            })
        },
        selectLeagueSchedMode(schedMode) {
            this.$store.commit('trade/SET_SELECTED_LEAGUE', null)
            this.selectedLeagueSchedMode = schedMode
            this.selectedLeagues = []
            this.filterLeaguesBySched(schedMode)
        },
        selectLeague(league) {
            this.$store.commit('trade/SET_SELECTED_LEAGUE', league)

            if(this.selectedLeagues.includes(league)) {
                this.selectedLeagues = this.selectedLeagues.filter((selectedLeague, index) => index != league)
            } else {
                this.selectedLeagues.push(league)
            }
        }
    }
}
</script>

<style>

</style>
