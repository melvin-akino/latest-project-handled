<template>
    <div class="flex flex-col bg-white shadow-xl ml-4">
        <div class="flex justify-between bg-orange-500 text-white">
            <a href="#" class="text-sm uppercase py-2 px-3 leagueSchedule" :class="{'bg-orange-400 shadow-xl': selectedLeagueSchedMode === leagueSchedMode}" @click="selectLeagueSchedMode(leagueSchedMode)" v-for="(leagueSchedMode, index) in leagueSchedModes" :key="index">{{leagueSchedMode}} &nbsp; ({{leagues[leagueSchedMode].length}})</a>
        </div>

        <div class="flex flex-col" v-adjust-leagues-height="isBetBarOpen" ref="leaguesList">
            <a href="#" class="text-sm capitalize py-1 px-6 w-full league" :class="{'bg-gray-900 shadow-xl text-white border-l-8 border-orange-500': selectedLeagues.includes(index)}" @click="selectLeague(index)" v-for="(league, index) in displayedLeagues" :key="index">{{league.league}} &nbsp; ({{league.gameCount}})</a>
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
        ...mapState('trade', ['selectedLeague', 'isBetBarOpen'])
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
    },
    directives: {
        adjustLeaguesHeight: {
            update(el, binding, vnode) {
                if (vnode.context.$refs.leaguesList.clientHeight >= 367) {
                    if (binding.value) {
                            el.style.height = '367px'
                            el.style.overflowY = 'auto'
                    } else {
                        el.style.height = '521px'
                    }
                } else {
                    el.style.height = '100%'
                }
            }
        }
    }
}
</script>

<style>

</style>
