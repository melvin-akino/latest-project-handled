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
                <a href="#" class="text-sm capitalize py-1 px-3 w-full league" :class="[selectedLeagues[selectedLeagueSchedMode].includes(league.name)  ? 'bg-gray-900 shadow-xl text-white selectedLeague' : 'text-gray-700']" @click="selectLeague(league.name)" v-for="(league, index) in displayedLeagues" :key="index">{{league.name}} &nbsp; ({{league.match_count}})</a>
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
        ...mapState('trade', ['selectedLeagues', 'selectedLeagueSchedMode', 'selectedSport', 'events', 'leagues', 'isLoadingLeagues']),
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
                        let selectedLeagues = getSocketValue(response.data, 'getSelectedLeagues')
                        this.leagueSchedModes.map(sched => {
                            if(sched in selectedLeagues) {
                                selectedLeagues[sched].map(selectedLeague => {
                                    this.$store.commit('trade/ADD_TO_SELECTED_LEAGUE', { schedule: sched, league: selectedLeague })
                                })
                            }
                        })
                    }
                }
            }
        },
        selectLeagueSchedMode(schedMode) {
            Cookies.set('leagueSchedMode', schedMode)
            this.$store.commit('trade/CHANGE_LEAGUE_SCHED_MODE', schedMode)
        },
        selectLeague(league) {
            let token = Cookies.get('mltoken')

            if(this.selectedLeagues[this.selectedLeagueSchedMode].includes(league)) {
                this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: this.selectedLeagueSchedMode, league: league })
                this.$store.commit('trade/REMOVE_FROM_EVENT_LIST', { league_name: league, game_schedule: this.selectedLeagueSchedMode })
                this.$store.dispatch('trade/toggleLeague', { action: 'remove', league_name: league, sport_id: this.selectedSport, schedule: this.selectedLeagueSchedMode  })
            } else {
                this.$store.commit('trade/ADD_TO_SELECTED_LEAGUE', { schedule: this.selectedLeagueSchedMode, league: league })
                this.$socket.send(`getEvents_${league}_${this.selectedLeagueSchedMode}`)
                this.$store.dispatch('trade/toggleLeague', { action: 'add', league_name: league, sport_id: this.selectedSport, schedule: this.selectedLeagueSchedMode  })
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
