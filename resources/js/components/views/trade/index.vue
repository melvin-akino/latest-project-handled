<template>
    <div class="trade">
        <div class="flex">
            <div class="w-1/6">
                <div class="fixed sidebar bg-gray-800 w-1/6 pr-4 overflow-y-auto h-screen" v-adjust-sidebar-height="isBetBarOpen">
                    <Wallet></Wallet>
                    <Watchlist :watchlist="events.watchlist"></Watchlist>
                    <Sports></Sports>
                </div>
            </div>

            <div class="w-5/6 gameWindow">
                <Columns></Columns>
                <div class="gameScheds" v-adjust-game-window-height="isBetBarOpen" v-adjust-game-window-width>
                    <Games gameSchedType="watchlist" :games="events.watchlist"></Games>
                    <Games gameSchedType="inplay" :games="events.inplay"></Games>
                    <Games gameSchedType="today" :games="events.today"></Games>
                    <Games gameSchedType="early" :games="events.early"></Games>
                </div>
            </div>
        </div>
        <bet-slip v-for="odd in openedBetSlips" :odd_details="odd" :key="odd.market_id"></bet-slip>
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
import BetSlip from './BetSlip'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'
import { sortByObjectKeys } from '../../../helpers/array'

export default {
    components: {
        Sports,
        Wallet,
        Watchlist,
        Columns,
        Games,
        Betbar,
        BetSlip,
    },
    head: {
        title() {
            return {
                inner: 'Trade'
            }
        }
    },
    computed: {
        ...mapState('trade', ['isBetBarOpen', 'selectedSport', 'selectedLeagues', 'oddsTypeBySport', 'columnsToDisplay', 'allEventsList', 'eventsList', 'events', 'openedBetSlips', 'tradePageSettings'])
    },
    mounted() {
        this.$store.dispatch('trade/getTradeWindowData')
        this.$store.dispatch('trade/getTradePageSettings')
        this.getWatchlist()
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
                    let watchlistEvents = sortByObjectKeys(watchlistObject, sortedWatchlistObject)
                    this.$store.commit('trade/SET_WATCHLIST', watchlistEvents)
                }
            })
        },
        getEvents() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getEvents') {
                    let receivedEvents = getSocketValue(response.data, 'getEvents')
                    receivedEvents.map(receivedEvent => {
                        Object.keys(this.selectedLeagues).map(schedule => {
                            this.selectedLeagues[schedule].map(league => {
                                if(receivedEvent.game_schedule == schedule && receivedEvent.league_name == league) {
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
                                }
                            })
                        })
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
                        let events = sortByObjectKeys(eventObject[schedule], sortedEventObject[schedule])
                        this.$store.commit('trade/SET_EVENTS', { schedule: schedule, events: events })
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
                                this.$store.dispatch('trade/transformEvents', { schedule: event.game_schedule, league: event.league_name, payload: event })
                            } else if(this.tradePageSettings.sort_event == 2) {
                                let eventStartTime = `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`
                                this.$store.commit('trade/REMOVE_EVENT', { schedule: event.game_schedule, removedLeague: eventStartTime, removedEvent: event.uid })
                                if(this.events[event.game_schedule][eventStartTime].length === 0) {
                                    this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: event.game_schedule, league: event.league_name })
                                    this.$delete(this.events[event.game_schedule], eventStartTime)
                                }
                                this.$set(event, 'game_schedule', updatedEventSchedule.game_schedule)
                                this.$store.dispatch('trade/transformEvents', { schedule: event.game_schedule, league: eventStartTime, payload: event })
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
        adjustSidebarHeight: {
            bind(el, binding, vnode) {
                if(binding.value) {
                    el.style.height = 'calc(100vh - 256px)'
                } else {
                    el.style.height = 'calc(100vh - 104px)'
                }
            },
            update(el, binding, vnode) {
                if(binding.value) {
                    el.style.height = 'calc(100vh - 256px)'
                } else {
                    el.style.height = 'calc(100vh - 104px)'
                }
            }
        },
        adjustGameWindowHeight: {
            bind(el, binding, vnode) {
                if(binding.value) {
                    el.style.height = 'calc(100vh - 320px)'
                } else {
                    el.style.height = 'calc(100vh - 168px)'
                }
            },
            update(el, binding, vnode) {
                if(binding.value) {
                    el.style.height = 'calc(100vh - 320px)'
                } else {
                    el.style.height = 'calc(100vh - 168px)'
                }
            }
        },
        adjustGameWindowWidth: {
            bind(el, binding, vnode) {
                let { selectedSport, columnsToDisplay } = vnode.context
                if(selectedSport == 3) {
                    if(columnsToDisplay.length > 8) {
                        el.style.width = '115rem'
                    } else {
                        el.style.width = '100%'
                    }
                } else {
                    el.style.width = '100%'
                }
            },
            componentUpdated(el, binding, vnode) {
                let { selectedSport, columnsToDisplay } = vnode.context
                if(selectedSport == 3) {
                    if(columnsToDisplay.length > 8) {
                        el.style.width = '115rem'
                    } else {
                        el.style.width = '100%'
                    }
                } else {
                    el.style.width = '100%'
                }
            }
        }
    },
    beforeRouteLeave(to, from, next) {
        this.$store.commit('trade/SET_IS_LOADING_LEAGUES', true)
        this.$store.commit('trade/SET_IS_LOADING_EVENTS', true)
        this.$store.commit('trade/CLEAR_EVENTS')
        this.$store.commit('trade/CLEAR_EVENTS_LIST')
        this.$store.commit('trade/CLEAR_ALL_EVENTS_LIST')
        next()
    }
}
</script>

<style lang="scss">
    .gameWindow {
        position: relative;
        overflow-x: auto;
    }

    .gameScheds {
        overflow-x: hidden;
        overflow-y: auto;
    }
</style>
