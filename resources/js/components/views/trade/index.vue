<template>
    <div class="trade">
        <div class="flex" v-adjust-trade-window-height="isBetBarOpen">
            <div class="w-1/6">
                <div class="fixed sidebar bg-gray-800 w-1/6 h-screen pr-4">
                    <Wallet></Wallet>
                    <Watchlist :watchlist="events.watchlist"></Watchlist>
                    <Sports></Sports>
                </div>
            </div>

            <div class="w-5/6 gameWindow">
                <Columns></Columns>
                <div class="gameScheds pb-4">
                    <Games gameSchedType="watchlist" :games="events.watchlist"></Games>
                    <Games gameSchedType="in-play" :games="events.inplay"></Games>
                    <Games gameSchedType="today" :games="events.today"></Games>
                    <Games gameSchedType="early" :games="events.early"></Games>
                </div>
            </div>
        </div>
        <Betbar></Betbar>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import _ from 'lodash'
import Cookies from 'js-cookie'
import Sports from './Sports'
import Wallet from './Wallet'
import Watchlist from './Watchlist'
import Columns from './Columns'
import Games from './Games'
import Betbar from './Betbar'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'

export default {
    components: {
        Sports,
        Wallet,
        Watchlist,
        Columns,
        Games,
        Betbar
    },
    head: {
        title() {
            return {
                inner: 'Trade'
            }
        }
    },
    computed: {
        ...mapState('trade', ['isBetBarOpen', 'oddsTypeBySport', 'eventsList', 'events'])
    },
    mounted() {
        this.getWatchlist()
        this.getUserTradeLayout()
        this.getEvents()
    },
    watch: {
        eventsList() {
            this.getForRemovalEvents()
            this.getUpdatedOdds()
        }
    },
    methods: {
        getUserTradeLayout() {
            this.$store.dispatch('settings/getUserSettingsConfig', 'trade-page')
            .then(response => {
                this.$store.commit('trade/SET_TRADE_LAYOUT', response.trade_layout)
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        },
        getWatchlist() {
            this.$socket.send('getWatchlist')
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) ===  'getWatchlist') {
                    let watchlist = getSocketValue(response.data, 'getWatchlist')
                    let watchlistLeagues = _.uniq(watchlist.map(event => event.league_name))
                    let watchlistObject = {}
                    watchlistLeagues.map(league => {
                        watchlist.map(event => {
                            if(event.league_name === league) {
                                if(typeof(watchlistObject[league]) == "undefined") {
                                    watchlistObject[league] = []
                                }
                                watchlistObject[league].push(event)
                            }
                        })
                    })
                    this.events.watchlist = watchlistObject
                }
            })
        },
        getEvents() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getEvents') {
                    let receivedEvents = getSocketValue(response.data, 'getEvents')
                    receivedEvents.map(event => {
                        this.$store.commit('trade/SET_EVENTS_LIST', event)
                    })
                    let eventsSchedule = _.uniq(this.eventsList.map(event => event.game_schedule))
                    let eventsLeague = _.uniq(this.eventsList.map(event => event.league_name))
                    let eventObject = {}
                    eventsSchedule.map(schedule => {
                        eventsLeague.map(league => {
                            this.eventsList.map(event => {
                                if(event.game_schedule === schedule && event.league_name === league) {
                                    if(typeof(eventObject[schedule]) == "undefined") {
                                        eventObject[schedule] = {}
                                    }

                                    if(typeof(eventObject[schedule][league]) == "undefined") {
                                        eventObject[schedule][league] = []
                                    }
                                    eventObject[schedule][league].push(event)
                                }
                            })
                        })
                    })
                    Object.keys(eventObject).map(schedule => {
                        this.$store.commit('trade/SET_EVENTS', { schedule: schedule, events: eventObject[schedule] })
                    })
                }
            })
        },
        getForRemovalEvents() {
            this.$socket.send('getForRemovalEvents')
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getForRemovalEvents') {
                    let removedEvents = getSocketValue(response.data, 'getForRemovalEvents')
                    this.eventsList.map(event => {
                        removedEvents.map(removedEvent => {
                            if(event.uid === removedEvent) {
                                this.$store.commit('trade/REMOVE_EVENT', { schedule: event.game_schedule, removedLeague: event.league_name, removedEvent: removedEvent })
                                this.$store.commit('trade/REMOVE_FROM_EVENT_LIST', { type: 'uid', data: removedEvent })

                                if(this.events[event.game_schedule][event.league_name].length === 0) {
                                    this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: event.game_schedule, league: event.league_name })
                                    this.$delete(this.events[event.game_schedule], event.league_name)
                                }
                            }
                        })
                    })
                }
            })
        },
        getUpdatedOdds() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getUpdatedOdds') {
                    let updatedOdds = getSocketValue(response.data, 'getUpdatedOdds')
                    let team = ['home', 'away', 'draw']
                    this.eventsList.map(event => {
                        updatedOdds.map(updatedOdd => {
                            this.oddsTypeBySport.map(oddType => {
                                team.map(team => {
                                    if(oddType in event.market_odds.main && team in event.market_odds.main[oddType]) {
                                        if(event.market_odds.main[oddType][team].market_id === updatedOdd.market_id && event.market_odds.main[oddType][team].odds != updatedOdd.odds) {
                                            event.market_odds.main[oddType][team].odds = updatedOdd.odds
                                        }
                                    }
                                })
                            })
                        })
                    })
                }
            })
        }
    },
    directives: {
        adjustTradeWindowHeight: {
            update(el, binding, vnode) {
                if(binding.value) {
                    el.style.height = 'calc(100vh - 256px)'
                    el.style.overflowY = 'auto'
                } else {
                    el.style.height = 'calc(100vh - 104px)'
                }
            }
        }
    }
}
</script>

<style lang="scss">
    .gameWindow {
        position: relative;
        overflow-x: hidden;
    }

    .gameScheds {
        margin-top: 52px;
    }
</style>
