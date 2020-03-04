<template>
    <div class="flex flex-col bg-white shadow-lg">
        <div class="flex justify-between bg-orange-500 text-white">
            <a href="#" class="text-sm uppercase py-2 px-3 leagueSchedule" :class="{'bg-orange-400 shadow-xl': selectedLeagueSchedMode === leagueSchedMode}" @click="selectLeagueSchedMode(leagueSchedMode)" v-for="(leagueSchedMode, index) in leagueSchedModes" :key="index">{{leagueSchedMode}} &nbsp; <span v-if="leagues">({{leagues[leagueSchedMode].length}})</span></a>
        </div>

        <div class="flex justify-center" v-if="checkIfLeaguesIsEmpty">
            <p class="text-sm p-3">No leagues/events available for this sport/schedule.</p>
        </div>

        <div class="flex flex-col leaguesList" v-else>
            <a href="#" class="text-sm capitalize py-1 px-3 w-full league" :class="[selectedLeagues.includes(league.name) ? 'bg-gray-900 shadow-xl text-white selectedLeague' : 'text-gray-700']" @click="selectLeague(league.name)" v-for="(league, index) in displayedLeagues" :key="index">{{league.name}} &nbsp; ({{league.match_count}})</a>
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
        ...mapState('trade', ['selectedLeagues', 'selectedSport']),
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
                            this.$store.commit('trade/SET_SELECTED_LEAGUES', league)
                            this.$socket.send(`getEvents_${league}`)
                        })
                    }
                } else if (getSocketKey(response.data) === 'getForRemovalLeagues') {
                    if(getSocketValue(response.data, 'getForRemovalLeagues') != '') {
                        let removalLeagues = getSocketValue(response.data, 'getForRemovalLeagues')
                        this.leagueSchedModes.map(sched => {
                            if(sched in removalLeagues) {
                                removalLeagues[sched].map(removalLeague => {
                                    this.leagues[sched] = this.leagues[sched].filter(league => league.name != removalLeague)
                                    this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', removalLeague)
                                })
                            }
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

            if(this.selectedLeagues.includes(league)) {
                this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', league)
                this.$store.commit('trade/REMOVE_FROM_EVENTS', { schedule: this.selectedLeagueSchedMode, removedLeague: league })
            } else {
                this.$store.commit('trade/SET_SELECTED_LEAGUES', league)
                this.$socket.send(`getEvents_${league}`)
            }

            axios.post('v1/trade/leagues/toggle', { data: league, sport_id: this.selectedSport }, { headers: { 'Authorization': `Bearer ${token}` } })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
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
