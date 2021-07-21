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
        <Betbar>
            <bet-slip v-for="odd in openedBetSlips" :odd_details="odd" :key="odd.betslip_id"></bet-slip>
        </Betbar>
    </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import _ from 'lodash'
import Cookies from 'js-cookie'
import Swal from 'sweetalert2'
import Sports from './Sports'
import Wallet from './Wallet'
import Watchlist from './Watchlist'
import Columns from './Columns'
import Games from './Games'
import Betbar from './Betbar'
import BetSlip from './BetSlip'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'
import { sortByObjectKeys } from '../../../helpers/array'
import Vue from 'vue'
const vm = new Vue()
import bus from '../../../eventBus'

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
    data() {
        return {
            maintenance: {
                hg: false,
                isn: false,
                pin: false
            }
        }
    },
    computed: {
        ...mapState('trade', ['isBetBarOpen', 'selectedSport', 'leagues', 'selectedLeagues', 'oddsTypeBySport', 'columnsToDisplay', 'eventsList', 'openedBetSlips', 'tradePageSettings']),
        ...mapGetters('trade', ['events'])
    },
    created() {
        vm.$connect()
    },
    mounted() {
        this.$store.dispatch('trade/getTradeWindowData')
        this.$store.dispatch('trade/getTradePageSettings')
        this.modifyEventsFromSocket()
    },
    methods: {
        modifyEventsFromSocket() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) ===  'getWatchlist') {
                    let watchlist = getSocketValue(response.data, 'getWatchlist')
                    if(_.isArray(watchlist)) {
                        if(watchlist.length == 0) {
                            this.$store.commit('trade/CLEAR_WATCHLIST')
                        } else {
                            watchlist.map(watchlistEvent => {
                                let oddTypeWithIncompleteMarkets = []
                                if(watchlistEvent.hasOwnProperty('market_odds')) {
                                    Object.keys(watchlistEvent.market_odds).map(marketType => {
                                        Object.keys(watchlistEvent.market_odds[marketType]).map(oddType => {
                                            let odds = []
                                            Object.keys(watchlistEvent.market_odds[marketType][oddType]).map(team => {
                                                odds.push(watchlistEvent.market_odds[marketType][oddType][team].odds)
                                            })
                                            let oddsNonEmpty = odds.filter(odds => odds)
                                            let oddsEmpty = odds.filter(odds => !odds)
                                            if(odds.length != oddsNonEmpty.length && odds.length != oddsEmpty.length) {
                                                oddTypeWithIncompleteMarkets.push(oddType)
                                                if(watchlistEvent.market_odds[marketType].hasOwnProperty(oddType)) {
                                                    this.$delete(watchlistEvent.market_odds[marketType], oddType)
                                                }
                                            }
                                        })
                                    })
                                    if(!_.isEmpty(oddTypeWithIncompleteMarkets)) {
                                        if(watchlistEvent.market_odds.hasOwnProperty('other')) {
                                            this.$socket.send(`getWatchlist_${watchlistEvent.uid}_withOtherMarket`)
                                        } else {
                                            this.$socket.send(`getWatchlist_${watchlistEvent.uid}`)
                                        }
                                    }
                                }
                                this.$set(watchlistEvent, 'watchlist', true)
                                this.$store.commit('trade/SET_EVENTS_LIST', watchlistEvent)
                                if(!watchlistEvent.hasOwnProperty('single_event_response')) {
                                    let watchlistEventsUIDs = this.eventsList.filter(event => event.master_league_id == watchlistEvent.master_league_id && event.game_schedule == watchlistEvent.game_schedule && event.hasOwnProperty('watchlist')).map(event => event.uid)
                                    let watchlistUIDs = watchlist.map(event => event.uid)
                                    watchlistEventsUIDs.map(uid => {
                                        if(!watchlistUIDs.includes(uid)) {
                                            this.$store.commit('trade/REMOVE_ALL_FROM_EVENT_LIST', { game_schedule: watchlistEvent.game_schedule, master_league_id: watchlistEvent.master_league_id, uid: uid })
                                        }
                                    })
                                }
                            })
                        }
                    }
                } else if(getSocketKey(response.data) === 'getEvents') {
                    let receivedEvents = getSocketValue(response.data, 'getEvents')
                    if(_.isArray(receivedEvents)) {
                        Object.keys(this.selectedLeagues).map(schedule => {
                            this.selectedLeagues[schedule].map(league => {
                                receivedEvents.map(receivedEvent => {
                                    let oddTypeWithIncompleteMarkets = []
                                    if(receivedEvent.hasOwnProperty('market_odds')) {
                                        Object.keys(receivedEvent.market_odds).map(marketType => {
                                            Object.keys(receivedEvent.market_odds[marketType]).map(oddType => {
                                                let odds = []
                                                Object.keys(receivedEvent.market_odds[marketType][oddType]).map(team => {
                                                    odds.push(receivedEvent.market_odds[marketType][oddType][team].odds)
                                                })
                                                let oddsNonEmpty = odds.filter(odds => odds)
                                                let oddsEmpty = odds.filter(odds => !odds)
                                                if(odds.length != oddsNonEmpty.length && odds.length != oddsEmpty.length) {
                                                    oddTypeWithIncompleteMarkets.push(oddType)
                                                    if(receivedEvent.market_odds[marketType].hasOwnProperty(oddType)) {
                                                        this.$delete(receivedEvent.market_odds[marketType], oddType)
                                                    }
                                                }
                                            })
                                        })
                                    }
                                    if(receivedEvent.game_schedule == schedule && receivedEvent.master_league_id == league.master_league_id && receivedEvent.sport_id == this.selectedSport) {
                                        if(!_.isEmpty(oddTypeWithIncompleteMarkets) && receivedEvent.hasOwnProperty('market_odds')) {
                                            if(receivedEvent.market_odds.hasOwnProperty('other')) {
                                                this.$socket.send(`getEvents_${receivedEvent.master_league_id}_${receivedEvent.game_schedule}_${receivedEvent.uid}_withOtherMarket`)
                                            } else {
                                                this.$socket.send(`getEvents_${receivedEvent.master_league_id}_${receivedEvent.game_schedule}_${receivedEvent.uid}`)
                                            }
                                        }
                                        this.$store.commit('trade/SET_EVENTS_LIST', receivedEvent)

                                        if(!receivedEvent.hasOwnProperty('single_event_response')) {
                                            let selectedEventsUIDs = this.eventsList.filter(event => event.master_league_id == receivedEvent.master_league_id && event.game_schedule == receivedEvent.game_schedule && !event.hasOwnProperty('watchlist')).map(event => event.uid)
                                            let receivedEventsUIDs = receivedEvents.map(event => event.uid)
                                            selectedEventsUIDs.map(uid => {
                                                if(!receivedEventsUIDs.includes(uid)) {
                                                    this.$store.commit('trade/REMOVE_FROM_EVENT_LIST', { game_schedule: receivedEvent.game_schedule, master_league_id: receivedEvent.master_league_id, uid: uid })
                                                }
                                            })
                                        }

                                        this.$store.dispatch('trade/updateLeagueMatchCount', { schedule: schedule, league: league })
                                    }
                                })
                            })
                        })
                    } else {
                        let leagueIds = this.leagues[receivedEvents.schedule].map(league => league.master_league_id)
                        if(leagueIds.includes(receivedEvents.master_league_id)) {
                            let selectedLeagueIds = this.selectedLeagues[receivedEvents.schedule].map(league => league.master_league_id)
                            if(selectedLeagueIds.includes(receivedEvents.master_league_id)) {
                                this.$store.dispatch('trade/toggleLeague', { action: 'remove', master_league_id: receivedEvents.master_league_id, schedule: receivedEvents.schedule, sport_id: this.selectedSport })
                                this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', {schedule: receivedEvents.schedule, league: receivedEvents.master_league_id })
                            }
                            this.$store.commit('trade/REMOVE_FROM_LEAGUE', {schedule: receivedEvents.schedule, league: receivedEvents.master_league_id })
                        }
                        if(receivedEvents.hasOwnProperty('uid')) {
                            this.$store.commit('trade/REMOVE_FROM_EVENT_LIST', { game_schedule: receivedEvents.schedule, master_league_id: receivedEvents.master_league_id, uid: receivedEvents.uid })
                        } else {
                            this.$store.commit('trade/REMOVE_FROM_EVENT_LIST', { game_schedule: receivedEvents.schedule, master_league_id: receivedEvents.master_league_id })
                    }
                    }
                } else if(getSocketKey(response.data) === 'getAdditionalEvents') {
                    let additionalEvents = getSocketValue(response.data, 'getAdditionalEvents')
                    if(_.isArray(additionalEvents)) {
                        additionalEvents.map(newEvent => {
                            let leagueNames = this.leagues[newEvent.game_schedule].map(league => league.name)
                            let selected = this.selectedLeagues[newEvent.game_schedule].includes(newEvent.league_name)
                            if (selected) {
                                this.$store.commit('trade/SET_EVENTS_LIST', newEvent)
                                let leagueMatchCount = this.eventsList.filter(event => newEvent.league_name == event.league_name && newEvent.game_schedule == event.game_schedule && !event.hasOwnProperty('watchlist')).length
                                if (leagueNames.includes(newEvent.league_name)) {
                                    this.$store.dispatch('trade/updateLeagueMatchCount', {
                                        schedule: newEvent.game_schedule,
                                        league: newEvent.league_name,
                                        match_count: leagueMatchCount
                                    })
                                } else {
                                    this.$store.commit('trade/ADD_TO_LEAGUES', {
                                        schedule: newEvent.game_schedule,
                                        league: {name: newEvent.league_name, match_count: leagueMatchCount}
                                    })
                                }
                            } else {
                                if (leagueNames.includes(newEvent.league_name)) {
                                    let leagueMatchCount = this.leagues[newEvent.game_schedule].filter(league => league.name == newEvent.league_name)[0].match_count
                                    this.$store.dispatch('trade/updateLeagueMatchCount', {
                                        schedule: newEvent.game_schedule,
                                        league: newEvent.league_name,
                                        match_count: leagueMatchCount + 1
                                    })
                                } else {
                                    let newEventsCount = additionalEvents.filter(event => event.league_name == newEvent.league_name && event.game_schedule == newEvent.game_schedule).length
                                    this.$store.commit('trade/ADD_TO_LEAGUES', {
                                        schedule: newEvent.game_schedule,
                                        league: {name: newEvent.league_name, match_count: newEventsCount}
                                    })
                                }
                            }
                        })
                    }
                } else if(getSocketKey(response.data) === 'getForRemovalEvents') {
                    let removedEvents = getSocketValue(response.data, 'getForRemovalEvents')
                    removedEvents.map(removedEvent => {
                        if(removedEvent.game_schedule && removedEvent.league_name && removedEvent.uid) {
                            let leagueNames = this.leagues[removedEvent.game_schedule].map(league => league.name)
                            let eventInTradeWindow = this.eventsList.some(event => event.league_name == removedEvent.league_name && event.game_schedule == removedEvent.game_schedule)
                            if(eventInTradeWindow) {
                                this.$store.commit('trade/REMOVE_ALL_FROM_EVENT_LIST', { league_name: removedEvent.league_name, game_schedule: removedEvent.game_schedule, uid: removedEvent.uid })
                                let leagueMatchCount = this.eventsList.filter(event => removedEvent.league_name == event.league_name && removedEvent.game_schedule == event.game_schedule && !event.hasOwnProperty('watchlist')).length
                                if(leagueMatchCount == 0) {
                                    this.$store.dispatch('trade/toggleLeague', { action: 'remove', league_name: removedEvent.league_name,  schedule: removedEvent.game_schedule, sport_id: this.selectedSport })
                                    this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', {schedule: removedEvent.game_schedule, league: removedEvent.league_name })
                                    this.$store.commit('trade/REMOVE_FROM_LEAGUE', {schedule: removedEvent.game_schedule, league: removedEvent.league_name })
                                } else {
                                    this.$store.dispatch('trade/updateLeagueMatchCount', { schedule: removedEvent.game_schedule, league: removedEvent.league_name, match_count: leagueMatchCount })
                                }
                            } else {
                                if(leagueNames.includes(removedEvent.league_name)) {
                                    let leagueMatchCount = this.leagues[removedEvent.game_schedule].filter(league => league.name == removedEvent.league_name)[0].match_count
                                    if(leagueMatchCount == 1) {
                                        this.$store.commit('trade/REMOVE_FROM_LEAGUE', {schedule: removedEvent.game_schedule, league: removedEvent.league_name })
                                    } else {
                                        let removedEventsCount = removedEvents.filter(event => event.league_name == removedEvent.league_name && event.game_schedule == removedEvent.game_schedule).length
                                        let updatedMatchCount = leagueMatchCount - removedEventsCount
                                        if(updatedMatchCount > 0) {
                                            this.$store.dispatch('trade/updateLeagueMatchCount', { schedule: removedEvent.game_schedule, league: removedEvent.league_name, match_count: updatedMatchCount })
                                        } else {
                                            this.$store.commit('trade/REMOVE_FROM_LEAGUE', {schedule: removedEvent.game_schedule, league: removedEvent.league_name })
                                        }
                                    }
                                }
                            }
                        }
                    })
                } else if(getSocketKey(response.data) === 'getUpdatedOdds') {
                    let updatedOdds = getSocketValue(response.data, 'getUpdatedOdds')
                    updatedOdds.map(updatedOdd => {
                        this.$store.dispatch('trade/updateOdds', updatedOdd)
                    })
                } else if(getSocketKey(response.data) === 'getEventHasOtherMarket') {
                    let eventHasOtherMarket = getSocketValue(response.data, 'getEventHasOtherMarket')
                    this.eventsList.map(event => {
                        if(event.uid == eventHasOtherMarket.uid) {
                            event.has_other_markets = eventHasOtherMarket.has_other_markets
                            if(!eventHasOtherMarket.has_other_markets && event.hasOwnProperty('market_odds')) {
                                this.$delete(event.market_odds, 'other')
                            }
                        }
                    })
                } else if(getSocketKey(response.data) === 'getMaintenance') {
                    let maintenance = getSocketValue(response.data, 'getMaintenance')
                    if(this.maintenance[maintenance.provider] != maintenance.under_maintenance) {
                        if(maintenance.under_maintenance) {
                            bus.$emit("SHOW_SNACKBAR", {
                                id: `${maintenance.provider.toUpperCase()}-maintenance`,
                                color: "error",
                                text: `${maintenance.provider.toUpperCase()} bookmaker is unavailable`,
                                timeout: -1
                            });
                            this.$store.commit('trade/ADD_TO_UNDER_MAINTENANCE_PROVIDERS', maintenance.provider)
                        } else {
                            bus.$emit("SHOW_SNACKBAR", {
                                id: `${maintenance.provider.toUpperCase()}-maintenance`,
                                color: "primary",
                                text: `${maintenance.provider.toUpperCase()} bookmaker is available`
                            });
                            this.$store.commit('trade/REMOVE_FROM_UNDER_MAINTENANCE_PROVIDERS', maintenance.provider)
                        }
                    }
                    this.maintenance[maintenance.provider] = maintenance.under_maintenance
                } else if(getSocketKey(response.data) === 'getEventsUpdate') {
                    let eventsUpdate = getSocketValue(response.data, 'getEventsUpdate')
                    this.eventsList.map(event => {
                        if(event.uid == eventsUpdate.id) {
                            this.$set(event.home, 'score', eventsUpdate.score.home)
                            this.$set(event.away, 'score', eventsUpdate.score.away)
                            this.$set(event, 'running_time', eventsUpdate.running_time)
                        }
                    })
                } else if(getSocketKey(response.data) === 'getForRemovalOdds') {
                    let removalOdds = getSocketValue(response.data, 'getForRemovalOdds')
                    this.eventsList.map(event => {
                        if(event.uid == removalOdds.uid && event.hasOwnProperty('market_odds')) {
                            this.oddsTypeBySport.map(oddType => {
                                if(oddType in event.market_odds.main) {
                                    Object.keys(event.market_odds.main[oddType]).map(team => {
                                        this.$set(event.market_odds.main[oddType][team], 'market_id', '')
                                        this.$set(event.market_odds.main[oddType][team], 'odds', '')
                                        if(event.market_odds.main[oddType][team].hasOwnProperty('points')) {
                                            this.$set(event.market_odds.main[oddType][team], 'points', '')
                                        }
                                    })
                                }
                            })
                            event.has_other_markets = false
                            if('other' in event.market_odds) {
                                this.$delete(event.market_odds, 'other')
                            }
                        }
                    })
                } else if(getSocketKey(response.data) === 'getForRemovalSection') {
                    let removalSection = getSocketValue(response.data, 'getForRemovalSection')
                    this.eventsList.map(event => {
                        if(event.uid == removalSection.uid && event.hasOwnProperty('market_odds')) {
                            let mainMarketEventIdentifier = event.uid.split('-')[3]
                            if(mainMarketEventIdentifier == removalSection.market_event_identifier) {
                                if(removalSection.odd_type in event.market_odds.main) {
                                    Object.keys(event.market_odds.main[removalSection.odd_type]).map(team => {
                                        this.$set(event.market_odds.main[removalSection.odd_type][team], 'market_id', '')
                                        this.$set(event.market_odds.main[removalSection.odd_type][team], 'odds', '')
                                        if(event.market_odds.main[removalSection.odd_type][team].hasOwnProperty('points')) {
                                            this.$set(event.market_odds.main[removalSection.odd_type][team], 'points', '')
                                        }
                                    })
                                }
                            } else {
                                if('other' in event.market_odds && removalSection.odd_type in event.market_odds.other[removalSection.market_event_identifier]) {
                                    Object.keys(event.market_odds.other[removalSection.market_event_identifier][removalSection.odd_type]).map(team => {
                                        this.$set(event.market_odds.other[removalSection.market_event_identifier][removalSection.odd_type][team], 'market_id', '')
                                        this.$set(event.market_odds.other[removalSection.market_event_identifier][removalSection.odd_type][team], 'odds', '')
                                        if(event.market_odds.other[removalSection.market_event_identifier][removalSection.odd_type][team].hasOwnProperty('points')) {
                                            this.$set(event.market_odds.other[removalSection.market_event_identifier][removalSection.odd_type][team], 'points', '')
                                        }
                                    })
                                }
                            }
                        }
                    })
                } else if(getSocketKey(response.data) === 'getEventData') {
                    let eventData = getSocketValue(response.data, 'getEventData')
                    this.eventsList.map(event => {
                        if(event.uid == eventData.uid && event.hasOwnProperty('market_odds')) {
                            if(event.hasOwnProperty('watchlist')) {
                                if(event.market_odds.hasOwnProperty('other')) {
                                    this.$socket.send(`getWatchlist_${event.uid}_withOtherMarket`)
                                } else {
                                    this.$socket.send(`getWatchlist_${event.uid}`)
                                }
                            } else {
                                if(event.market_odds.hasOwnProperty('other')) {
                                    this.$socket.send(`getEvents_${event.league_name}_${event.game_schedule}_${event.uid}_withOtherMarket`)
                                } else {
                                    this.$socket.send(`getEvents_${event.league_name}_${event.game_schedule}_${event.uid}`)
                                }
                            }
                        }
                    })
                } else if (getSocketKey(response.data) === 'userLogout') {
                    this.$store.dispatch('auth/logout', { new_access: true })
                }
            })
            this.$options.sockets.onclose = (response => {
                console.clear()
                setTimeout(function() {
                    vm.$connect()
                }, 3000)
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
        this.$store.commit('trade/SET_EVENTS_ERROR', false)
        this.$store.commit('trade/CLEAR_EVENTS_LIST')
        vm.$disconnect()
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

.leaguePanel button {
    &.text-red-600 {
        color: rgba(229, 62, 62, var(--text-opacity)) !important;
    }

    &.text-orange-500 {
        color: rgba(237, 137, 54, var(--text-opacity)) !important;
    }
}
</style>
