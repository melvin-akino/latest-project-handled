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
    data () {
        return {
            events: {
                watchlist: [],
                inplay: [],
                today: [],
                early: []
            },
            eventsList: []
        }
    },
    head: {
        title() {
            return {
                inner: 'Trade'
            }
        }
    },
    computed: {
        ...mapState('trade', ['isBetBarOpen', 'oddsTypeBySport'])
    },
    mounted() {
        this.getWatchlistData()
        this.getUserTradeLayout()
        this.getEvents()
        this.getUpdatedOdds()
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
        getWatchlistData() {
            let token = Cookies.get('mltoken')

            axios.get('v1/trade/watchlist', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                let watchListLeagues = _.uniq(response.data.data.map(event => event.league_name))
                let watchListObject = {}
                watchListLeagues.map(league => {
                    response.data.data.map(event => {
                        if(event.league_name === league) {
                            if(typeof(watchListObject[league]) == "undefined") {
                                watchListObject[league] = []
                            }
                            watchListObject[league].push(event)
                        }
                    })
                })
                this.events.watchlist = watchListObject
            })
            .catch(err => this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code))
        },
        getEvents() {
            this.$options.sockets.onmessage = ((response) => {
                if(getSocketKey(response.data) === 'getEvents') {
                    let receivedEvents = getSocketValue(response.data, 'getEvents')
                    this.eventsList = receivedEvents
                    let eventsSchedule = _.uniq(receivedEvents.map(event => event.game_schedule))
                    let eventsLeague = _.uniq(receivedEvents.map(event => event.league_name))
                    let eventObject = {}
                    eventsSchedule.map(schedule => {
                        eventsLeague.map(league => {
                            receivedEvents.map(event => {
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
                        this.events[schedule] = eventObject[schedule]
                    })
                }
            })
        },
        getUpdatedOdds() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getUpdatedOdds') {
                    let updatedOdd = getSocketValue(response.data, 'getUpdatedOdds')
                    let team = ['home', 'away', 'draw']
                    this.eventsList.map(event => {
                        this.oddsTypeBySport.map(oddType => {
                            team.map(team => {
                                if(team in event.market_odds.main[oddType]) {
                                    if(event.market_odds.main[oddType][team].market_id === updatedOdd.market_id) {
                                        if(event.market_odds.main[oddType][team].odds != updatedOdd.odds) {
                                            event.market_odds.main[oddType][team].odds = updatedOdd.odds
                                        }
                                    }
                                }
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
