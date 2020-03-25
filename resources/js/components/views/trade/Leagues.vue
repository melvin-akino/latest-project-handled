<template>
    <div class="flex flex-col bg-white shadow-lg">
        <div class="flex justify-between bg-orange-500 text-white">
            <a href="#" class="text-sm uppercase py-2 px-3 leagueSchedule" :class="{'bg-orange-400 shadow-xl': selectedLeagueSchedMode === leagueSchedMode}" @click="selectLeagueSchedMode(leagueSchedMode)" v-for="(leagueSchedMode, index) in leagueSchedModes" :key="index">{{leagueSchedMode}} &nbsp; <span v-if="leagues">({{leagues[leagueSchedMode].length}})</span></a>
        </div>

        <div class="flex justify-center" v-if="checkIfLeaguesIsEmpty">
            <p class="text-sm p-3">No leagues/events available for this sport/schedule.</p>
        </div>

        <div class="flex flex-col leaguesList" v-else>
            <a href="#" class="text-sm capitalize py-1 px-3 w-full league" :class="[selectedLeagues[selectedLeagueSchedMode].includes(league.name)  ? 'bg-gray-900 shadow-xl text-white selectedLeague' : 'text-gray-700']" @click="selectLeague(league.name)" v-for="(league, index) in displayedLeagues" :key="index">{{league.name}} &nbsp; ({{league.match_count}})</a>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import _ from 'lodash'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'

export default {
    data() {
        return {
            leagues: null,
            leagueSchedModes: ['inplay', 'today', 'early'],
            selectedLeagueSchedMode: null,
            displayedLeagues: []
        }
    },
    computed: {
        ...mapState('trade', ['selectedLeagues', 'selectedSport', 'events']),
        checkIfLeaguesIsEmpty() {
            if(this.leagues != null) {
                return _.isEmpty(this.leagues[this.selectedLeagueSchedMode])
            }
        }
    },
    mounted() {
        this.getLeagues()
        this.selectedLeagueSchedMode = Cookies.get('leagueSchedMode') || 'today'
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
            if(this.leagues != null) {
                this.displayedLeagues = this.leagues[schedMode].map(league => {
                    return league
                })
            }
        },
        modifyLeaguesFromSocket() {
            this.$socket.send(`getSelectedLeagues_${this.selectedSport}`)
            this.$options.sockets.onmessage = (response) => {
                if (getSocketKey(response.data) === 'getAdditionalLeagues') {
                    if(getSocketValue(response.data, 'getAdditionalLeagues') != '') {
                        let additionalLeagues = getSocketValue(response.data, 'getAdditionalLeagues')
                        this.leagueSchedModes.map(sched => {
                            additionalLeagues.map(additionalLeague => {
                                if(sched == additionalLeague.schedule) {
                                    this.leagues[sched].push(additionalLeague)
                                }
                            })
                        })
                    }
                } else if (getSocketKey(response.data) === 'getSelectedLeagues') {
                    if(getSocketValue(response.data, 'getSelectedLeagues') != '') {
                        let selectedLeagues = getSocketValue(response.data, 'getSelectedLeagues')
                        this.leagueSchedModes.map(sched => {
                            if(sched in selectedLeagues) {
                                selectedLeagues[sched].map(selectedLeague => {
                                    this.$store.commit('trade/ADD_TO_SELECTED_LEAGUE', { schedule: sched, league: selectedLeague })
                                })
                            }
                        })
                    }
                } else if (getSocketKey(response.data) === 'getForRemovalLeagues') {
                    if(getSocketValue(response.data, 'getForRemovalLeagues') != '') {
                        let removalLeagues = getSocketValue(response.data, 'getForRemovalLeagues')
                        this.leagueSchedModes.map(sched => {
                            removalLeagues.map(removalLeague => {
                                if(sched == removalLeague.schedule) {
                                    this.leagues[sched] = this.leagues[sched].filter(league => league.name != removalLeague.name)
                                    this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: sched, league: removalLeague.name })
                                    if(removalLeague.name in this.events.watchlist) {
                                        this.$store.commit('trade/REMOVE_FROM_EVENTS', { schedule: 'watchlist', removedLeague: removalLeague.name  })
                                    }
                                }
                            })
                        })
                    }
                }
                this.filterLeaguesBySched(this.selectedLeagueSchedMode)
            }
        },
        selectLeagueSchedMode(schedMode) {
            Cookies.set('leagueSchedMode', schedMode)
            this.selectedLeagueSchedMode = schedMode
            this.filterLeaguesBySched(schedMode)
        },
        selectLeague(league) {
            let token = Cookies.get('mltoken')
            if(this.selectedLeagues[this.selectedLeagueSchedMode].includes(league)) {
                this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: this.selectedLeagueSchedMode, league: league })
                this.$store.commit('trade/REMOVE_FROM_EVENTS', { schedule: this.selectedLeagueSchedMode, removedLeague: league })
                this.$store.commit('trade/REMOVE_FROM_EVENT_LIST', { type: 'league_name', data: league })
                this.$store.commit('trade/REMOVE_FROM_ALL_EVENT_LIST', { type: 'league_name', data: league })
            } else {
                this.$store.commit('trade/ADD_TO_SELECTED_LEAGUE', { schedule: this.selectedLeagueSchedMode, league: league })
                this.$socket.send(`getEvents_${league}_${this.selectedLeagueSchedMode}`)
            }
            this.$store.dispatch('trade/toggleLeague', { league_name: league, sport_id: this.selectedSport, schedule: this.selectedLeagueSchedMode  })
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
        max-height:302px;
        overflow-y:auto;
    }
</style>
