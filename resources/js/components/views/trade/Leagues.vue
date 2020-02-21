<template>
    <div class="flex flex-col bg-white shadow-lg">
        <div class="flex justify-between bg-orange-500 text-white">
            <a href="#" class="text-sm uppercase py-2 px-3 leagueSchedule" :class="{'bg-orange-400 shadow-xl': selectedLeagueSchedMode === leagueSchedMode}" @click="selectLeagueSchedMode(leagueSchedMode)" v-for="(leagueSchedMode, index) in leagueSchedModes" :key="index">{{leagueSchedMode}} &nbsp; <span v-if="leagues">({{leagues[leagueSchedMode].length}})</span></a>
        </div>

        <div class="flex flex-col leaguesList">
            <a href="#" class="text-sm capitalize py-1 px-3 w-full league" :class="[selectedLeagues.includes(index) ? 'bg-gray-900 shadow-xl text-white selectedLeague' : 'text-gray-700']" @click="selectLeague(index)" v-for="(league, index) in displayedLeagues" :key="index">{{league.name}} &nbsp; ({{league.match_count}})</a>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'

export default {
    data() {
        return {
            leagues: null,
            leagueSchedModes: ['inplay', 'today', 'early'],
            selectedLeagueSchedMode: 'today',
            displayedLeagues: [],
            selectedLeagues: []
        }
    },
    computed: {
        ...mapState('trade', ['selectedLeague'])
    },
    mounted() {
        this.getLeagues()
    },
    methods: {
        getLeagues() {
            let token = Cookies.get('mltoken')

            axios.get('v1/trade/leagues', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.leagues = response.data.data
                this.filterLeaguesBySched(this.selectedLeagueSchedMode)
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        },
        filterLeaguesBySched(schedMode) {
            this.displayedLeagues = this.leagues[schedMode].map(league => {
                return league
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
    .selectedLeague {
        -webkit-box-shadow: inset 8px 0px 0px 0px rgba(237,137,54,1);
        -moz-box-shadow: inset 8px 0px 0px 0px rgba(237,137,54,1);
        box-shadow: inset 8px 0px 0px 0px rgba(237,137,54,1);
    }

    .leaguesList {
        max-height:380px;
        overflow-y:auto;
    }
</style>
