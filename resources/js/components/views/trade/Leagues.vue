<template>
    <div class="flex flex-col bg-white shadow-lg">
        <div class="flex justify-between bg-orange-500 text-white">
            <a href="#" class="text-sm uppercase py-2 px-3 leagueSchedule" :class="{'bg-orange-400 shadow-xl': selectedLeagueSchedMode === leagueSchedMode}" @click="selectLeagueSchedMode(leagueSchedMode)" v-for="(leagueSchedMode, index) in leagueSchedModes" :key="index">{{leagueSchedMode}} &nbsp; <span v-if="leagues">({{leagues[leagueSchedMode].length}})</span></a>
        </div>

        <div class="flex justify-center" v-if="leagues===null">
            <p class="text-sm p-3">No leagues available for this sport.</p>
        </div>

        <div class="flex flex-col leaguesList" v-else>
            <a href="#" class="text-sm capitalize py-1 px-3 w-full league" :class="[selectedLeagues.includes(league.name) ? 'bg-gray-900 shadow-xl text-white selectedLeague' : 'text-gray-700']" @click="selectLeague(league.name)" v-for="(league, index) in displayedLeagues" :key="index">{{league.name}} &nbsp; ({{league.match_count}})</a>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'

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
        ...mapState('trade', ['selectedLeague', 'selectedSport'])
    },
    mounted() {
        this.getLeagues()
    },
    methods: {
        getLeagues() {
            this.$store.dispatch('trade/getInitialLeagues')
            .then(response => {
                this.leagues = response
                this.modifyLeaguesFromSocket()
                this.filterLeaguesBySched(this.selectedLeagueSchedMode)
            })
        },
        filterLeaguesBySched(schedMode) {
            this.displayedLeagues = this.leagues[schedMode].map(league => {
                return league
            })
        },
        modifyLeaguesFromSocket() {
            this.$socket.send(`getAdditionalLeagues_${this.selectedSport}`)
            this.$socket.send(`getSelectedLeagues_${this.selectedSport}`)
            this.$socket.send(`getForRemovalLeagues_${this.selectedSport}`)
            this.$options.sockets.onmessage = (response) => {
                if (getSocketKey(response.data) === 'getAdditionalLeagues') {
                    if(getSocketValue(response.data, 'getAdditionalLeagues') != '') {
                        let additionalLeagues = getSocketValue(response.data, 'getAdditionalLeagues')
                        this.leagueSchedModes.forEach(sched => {
                            if(sched in additionalLeagues) {
                                additionalLeagues[sched].map(league => {
                                    this.leagues[sched].push(league)
                                })
                            }
                        })
                    }
                } else if (getSocketKey(response.data) === 'getSelectedLeagues') {
                    if(getSocketValue(response.data, 'getSelectedLeagues') != '') {
                        let selectedLeagues = getSocketValue(response.data, 'getSelectedLeagues')
                        selectedLeagues.map(league => {
                            this.selectedLeagues.push(league)
                        })
                    }
                } else if (getSocketKey(response.data) === 'getForRemovalLeagues') {
                    if(getSocketValue(response.data, 'getForRemovalLeagues') != '') {
                        let removalLeagues = getSocketValue(response.data, 'getForRemovalLeagues')
                        this.leagueSchedModes.forEach(sched => {
                            if(sched in removalLeagues) {
                                removalLeagues[sched].map(removalLeague => {
                                    this.leagues[sched] = this.leagues[sched].filter(league => league.name != removalLeague)
                                })
                            }
                        })
                    }
                }
                this.filterLeaguesBySched(this.selectedLeagueSchedMode)
            }
        },
        selectLeagueSchedMode(schedMode) {
            this.$store.commit('trade/SET_SELECTED_LEAGUE', null)
            this.selectedLeagueSchedMode = schedMode
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
