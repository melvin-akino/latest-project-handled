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

            <div class="w-5/6">
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
            }
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
        ...mapState('trade', ['isBetBarOpen'])
    },
    mounted() {
        this.getWatchlistData()
        this.getUserTradeLayout()
        this.$socket.send('getEvents')
        this.$options.sockets.onmessage = ((response) => {
            if(getSocketKey(response.data) === 'getEvents') {
                let receivedEvents = getSocketValue(response.data, 'getEvents')
                console.log(receivedEvents)
                //TODO: Transform events grouped by schedule and league
                // Object.keys(getSocketValue(response.data, 'getEvents')).forEach(sched => {
                //     // this.events[sched] = receivedEvents[sched]
                // })
            }
        })
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
    .gameScheds {
        margin-top: 52px;
    }
</style>
