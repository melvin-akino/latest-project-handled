<template>
    <div class="gameSchedType">
        <div class="text-white text-center bg-orange-400 cursor-pointer capitalize" :class="{'gameSchedPanel' : !isGameSchedTypeOpen}" @click="isGameSchedTypeOpen = !isGameSchedTypeOpen">
            {{gameSchedType}}
            <span v-show="isGameSchedTypeOpen"><i class="fas fa-chevron-down"></i></span>
            <span v-show="!isGameSchedTypeOpen"><i class="fas fa-chevron-right"></i></span>
        </div>
        <div class="games" :class="{'hidden': !isGameSchedTypeOpen}">
            <p v-if="isLoadingEvents" class="text-sm text-gray-500 text-center pt-2 noeventspanel">Loading events <i class="fas fa-circle-notch fa-spin"></i></p>
            <div v-else class="events">
                <p class="text-sm text-gray-500 text-center pt-2 noeventspanel" v-if="checkIfGamesIsEmpty">No competitions watched in this market.</p>
                <div class="eventGroup" v-else>
                    <div class="bg-white text-sm text-gray-700" v-for="(league, index) in games" :key="index">
                        <div class="flex justify-between py-2 px-4 font-bold border-t border-orange-500 leaguePanel">
                            <div>
                                <button type="button" class="mt-1 pr-1 text-red-600 focus:outline-none" @click="gameSchedType==='watchlist' ? removeFromWatchlist('league', index, league) : unselectLeague(index)"><i class="fas fa-times-circle"></i></button>
                                <button type="button" class="mt-1 pr-1 text-orange-500 focus:outline-none" @click="toggleLeague(index)">
                                    <span v-show="closedLeagues.includes(index)"><i class="fas fa-chevron-down"></i></span>
                                    <span v-show="!closedLeagues.includes(index)"><i class="fas fa-chevron-up"></i></span>
                                </button>
                                {{index}}
                            </div>
                            <div :class="[gameSchedType==='watchlist' ? 'in-watchlist-star' : 'text-white']" @click="gameSchedType==='watchlist' ? removeFromWatchlist('league', index, league) : addToWatchlist('league', index, league)"><i class="fas fa-star"></i></div>
                        </div>
                        <div class="gamesWrapper" :class="!closedLeagues.includes(index) ? 'h-full' : 'h-0 overflow-hidden'">
                            <div class="asianLayout"  v-if="tradeLayout==1">
                                <div class="event" :class="[index % 2 != 0 ? 'alternateEvent' : '']" v-for="(game, index) in league" :key="game.uid">
                                    <div class="relative flex py-4 px-4 game" >
                                        <div class="w-2/12 flex flex-col">
                                            <div><span class="font-bold text-green-400 mr-2">H</span>{{game.home.name}}</div>
                                            <div><span class="font-bold text-red-600 mr-2">A</span>{{game.away.name}}</div>
                                            <div><span class="mr-3">&nbsp;</span>Draw</div>
                                        </div>
                                        <div class="w-1/12 flex-col justify-center">
                                            <div class="text-center">
                                                <span>{{game.sport}}</span>
                                            </div>
                                            <div class="text-center">
                                                <span v-for="(provider, index) in game.with_providers" class="font-bold px-2 mr-1 rounded-lg provider" :class="[`${provider.provider.toLowerCase()}`]" :key="index">{{provider.provider}}</span>
                                            </div>
                                        </div>
                                        <div class="w-1/12 flex flex-col items-center">
                                            <span v-if="gameSchedType === 'inplay' || (gameSchedType === 'watchlist' && game.game_schedule === 'inplay')">{{game.home.score}} - {{game.away.score}}</span>
                                            <span>{{ gameSchedType === 'inplay' || (gameSchedType === 'watchlist' && game.game_schedule === 'inplay') ? game.running_time : game.ref_schedule.split(' ')[0]}}</span>
                                            <span v-if="game.game_schedule != 'inplay'">{{ game.ref_schedule.split(' ')[1]}}</span>
                                        </div>
                                        <div class="w-1/12"></div>
                                        <div class="w-1/12 flex flex-col items-center" :class="column" v-for="(column, index) in oddsTypeBySport" :key="index">
                                            <div class="relative" :class="[{'order-1' : index=='HOME'}, {'order-2' : index=='AWAY'}, {'order-3': index=='DRAW'}, {'mt-5': index=='AWAY' && (!game.market_odds.main[column].hasOwnProperty('HOME') || game.market_odds.main[column].HOME.odds == 0)}, {'mt-6': index=='DRAW' && (game.market_odds.main[column].hasOwnProperty('HOME') && (!game.market_odds.main[column].hasOwnProperty('AWAY') || game.market_odds.main[column].AWAY.odds == 0))}, {'mt-10': index=='DRAW' && ((!game.market_odds.main[column].hasOwnProperty('HOME') || game.market_odds.main[column].HOME.odds == 0) && (!game.market_odds.main[column].hasOwnProperty('AWAY') || game.market_odds.main[column].AWAY.odds == 0))}]" v-for="(odd, index) in game.market_odds.main[column]" :key="odd.market_id" v-toggle-odds="odd.odds">
                                                <span class="absolute text-gray-500 odds-label" :class="[odd.odds != '' ? 'left-label' : 'empty-left-label']">{{odd.points}}</span>
                                                <a href="#" @click.prevent="openBetSlip(odd, game)" class="px-2 rounded-lg" :class="[odd.odds ? 'bet-click' : '', odd.provider_alias ? `${odd.provider_alias.toLowerCase()}` : '']" v-adjust-odd-color="odd.odds">{{odd.odds | twoDecimalPlacesFormat}}</a>
                                            </div>
                                        </div>
                                        <div class="absolute eventStar" :class="[gameSchedType==='watchlist' ? 'in-watchlist-star' : 'text-white']" @click="gameSchedType==='watchlist' ? removeFromWatchlist('event', game.uid, game) : addToWatchlist('event', game.uid, game)">
                                            <span><i class="fas fa-star"></i></span>
                                        </div>
                                        <button class="otherMarketsBtn absolute text-orange-500 hover:text-orange-600 focus:outline-none" @click="toggleOtherMarkets(game)" title="View other markets.">
                                            <span v-show="game.market_odds.hasOwnProperty('other')"><i class="fas fa-minus-square"></i></span>
                                            <span v-show="!game.market_odds.hasOwnProperty('other')"><i class="fas fa-plus-square"></i></span>
                                        </button>
                                    </div>
                                    <div class="otherMarkets" v-if="'other' in game.market_odds">
                                        <div class="relative flex py-4 px-4 game" v-for="(otherMarket, index) in game.market_odds.other" :key="index">
                                            <div class="w-2/12"></div>
                                            <div class="w-1/12 flex justify-center"></div>
                                            <div class="w-1/12"></div>
                                            <div class="w-1/12"></div>
                                            <div class="w-1/12 flex flex-col items-center" :class="column" v-for="(column, index) in oddsTypeBySport" :key="index">
                                                <div class="relative" :class="[{'order-1' : index=='HOME'}, {'order-2' : index=='AWAY'}, {'order-3': index=='DRAW'}, {'mt-5': index=='AWAY' && (!otherMarket[column].hasOwnProperty('HOME') || otherMarket[column].HOME.odds == 0)}, {'mt-6': index=='DRAW' && (otherMarket[column].hasOwnProperty('HOME') && (!otherMarket[column].hasOwnProperty('AWAY') || otherMarket[column].AWAY.odds == 0))}, {'mt-10': index=='DRAW' && ((!otherMarket[column].hasOwnProperty('HOME') || otherMarket[column].HOME.odds == 0) && (!otherMarket[column].hasOwnProperty('AWAY') || otherMarket[column].AWAY.odds == 0))}]" v-for="(odd, index) in otherMarket[column]" :key="odd.market_id" v-toggle-odds="odd.odds">
                                                    <span class="absolute text-gray-500 odds-label" :class="[odd.odds != '' ? 'left-label' : 'empty-left-label']">{{odd.points}}</span>
                                                    <a href="#" @click.prevent="openBetSlip(odd, game)" class="px-2 rounded-lg" :class="[odd.odds ? 'bet-click' : '', odd.provider_alias ? `${odd.provider_alias.toLowerCase()}` : '']" v-adjust-odd-color="odd.odds">{{odd.odds | twoDecimalPlacesFormat}}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="europeanLayout" v-if="tradeLayout==2">
                                <div class="flex flex-col justify-around pl-4 pr-8 py-4 game" :class="[index % 2 != 0 ? 'alternateEvent' : '']" v-for="(game, index) in league" :key="game.uid">
                                    <div class="relative flex justify-center pb-4">
                                        <span class="gameColumn teamColumn">{{game.home.name}}</span>
                                        <span class="gameColumn font-bold text-green-400 text-center">H</span>
                                        <span class="gameColumn text-lg text-center">{{game.home.score}}</span>
                                        <span class="gameColumn text-center">{{ gameSchedType === 'inplay' || (gameSchedType === 'watchlist' && game.game_schedule === 'inplay') ? game.running_time : game.ref_schedule }}</span>
                                        <span class="gameColumn text-lg text-center">{{game.away.score}}</span>
                                        <span class="gameColumn font-bold text-red-600 text-center">A</span>
                                        <span class="gameColumn teamColumn">{{game.away.name}}</span>
                                        <div class="absolute european-event-star" :class="[gameSchedType==='watchlist' ? 'in-watchlist-star' : 'text-white']" @click="gameSchedType==='watchlist' ? removeFromWatchlist('event', game.uid, game) : addToWatchlist('event', game.uid, game)">
                                            <span><i class="fas fa-star"></i></span>
                                        </div>
                                        <button class="europeanOtherMarketsBtn absolute text-orange-500 hover:text-orange-600 focus:outline-none" @click="toggleOtherMarkets(game)" title="View other markets.">
                                            <span v-show="game.market_odds.hasOwnProperty('other')"><i class="fas fa-minus-square"></i></span>
                                            <span v-show="!game.market_odds.hasOwnProperty('other')"><i class="fas fa-plus-square"></i></span>
                                        </button>
                                    </div>
                                    <div class="flex">
                                        <div class="w-1/12"></div>
                                        <div class="w-1/12 flex justify-between mr-10" :class="column" v-for="(column, index) in oddsTypeBySport" :key="index">
                                            <div class="relative" :class="[{'order-1' : index=='HOME'}, {'order-2' : index=='DRAW'}, {'order-3': index=='AWAY'}]" v-for="(odd, index) in game.market_odds.main[column]" :key="odd.market_id" v-toggle-odds="odd.odds">
                                                <span class="absolute text-gray-500 odds-label" :class="[odd.odds != '' ? 'left-label' : 'empty-left-label']">{{odd.points}}</span>
                                                <a href="#"  @click.prevent="openBetSlip(odd, game)" class="px-2 rounded-lg" :class="[odd.odds ? 'bet-click' : '', odd.provider_alias ? `${odd.provider_alias.toLowerCase()}` : '']" v-adjust-odd-color="odd.odds">{{odd.odds | twoDecimalPlacesFormat}}</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="otherMarkets" v-if="'other' in game.market_odds">
                                        <div class="flex" v-for="(otherMarket, index) in game.market_odds.other" :key="index">
                                            <div class="w-1/12"></div>
                                            <div class="w-1/12 flex justify-between mr-10" :class="column" v-for="(column, index) in oddsTypeBySport" :key="index">
                                                <div class="relative" :class="[{'order-1' : index=='HOME'}, {'order-2' : index=='DRAW'}, {'order-3': index=='AWAY'}]" v-for="(odd, index) in otherMarket[column]" :key="odd.market_id" v-toggle-odds="odd.odds">
                                                    <span class="absolute text-gray-500 odds-label" :class="[odd.odds != '' ? 'left-label' : 'empty-left-label']">{{odd.points}}</span>
                                                    <a href="#"  @click.prevent="openBetSlip(odd, game)" class="px-2 rounded-lg" :class="[odd.odds ? 'bet-click' : '', odd.provider_alias ? `${odd.provider_alias.toLowerCase()}` : '']" v-adjust-odd-color="odd.odds">{{odd.odds | twoDecimalPlacesFormat}}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import _ from 'lodash'
