<template>
    <div class="flex flex-col bg-white shadow-lg">
        <div class="flex justify-between bg-orange-500 text-white">
            <a href="#" class="text-sm uppercase p-2 leagueSchedule" :class="{'bg-orange-400 shadow-xl': selectedLeagueSchedMode === leagueSchedMode}" @click="selectLeagueSchedMode(leagueSchedMode)" v-for="(leagueSchedMode, index) in leagueSchedModes" :key="index">{{leagueSchedMode}} &nbsp; <span v-if="leagues[leagueSchedMode]">({{leagues[leagueSchedMode].length}})</span></a>
        </div>
        <div v-if="isLoadingLeagues" class="flex justify-center">
            <p class="text-sm p-3">Loading leagues <i class="fas fa-circle-notch fa-spin"></i></p>
        </div>
        <div class="leagues" v-else>
            <div class="flex justify-center" v-if="checkIfLeaguesIsEmpty">
                <p class="text-sm p-3">No leagues/events available for this sport/schedule.</p>
            </div>

            <div class="flex flex-col leaguesList" v-else>
                <a href="#" class="text-sm capitalize py-1 px-3 w-full league" :class="[selectedLeagues[selectedLeagueSchedMode].map(league => league.master_league_id).includes(league.master_league_id)  ? 'bg-gray-900 shadow-xl text-white selectedLeague' : 'text-gray-700']" @click="selectLeague(league)" v-for="(league, index) in displayedLeagues" :key="index">{{league.name}} &nbsp; ({{league.match_count}})</a>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState, mapGetters  } from 'vuex'
import Cookies from 'js-cookie'
import _ from 'lodash'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'

export default {
    data() {
        return {
            leagueSchedModes: ['inplay', 'today', 'early'],
        }
    },
    computed: {
        ...mapState('trade', ['leagues', 'selectedLeagues', 'selectedLeagueSchedMode', 'selectedSport', 'isLoadingLeagues', 'eventsList']),
        ...mapGetters('trade', ['displayedLeagues']),
        checkIfLeaguesIsEmpty() {
            if(!_.isEmpty(this.leagues)) {
                return _.isEmpty(this.leagues[this.selectedLeagueSchedMode])
            }
        }
    },
    mounted() {
        this.modifyLeaguesFromSocket()
    },
    methods: {
        modifyLeaguesFromSocket() {
            this.$options.sockets.onmessage = (response) => {
                if (getSocketKey(response.data) === 'getUpdatedLeagues') {
                    this.$store.dispatch('trade/getInitialLeagues', true)
                } else if (getSocketKey(response.data) === 'getSelectedLeagues') {
                    if(getSocketValue(response.data, 'getSelectedLeagues') != '') {
                        let getSelectedLeagues = getSocketValue(response.data, 'getSelectedLeagues')
                        Object.keys(getSelectedLeagues).map(schedule => {
                            let selectedLeaguesIds = this.selectedLeagues[schedule].map(league => league.master_league_id)
                            let newSelectedLeagues = getSelectedLeagues[schedule]
                            let leagueIds = this.leagues[schedule].map(league => league.master_league_id)
                            newSelectedLeagues.map(league => {
                                if(!selectedLeaguesIds.includes(league.master_league_id)) {
                                    if(leagueIds.includes(league.master_league_id)) {
                                        this.$store.commit('trade/ADD_TO_SELECTED_LEAGUE', { schedule: schedule, league: league })
                                    } else {
                                        this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: schedule, league: league.master_league_id })
                                        this.$store.dispatch('trade/toggleLeague', { action: 'remove', sport_id: this.selectedSport, schedule: schedule, master_league_id: league.master_league_id  })
                                    }
                                }
                            })
                        })
                    }
                } else if (getSocketKey(response.data) === 'getSidebarLeagues') {
                    let getSidebarLeagues = getSocketValue(response.data, 'getSidebarLeagues')
                    Object.keys(getSidebarLeagues).map(schedule => {
                        this.$socket.send('getWatchlist')
                        this.selectedLeagues[schedule].map(league => {
                            this.$socket.send(`getEvents_${league.master_league_id}_${schedule}`)
                        })
                        this.eventsList.map(event => {
                            if(event.hasOwnProperty('market_odds') && event.game_schedule == schedule) {
                                if(event.hasOwnProperty('watchlist')) {
                                    if(event.market_odds.hasOwnProperty('other')) {
                                        this.$socket.send(`getWatchlist_${event.uid}_withOtherMarket`)
                                    }
                                } else {
                                    if(event.market_odds.hasOwnProperty('other')) {
                                        this.$socket.send(`getEvents_${event.master_league_id}_${event.game_schedule}_${event.uid}_withOtherMarket`)
                                    }
                                }
                            }
                        })

                        let leagueIds = this.leagues[schedule].map(league => league.master_league_id)
                        let newLeagueIds = getSidebarLeagues[schedule].map(league => league.master_league_id)
                        let selectedLeaguesIds = this.selectedLeagues[schedule].map(league => league.master_league_id)
                        leagueIds.map(master_league_id => {
                            if(!newLeagueIds.includes(master_league_id)) {
                                if(selectedLeaguesIds.includes(master_league_id)) {
                                    this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: schedule, league: master_league_id })
                                    this.$store.dispatch('trade/toggleLeague', { action: 'remove', sport_id: this.selectedSport, schedule: schedule, master_league_id: master_league_id  })
                                }
                                this.$store.commit('trade/REMOVE_FROM_EVENT_LIST', { master_league_id: master_league_id, game_schedule: schedule })
                            }
                        })
                        this.$store.commit('trade/SET_LEAGUES_BY_SCHEDULE', { schedule: schedule, leagues: getSidebarLeagues[schedule] })
                    })
                }
            }
        },
        selectLeagueSchedMode(schedMode) {
            Cookies.set('leagueSchedMode', schedMode)
            this.$store.commit('trade/CHANGE_LEAGUE_SCHED_MODE', schedMode)
        },
        selectLeague(league) {
            let selectedLeaguesIds = this.selectedLeagues[this.selectedLeagueSchedMode].map(league => league.master_league_id)
            if(selectedLeaguesIds.includes(league.master_league_id)) {
                this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: this.selectedLeagueSchedMode, league: league.master_league_id })
                this.$store.commit('trade/REMOVE_FROM_EVENT_LIST', { master_league_id: league.master_league_id, game_schedule: this.selectedLeagueSchedMode })
                this.$store.dispatch('trade/toggleLeague', { action: 'remove', sport_id: this.selectedSport, schedule: this.selectedLeagueSchedMode, master_league_id: league.master_league_id  })
            } else {
                this.$store.commit('trade/ADD_TO_SELECTED_LEAGUE', { schedule: this.selectedLeagueSchedMode, league: league })
                this.$socket.send(`getEvents_${league.master_league_id}_${this.selectedLeagueSchedMode}`)
                this.$store.dispatch('trade/toggleLeague', { action: 'add', sport_id: this.selectedSport, schedule: this.selectedLeagueSchedMode, master_league_id: league.master_league_id  })
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
