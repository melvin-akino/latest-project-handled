<template>
    <div class="flex flex-col bg-white shadow-lg">
        <div class="flex justify-between bg-orange-500 text-white">
            <a href="#" class="text-sm uppercase py-2 px-3 leagueSchedule" :class="{'bg-orange-400 shadow-xl': selectedLeagueSchedMode === leagueSchedMode}" @click="selectLeagueSchedMode(leagueSchedMode)" v-for="(leagueSchedMode, index) in leagueSchedModes" :key="index">{{leagueSchedMode}} &nbsp; ({{leagues[leagueSchedMode].length}})</a>
        </div>

        <div class="flex flex-col" v-adjust-leagues-height="isBetBarOpen" ref="leaguesList">
            <a href="#" class="text-sm capitalize py-1 px-6 w-full league" :class="{'bg-gray-900 shadow-xl text-white selectedLeague': selectedLeagues.includes(index)}" @click="selectLeague(index)" v-for="(league, index) in displayedLeagues" :key="index">{{league.league}} &nbsp; ({{league.gameCount}})</a>
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
                if (vnode.context.$refs.leaguesList.clientHeight >= 231) {
                    if (binding.value) {
                            el.style.height = '231px'
                            el.style.overflowY = 'auto'
                    } else {
                        el.style.height = '382px'
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
    .selectedLeague {
        -webkit-box-shadow: inset 8px 0px 0px 0px rgba(237,137,54,1);
        -moz-box-shadow: inset 8px 0px 0px 0px rgba(237,137,54,1);
        box-shadow: inset 8px 0px 0px 0px rgba(237,137,54,1);
    }
</style>
