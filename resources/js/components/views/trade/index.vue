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
                    <Games gameSchedType="inplay" :games="events.inplay"></Games>
                    <Games gameSchedType="today" :games="events.today"></Games>
                    <Games gameSchedType="early" :games="events.early"></Games>
                </div>
            </div>
        </div>
        <Betslip v-for="market_id in openedBetSlips" :key="market_id" :market_id="market_id"></Betslip>
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
import Betslip from './BetSlip'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'

export default {
    components: {
        Sports,
        Wallet,
        Watchlist,
        Columns,
        Games,
        Betbar,
        Betslip
    },
    head: {
        title() {
            return {
                inner: 'Trade'
            }
        }
    },
    computed: {
        ...mapState('trade', ['isBetBarOpen', 'selectedSport', 'oddsTypeBySport', 'allEventsList', 'eventsList', 'events', 'openedBetSlips'])
    },
    mounted() {
        this.$store.dispatch('trade/getInitialEvents')
        this.getWatchlist()
        this.getUserTradeLayout()
        this.getEvents()
    },
    watch: {
        allEventsList() {
            this.getAdditionalEvents()
            this.getForRemovalEvents()
            this.getUpdatedOdds()
            this.getUpdatedEventsSchedule()
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
                    this.$store.commit('trade/SET_WATCHLIST', watchlistObject)
                }
            })
        },
        getEvents() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getEvents') {
                    let receivedEvents = getSocketValue(response.data, 'getEvents')
                    receivedEvents.map(receivedEvent => {
                        let eventsListCheckUID = this.eventsList.findIndex(event => event.uid === receivedEvent.uid)
                        let allEventsListCheckUID = this.allEventsList.findIndex(event => event.uid === receivedEvent.uid)
                        if(receivedEvent.sport_id == this.selectedSport) {
                            if(eventsListCheckUID === -1) {
                                this.$store.commit('trade/SET_EVENTS_LIST', receivedEvent)
                            }

                            if(allEventsListCheckUID === -1) {
                                this.$store.commit('trade/SET_ALL_EVENTS_LIST', receivedEvent)
                            }
                        }
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
        getAdditionalEvents() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getAdditionalEvents') {
                    let additionalEvents = getSocketValue(response.data, 'getAdditionalEvents')
                    additionalEvents.map(event => {
                        this.$store.commit('trade/SET_EVENTS_LIST', event)
                        this.$store.commit('trade/SET_ALL_EVENTS_LIST', event)
                        this.$store.commit('trade/ADD_TO_EVENTS', { schedule: event.game_schedule, league: event.league_name, event: event })
                    })
                }
            })
        },
        getForRemovalEvents() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getForRemovalEvents') {
                    let removedEvents = getSocketValue(response.data, 'getForRemovalEvents')
                    this.allEventsList.map(event => {
                        removedEvents.map(removedEvent => {
                            if(event.uid === removedEvent) {
                                this.$store.commit('trade/REMOVE_EVENT', { schedule: event.game_schedule, removedLeague: event.league_name, removedEvent: removedEvent })
                                this.$store.commit('trade/REMOVE_FROM_EVENT_LIST', { type: 'uid', data: removedEvent })
                                this.$store.commit('trade/REMOVE_FROM_ALL_EVENT_LIST', { type: 'uid', data: removedEvent })
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
        getUpdatedEventsSchedule() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getUpdatedEventsSchedule') {
                    let updatedEventSchedule = getSocketValue(response.data, 'getUpdatedEventsSchedule')
                    this.allEventsList.map(event => {
                        if(event.uid === updatedEventSchedule.uid && event.game_schedule != updatedEventSchedule.game_schedule) {
                            event.game_schedule = updatedEvent.game_schedule
                            this.$socket.send(`getEvents_${event.league_name}_${updatedEvent.game_schedule}`)
                        }
                    })
                }
            })
        },
        getUpdatedOdds() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getUpdatedOdds') {
                    let updatedOdds = getSocketValue(response.data, 'getUpdatedOdds')
                    let team = ['HOME', 'AWAY', 'DRAW']
                    this.allEventsList.map(event => {
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
    },
    beforeRouteLeave(to, from, next) {
        this.$store.commit('trade/CLEAR_EVENTS_LIST')
        this.$store.commit('trade/CLEAR_ALL_EVENTS_LIST')
        next()
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