import Swal from 'sweetalert2'
import { twoDecimalPlacesFormat } from '../../../helpers/numberFormat'

export default {
    props: ['gameSchedType', 'games'],
    data() {
        return {
            isGameSchedTypeOpen: true,
            closedLeagues: [],
            leaguesLength: 0
        }
    },
    computed: {
        ...mapState('trade', ['selectedSport', 'selectedLeagues', 'tradeLayout', 'oddsTypeBySport', 'events', 'eventsList', 'watchlist', 'openedBetSlips', 'tradePageSettings', 'isLoadingEvents']),
        ...mapState('settings', ['disabledBetColumns']),
        checkIfGamesIsEmpty() {
            return _.isEmpty(this.games)
        }
    },
    methods: {
        toggleOtherMarkets(game) {
            let token = Cookies.get('mltoken')
            axios.get(`v1/trade/other-markets/${game.uid}`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                if(!_.isEmpty(response.data.data)) {
                    if(this.tradePageSettings.sort_event == 1) {
                        this.events[this.gameSchedType][game.league_name].map(event => {
                            if(game.uid == event.uid) {
                                if('other' in event.market_odds) {
                                    this.$delete(event.market_odds, 'other')
                                } else {
                                    this.$set(event.market_odds, 'other', response.data.data)
                                }
                            }
                        })
                    } else if(this.tradePageSettings.sort_event == 2) {
                        let eventStartTime = `[${game.ref_schedule.split(' ')[1]}] ${game.league_name}`
                        this.events[this.gameSchedType][eventStartTime].map(event => {
                            if(game.uid == event.uid) {
                                if('other' in event.market_odds) {
                                    this.$delete(event.market_odds, 'other')
                                } else {
                                    this.$set(event.market_odds, 'other', response.data.data)
                                }
                            }
                        })
                    }
                } else {
                    Swal.fire({
                        icon: 'warning',
                        text: 'No other markets available for that event.'
                    })
                }
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        },
        openBetSlip(odd, game) {
            this.$store.commit('trade/CLOSE_BETSLIP', odd.market_id)
            this.$store.commit('trade/OPEN_BETSLIP', { odd: odd, game: game })
            this.$store.commit('trade/SET_ACTIVE_BETSLIP', odd.market_id)
        },
        toggleLeague(index) {
            if(this.closedLeagues.includes(index)) {
                this.closedLeagues = this.closedLeagues.filter(league => index != league)
            } else {
                this.closedLeagues.push(index)
            }
        },
        unselectLeague(league) {
            if(this.tradePageSettings.sort_event == 2) {
                league = league.split('] ')[1]
            }
            let token = Cookies.get('mltoken')
            this.$store.commit('trade/REMOVE_SELECTED_LEAGUE', { schedule: this.gameSchedType, league: league })
            this.$store.commit('trade/REMOVE_FROM_EVENTS', { schedule: this.gameSchedType, removedLeague: league })
            this.$store.commit('trade/REMOVE_FROM_EVENT_LIST', { type: 'league_name', data: league, game_schedule: this.gameSchedType })
            this.$store.commit('trade/REMOVE_FROM_ALL_EVENT_LIST', { type: 'league_name', data: league, game_schedule: this.gameSchedType })
            this.$store.dispatch('trade/toggleLeague', { league_name: league, sport_id: this.selectedSport, schedule: this.gameSchedType })
        },
        addToWatchlist(type, data, payload) {
            let token = Cookies.get('mltoken')
            if(type=='league' && this.tradePageSettings.sort_event == 2) {
                data = data.split('] ')[1]
            }
            this.$store.dispatch('trade/addToWatchlist', { type: type, data: data, payload: payload, game_schedule: this.gameSchedType})
        },
        providerColors(provider) {
            var color = '';
            switch(provider) {
                case 'hg':
                    color = 'text-green';
                case 'pin':
                default:
                    color = 'text-indigo';
            }
            return color;
        },
        removeFromWatchlist(type, data, payload) {
            let token = Cookies.get('mltoken')
            if(type=='league' && this.tradePageSettings.sort_event == 2) {
                data = data.split('] ')[1]
            }
            axios.post('v1/trade/watchlist/remove', { type: type, data: data }, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                if(type==='league') {
                    this.$store.commit('trade/REMOVE_FROM_EVENTS', { schedule: 'watchlist', removedLeague: data })
                    payload.map(event => {
                        this.$store.dispatch('trade/toggleLeague', { league_name: event.league_name, sport_id: this.selectedSport, schedule: event.game_schedule  })
                        this.$store.commit('trade/ADD_TO_SELECTED_LEAGUE', { schedule: event.game_schedule, league: event.league_name })
                        this.$store.commit('trade/SET_EVENTS_LIST', event)

                        if(this.tradePageSettings.sort_event == 1) {
                            this.$store.dispatch('trade/transformEvents', { schedule: event.game_schedule, league: event.league_name, payload: event })
                        } else if(this.tradePageSettings.sort_event == 2) {
                            let eventStartTime = `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`
                            this.$store.dispatch('trade/transformEvents', { schedule: event.game_schedule, league: eventStartTime, payload: event })
                        }
                    })
                } else if(type==='event') {
                    if(this.tradePageSettings.sort_event == 1) {
                        this.$store.commit('trade/REMOVE_EVENT', { schedule: 'watchlist', removedLeague: payload.league_name, removedEvent: data })
                        this.leaguesLength = this.events.watchlist[payload.league_name].length
                        this.$store.dispatch('trade/transformEvents', { schedule: payload.game_schedule, league: payload.league_name, payload: payload })
                    } else if(this.tradePageSettings.sort_event == 2) {
                        let eventStartTime = `[${payload.ref_schedule.split(' ')[1]}] ${payload.league_name}`
                        this.$store.commit('trade/REMOVE_EVENT', { schedule: 'watchlist', removedLeague: eventStartTime, removedEvent: data })
                        this.leaguesLength = this.events.watchlist[eventStartTime].length
                        this.$store.dispatch('trade/transformEvents', { schedule: payload.game_schedule, league: eventStartTime, payload: payload })
                    }
                    if(this.leaguesLength == 0) {
                        this.$store.commit('trade/REMOVE_FROM_EVENTS', { schedule: 'watchlist', removedLeague: payload.league_name })
                    }
                    this.$store.dispatch('trade/toggleLeague', { league_name: payload.league_name, sport_id: this.selectedSport, schedule: payload.game_schedule  })
                    this.$store.commit('trade/ADD_TO_SELECTED_LEAGUE', { schedule: payload.game_schedule, league: payload.league_name })
                    let eventsListCheckUID = this.eventsList.findIndex(event => event.uid === payload.uid)
                    if(eventsListCheckUID === -1) {
                        this.$store.commit('trade/SET_EVENTS_LIST', payload)
                    }
                }
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        }
    },
    directives: {
        adjustOddColor: {
            componentUpdated(el, binding, vnode) {
                if (binding.value > binding.oldValue) {
                    el.classList.add('ping-success')
                    setTimeout(() => {
                        el.classList.remove('ping-success')
                    }, 1000)
                } else if (binding.value < binding.oldValue) {
                    el.classList.add('ping-danger')
                    setTimeout(() => {
                        el.classList.remove('ping-danger')
                    }, 1000)
                }
            }
        },
        toggleOdds: {
            bind(el, binding, vnode) {
                if(binding.value != 0) {
                    el.style.display = 'block'
                } else {
                    el.style.display = 'none'
                }
            },
            componentUpdated(el, binding, vnode) {
                if(binding.value != 0) {
                    el.style.display = 'block'
                } else {
                    el.style.display = 'none'
                }
            }
        }
    },
    filters: {
        twoDecimalPlacesFormat
    }
}
</script>

<style>
    .otherMarketsBtn {
        bottom: 5px;
        right: 15px;
    }

    .europeanOtherMarketsBtn {
        right: -16px;
        bottom: -25px;
    }

    .gameSchedPanel {
        border-bottom: solid #ffe8cc 1px;
    }

    .noeventspanel {
        height:34px;
    }

    .gameColumn {
        width: 90px;
    }

    .teamColumn {
        width:140px;
    }

    .leaguePanel {
        background-color: #ffe8cc;
    }

    .alternateEvent {
        background-color: #f1f1f1;
    }

    .fa-star {
        filter:drop-shadow(0px 0px 1px #000000);
        z-index:1;
    }

    .fa-star:hover {
        color: #fff200;
    }

    .in-watchlist-star {
        color: #fff200;
    }

    .eventStar {
        right: 15px;
    }

    .european-event-star {
        right:-17px;
    }

    .game:not(:last-child) {
        border-bottom: solid #edf2f7 1px;
    }

    .bet-click:hover {
        color: #ffffff;
        cursor: pointer;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .hg:hover, .provider.hg {
        background-color: #8B0000;
    }

    .pin:hover, .provider.pin {
        background-color: #ed8936;
    }

    .provider {
        color: #ffffff;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .odds-label {
        width: 50px;
    }

        .european-left-label {
            right: 35px;
            width: 30px !important;
        }

        .left-label {
            left: -52px;
            text-align: right;
        }

        .empty-left-label {
            left: -63px;
            text-align: right;
        }

        .right-label {
            right: -52px;
            text-align: left;
        }

    .bet-click.ping-danger {
        animation-name: ping-danger;
        animation-duration: 2s;
        animation-iteration-count: 1;
    }

    .bet-click.ping-success {
        animation-name: ping-success;
        animation-duration: 2s;
        animation-iteration-count: 1;
    }

    @keyframes ping-danger{
        from {
            color:#ffffff;
            background-color: #d9534f;
            font-weight: 700;
        } to {
            color: rgba(74, 85, 104, 1);
            background-color: none;
            font-weight: 400;
        }
    }

    @keyframes ping-success{
        from {
            color:#ffffff;
            background-color: #5cb85c;
            font-weight: 700;
        } to {
            color: rgba(74, 85, 104, 1);
            background-color:none;
            font-weight: 400;
        }
    }
</style>
