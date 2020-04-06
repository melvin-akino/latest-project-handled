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
        <Betslip v-for="odd in openedBetSlips" :key="odd.market_id" :odd_details="odd"></Betslip>
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
        ...mapState('trade', ['isBetBarOpen', 'selectedSport', 'oddsTypeBySport', 'allEventsList', 'eventsList', 'events', 'openedBetSlips', 'tradePageSettings'])
    },
    mounted() {
        this.$store.dispatch('trade/getInitialEvents')
        this.$store.dispatch('trade/getTradePageSettings')
        this.getWatchlist()
        this.getUserTradeLayout()
        this.getEvents()
        this.getUpdatedEventsSchedule()
    },
    watch: {
        allEventsList() {
            this.getAdditionalEvents()
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
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) ===  'getWatchlist') {
                    let watchlist = getSocketValue(response.data, 'getWatchlist')
                    let watchlistLeagues = _.uniq(watchlist.map(event => event.league_name))
                    let watchlistStartTime = _.uniq(watchlist.map(event => `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`))
                    let watchlistObject = {}
                    if(this.tradePageSettings.sort_event == 1) {
                        watchlistLeagues.map(league => {
                            watchlist.map(event => {
                                this.$delete(event.market_odds, 'other')
                                if(event.league_name === league) {
                                    if(typeof(watchlistObject[league]) == "undefined") {
                                        watchlistObject[league] = []
                                    }
                                    watchlistObject[league].push(event)
                                }
                            })
                        })
                    } else if(this.tradePageSettings.sort_event == 2) {
                        watchlistStartTime.map(startTime => {
                            watchlist.map(event => {
                                this.$delete(event.market_odds, 'other')
                                let eventSchedLeague = `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`
                                if(eventSchedLeague === startTime) {
                                    if(typeof(watchlistObject[startTime]) == "undefined") {
                                        watchlistObject[startTime] = []
                                    }
                                    watchlistObject[startTime].push(event)
                                }
                            })
                        })
                    }
                    let sortedWatchlistObject = {}
                    Object.keys(watchlistObject).sort().map(league => {
                        if(typeof(sortedWatchlistObject[league]) == "undefined") {
                            sortedWatchlistObject[league] = []
                        }
                        sortedWatchlistObject[league] = watchlistObject[league]
                    })
                    this.$store.commit('trade/SET_WATCHLIST', sortedWatchlistObject)
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
                        this.$delete(receivedEvent.market_odds, 'other')
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
                    let eventStartTime = _.uniq(this.eventsList.map(event => `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`))
                    let eventObject = {}
                    eventsSchedule.map(schedule => {
                        if(this.tradePageSettings.sort_event == 1) {
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
                        } else if(this.tradePageSettings.sort_event == 2) {
                            eventStartTime.map(startTime => {
                                this.eventsList.map(event => {
                                    let eventSchedLeague = `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`
                                    if(event.game_schedule === schedule && eventSchedLeague === startTime) {
                                        if(typeof(eventObject[schedule]) == "undefined") {
                                            eventObject[schedule] = {}
                                        }
                                        if(typeof(eventObject[schedule][startTime]) == "undefined") {
                                            eventObject[schedule][startTime] = []
                                        }
                                        eventObject[schedule][startTime].push(event)
                                    }
                                })
                            })
                        }
                    })
                    let sortedEventObject = {}
                    Object.keys(eventObject).map(schedule => {
                        Object.keys(eventObject[schedule]).sort().map(league => {
                            if(typeof(sortedEventObject[schedule]) == "undefined") {
                                sortedEventObject[schedule] = {}
                            }
                            sortedEventObject[schedule][league] = eventObject[schedule][league]
                        })
                    })
                    Object.keys(eventObject).map(schedule => {
                        this.$store.commit('trade/SET_EVENTS', { schedule: schedule, events: sortedEventObject[schedule] })
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
                        if(this.tradePageSettings.sort_event == 1) {
                            if(!_.isEmpty(this.events.watchlist) && event.league_name in this.events.watchlist) {
                                this.$store.commit('trade/ADD_TO_EVENTS', { schedule: 'watchlist', league: event.league_name, event: event })
                            } else {
                                this.$store.commit('trade/ADD_TO_EVENTS', { schedule: event.game_schedule, league: event.league_name, event: event })
                            }
                        } else if(this.tradePageSettings.sort_event == 2) {
                            let eventStartTime = `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`
                            if(!_.isEmpty(this.events.watchlist) && eventStartTime in this.events.watchlist) {
                                this.$store.commit('trade/ADD_TO_EVENTS', { schedule: 'watchlist', league: eventStartTime, event: event })
                            } else {
                                this.$store.commit('trade/ADD_TO_EVENTS', { schedule: event.game_schedule, league: eventStartTime, event: event })
                            }
                        }
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
                            if(event.uid == removedEvent) {
                                this.$store.commit('trade/REMOVE_FROM_EVENT_LIST', { type: 'uid', data: removedEvent })
                                this.$store.commit('trade/REMOVE_FROM_ALL_EVENT_LIST', { type: 'uid', data: removedEvent })
                                if(this.tradePageSettings.sort_event == 1) {
                                    if(!_.isEmpty(this.events.watchlist) && event.league_name in this.events.watchlist) {
                                        this.$store.commit('trade/REMOVE_EVENT', { schedule: 'watchlist', removedLeague: event.league_name, removedEvent: removedEvent })
                                        if(this.events.watchlist[event.league_name].length === 0) {
                                            this.$delete(this.events.watchlist, event.league_name)
                                        }
                                    } else {
                                        this.$store.commit('trade/REMOVE_EVENT', { schedule: event.game_schedule, removedLeague: event.league_name, removedEvent: removedEvent })
                                        if(this.events[event.game_schedule][event.league_name].length === 0) {
                                            this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: event.game_schedule, league: event.league_name })
                                            this.$store.commit('trade/REMOVE_FROM_LEAGUE', { schedule:  event.game_schedule, league: event.league_name })
                                            this.$delete(this.events[event.game_schedule], event.league_name)
                                        }
                                    }
                                } else if(this.tradePageSettings.sort_event == 2) {
                                    let eventStartTime = `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`
                                    if(!_.isEmpty(this.events.watchlist) && eventStartTime in this.events.watchlist) {
                                        this.$store.commit('trade/REMOVE_EVENT', { schedule: 'watchlist', removedLeague: eventStartTime, removedEvent: removedEvent })
                                        if(this.events.watchlist[eventStartTime].length === 0) {
                                            this.$delete(this.events.watchlist, eventStartTime)
                                        }
                                    } else {
                                        this.$store.commit('trade/REMOVE_EVENT', { schedule: event.game_schedule, removedLeague: eventStartTime, removedEvent: removedEvent })
                                        if(this.events[event.game_schedule][eventStartTime].length === 0) {
                                            this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: event.game_schedule, league: event.league_name })
                                            this.$store.commit('trade/REMOVE_FROM_LEAGUE', { schedule:  event.game_schedule, league: event.league_name })
                                            this.$delete(this.events[event.game_schedule], eventStartTime)
                                        }
                                    }
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
                            if(this.tradePageSettings.sort_event == 1) {
                                this.$store.commit('trade/REMOVE_EVENT', { schedule: event.game_schedule, removedLeague: event.league_name, removedEvent: event.uid })
                                if(this.events[event.game_schedule][event.league_name].length === 0) {
                                    this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: event.game_schedule, league: event.league_name })
                                    this.$delete(this.events[event.game_schedule], event.league_name)
                                }
                                this.$set(event, 'game_schedule', updatedEventSchedule.game_schedule)
                                if(event.league_name in this.events[updatedEventSchedule.game_schedule]) {
                                    Object.keys(this.events[updatedEventSchedule.game_schedule]).map(league => {
                                        this.events[updatedEventSchedule.game_schedule][league].push(event)
                                    })
                                } else {
                                    if(typeof(this.events[updatedEventSchedule.game_schedule][event.league_name]) == "undefined") {
                                        this.events[updatedEventSchedule.game_schedule][event.league_name] = []
                                    }
                                    this.events[updatedEventSchedule.game_schedule][event.league_name].push(event)
                                    let sortedEventObject = {}
                                    Object.keys(this.events[updatedEventSchedule.game_schedule]).sort().map(league => {
                                        if(typeof(sortedEventObject[updatedEventSchedule.game_schedule]) == "undefined") {
                                            sortedEventObject[updatedEventSchedule.game_schedule] = {}
                                        }
                                        sortedEventObject[updatedEventSchedule.game_schedule][league] = this.events[updatedEventSchedule.game_schedule][league]
                                    })
                                    this.$store.commit('trade/SET_EVENTS', { schedule: updatedEventSchedule.game_schedule, events: sortedEventObject[updatedEventSchedule.game_schedule] })
                                }
                            } else if(this.tradePageSettings.sort_event == 2) {
                                let eventStartTime = `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`
                                this.$store.commit('trade/REMOVE_EVENT', { schedule: event.game_schedule, removedLeague: eventStartTime, removedEvent: event.uid })
                                if(this.events[event.game_schedule][eventStartTime].length === 0) {
                                    this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: event.game_schedule, league: event.league_name })
                                    this.$delete(this.events[event.game_schedule], eventStartTime)
                                }
                                this.$set(event, 'game_schedule', updatedEventSchedule.game_schedule)
                                if(eventStartTime in this.events[updatedEventSchedule.game_schedule]) {
                                    Object.keys(this.events[updatedEventSchedule.game_schedule]).map(eventStartTime => {
                                        this.events[updatedEventSchedule.game_schedule][eventStartTime].push(event)
                                    })
                                } else {
                                    if(typeof(this.events[updatedEventSchedule.game_schedule][eventStartTime]) == "undefined") {
                                        this.events[updatedEventSchedule.game_schedule][eventStartTime] = []
                                    }
                                    this.events[updatedEventSchedule.game_schedule][eventStartTime].push(event)
                                    let sortedEventObject = {}
                                    Object.keys(this.events[updatedEventSchedule.game_schedule]).sort().map(startTime => {
                                        if(typeof(sortedEventObject[updatedEventSchedule.game_schedule]) == "undefined") {
                                            sortedEventObject[updatedEventSchedule.game_schedule] = {}
                                        }
                                        sortedEventObject[updatedEventSchedule.game_schedule][startTime] = this.events[updatedEventSchedule.game_schedule][startTime]
                                    })
                                    this.$store.commit('trade/SET_EVENTS', { schedule: updatedEventSchedule.game_schedule, events: sortedEventObject[updatedEventSchedule.game_schedule] })
                                }
                            }
                        }
                    })
                }
            })
        },
        getUpdatedOdds() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getUpdatedOdds') {
                    let updatedOdds = getSocketValue(response.data, 'getUpdatedOdds')
                    let schedule = ['inplay', 'today', 'early', 'watchlist']
                    let team = ['HOME', 'AWAY', 'DRAW']
                    schedule.map(schedule => {
                        Object.keys(this.events[schedule]).map(league => {
                            this.events[schedule][league].map(event => {
                                updatedOdds.map(updatedOdd => {
                                    this.oddsTypeBySport.map(oddType => {
                                        team.map(team => {
                                            if(oddType in event.market_odds.main && team in event.market_odds.main[oddType]) {
                                                if(event.market_odds.main[oddType][team].market_id === updatedOdd.market_id && event.market_odds.main[oddType][team].odds != updatedOdd.odds) {
                                                    this.$set(event.market_odds.main[oddType][team], 'odds', updatedOdd.odds)
                                                }
                                            }
                                        })
                                    })

                                    if('other' in event.market_odds) {
                                        Object.keys(event.market_odds.other).map(otherMarket => {
                                            this.oddsTypeBySport.map(oddType => {
                                                team.map(team => {
                                                    if(oddType in event.market_odds.other[otherMarket] && team in event.market_odds.other[otherMarket][oddType]) {
                                                        if(event.market_odds.other[otherMarket][oddType][team].market_id === updatedOdd.market_id && event.market_odds.other[otherMarket][oddType][team].odds != updatedOdd.odds) {
                                                            this.$set(event.market_odds.other[otherMarket][oddType][team], 'odds', updatedOdd.odds)
                                                        }
                                                    }
                                                })
                                            })
                                        })
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
