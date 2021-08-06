import Vue from 'vue'
import _ from 'lodash'
import { sortByObjectKeys, sortByObjectProperty } from '../helpers/array'
import Cookies from 'js-cookie'
const token = Cookies.get('mltoken')
import Swal from 'sweetalert2'

const state = {
    leagues: {
        inplay: [],
        today: [],
        early: []
    },
    selectedLeagueSchedMode: Cookies.get('leagueSchedMode') || 'today',
    sports: [],
    selectedSport: null,
    selectedLeagues: {
        inplay: [],
        today: [],
        early: []
    },
    isBetBarOpen: false,
    tradeLayout: null,
    filteredColumnsBySport: [],
    oddsTypeBySport: [],
    columnsToDisplay: [],
    checkedColumns: [],
    eventsList: [],
    openedBetSlips: [],
    bookies: [],
    bets: [],
    tradePageSettings: {},
    betSlipSettings: {},
    showSearch: true,
    activePopup: null,
    wallet: {},
    isLoadingLeagues: true,
    isLoadingEvents: true,
    failedBetStatus: ['PENDING', 'FAILED', 'CANCELLED', 'REJECTED', 'VOID', 'ABNORMAL BET', 'REFUNDED'],
    eventsError: false,
    underMaintenanceProviders: [],
    popupZIndex: 100,
    receivedOrderStatusIds: []
}

const getters = {
    displayedLeagues: (state) => {
        return state.leagues[state.selectedLeagueSchedMode]
    },
    leagueNames: (state) => {
        if(!_.isEmpty(state.leagues)) {
            let leagueNames = {}
            Object.keys(state.leagues).map(schedule => {
                let leagues = []
                state.leagues[schedule].map(league => {
                    leagues.push(league.name)
                })
                Vue.set(leagueNames, schedule, leagues)
            })
            return leagueNames
        }
    },
    events: (state) => {
        let schedule = _.uniq(state.eventsList.map(event => event.game_schedule))
        let leagues = _.uniq(state.eventsList.map(event => event.league_name))
        let eventStartTime = _.uniq(state.eventsList.map(event => `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`))
        let eventObject = {
            watchlist: {},
            inplay: {},
            today: {},
            early: {}
        }
        schedule.map(schedule => {
            if(state.tradePageSettings.sort_event == 1) {
                leagues.map(league => {
                    state.eventsList.map(event => {
                        if(schedule == event.game_schedule && league == event.league_name) {
                            let eventSchedule = event.hasOwnProperty('watchlist') ? 'watchlist' : event.game_schedule
                            if(typeof(eventObject[eventSchedule][league]) == "undefined") {
                                eventObject[eventSchedule][league] = []
                            }
                            eventObject[eventSchedule][league].push(event)
                            eventObject[eventSchedule] = sortByObjectKeys(eventObject[eventSchedule], 'ref_schedule', 'uid')
                        }
                    })
                })
            } else {
                eventStartTime.map(startTime => {
                    state.eventsList.map(event => {
                        let eventSchedLeague = `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`
                        if(schedule == event.game_schedule && startTime == eventSchedLeague) {
                            let eventSchedule = event.hasOwnProperty('watchlist') ? 'watchlist' : event.game_schedule
                            if(typeof(eventObject[eventSchedule][startTime]) == "undefined") {
                                eventObject[eventSchedule][startTime] = []
                            }
                            eventObject[eventSchedule][startTime].push(event)
                            eventObject[eventSchedule] = sortByObjectKeys(eventObject[eventSchedule], 'ref_schedule', 'uid')
                        }
                    })
                })
            }
        })
        return eventObject
    }
}

const mutations = {
    SET_LEAGUES: (state, leagues) => {
        state.leagues = leagues
    },
    SET_LEAGUES_BY_SCHEDULE: (state, data) => {
        state.leagues[data.schedule] = data.leagues
    },
    CHANGE_LEAGUE_SCHED_MODE: (state, data) => {
        state.selectedLeagueSchedMode = data
    },
    ADD_TO_LEAGUES: (state, data) => {
        if(state.leagues.hasOwnProperty(data.schedule)) {
            state.leagues[data.schedule].push(data.league)
            state.leagues[data.schedule] = sortByObjectProperty(state.leagues[data.schedule], 'name')
        }
    },
    REMOVE_FROM_LEAGUE: (state, data) => {
        if(state.leagues.hasOwnProperty(data.schedule)) {
            state.leagues[data.schedule] = state.leagues[data.schedule].filter(league => league.master_league_id != data.league)
        }
    },
    REMOVE_FROM_LEAGUE_BY_NAME: (state, data) => {
        Object.keys(state.leagues).map(schedule => {
            state.leagues[schedule] = state.leagues[schedule].filter(league => league.master_league_id != data.league)
        })
    },
    CLEAR_LEAGUES: (state) => {
        Object.keys(state.leagues).map(schedule => {
            state.leagues[schedule] = []
        })
    },
    SET_SPORTS: (state, sports) => {
        state.sports = sports
    },
    SET_SELECTED_SPORT: (state, selectedSport) => {
        state.selectedSport = selectedSport
    },
    SET_SELECTED_LEAGUES: (state, selectedLeagues) => {
        state.selectedLeagues = selectedLeagues
    },
    ADD_TO_SELECTED_LEAGUE: (state, data) => {
        if(!state.selectedLeagues[data.schedule].includes(data.league)) {
            state.selectedLeagues[data.schedule].push(data.league)
        }
    },
    REMOVE_SELECTED_LEAGUE: (state, data) => {
        state.selectedLeagues[data.schedule] = state.selectedLeagues[data.schedule].filter(league => league.master_league_id != data.league)
    },
    REMOVE_SELECTED_LEAGUE_BY_NAME: (state, removedLeague) => {
        Object.keys(state.selectedLeagues).map(schedule => {
            state.selectedLeagues[schedule] = state.selectedLeagues[schedule].filter(league => league.master_league_id != removedLeague)
        })
    },
    CLEAR_SELECTED_LEAGUES: (state, data) => {
        let schedule = ['inplay', 'today', 'early']
        schedule.map(schedule => {
            state.selectedLeagues[schedule] = []
        })
    },
    TOGGLE_BETBAR: (state, status) => {
        state.isBetBarOpen = status
    },
    SET_TRADE_LAYOUT: (state, layout) => {
        state.tradeLayout = layout
    },
    SET_CHECKED_COLUMNS: (state, columns) => {
        state.checkedColumns = columns
    },
    SET_EVENTS_LIST: (state, newEvent) => {
        let eventsListUID = state.eventsList.map(event => event.uid)
        if(eventsListUID.includes(newEvent.uid)) {
            state.eventsList.map(event => {
                if(event.uid == newEvent.uid) {
                    Vue.set(event, 'league_name', newEvent.league_name)
                    Vue.set(event.home, 'name', newEvent.home.name)
                    Vue.set(event.away, 'name', newEvent.away.name)
                    Vue.set(event, 'game_schedule', newEvent.game_schedule)
                    Vue.set(event.home, 'score', newEvent.home.score)
                    Vue.set(event.away, 'score', newEvent.away.score)
                    Vue.set(event, 'running_time', newEvent.running_time)
                    Vue.set(event, 'with_providers', newEvent.with_providers)
                    if(event.hasOwnProperty('market_odds') && newEvent.hasOwnProperty('market_odds')) {
                        Vue.set(event.market_odds, 'main', newEvent.market_odds.main)
                        if(newEvent.market_odds.hasOwnProperty('other')) {
                            Vue.set(event.market_odds, 'other', newEvent.market_odds.other)
                        }
                    } else if(!event.hasOwnProperty('market_odds') && newEvent.hasOwnProperty('market_odds')) {
                        Vue.set(event, 'market_odds', newEvent.market_odds)
                    } else if(event.hasOwnProperty('market_odds') && !newEvent.hasOwnProperty('market_odds')) {
                        Vue.delete(event, 'market_odds')
                    }
                }
            })
        } else {
            state.eventsList.push(newEvent)
        }
    },
    REMOVE_FROM_EVENT_LIST: (state, data) => {
        state.eventsList.map(event => {
            if(event.master_league_id == data.master_league_id && event.game_schedule == data.game_schedule && !event.hasOwnProperty('watchlist')) {
                if(data.hasOwnProperty('uid')) {
                    state.eventsList = state.eventsList.filter(filteredEvent => filteredEvent.uid != data.uid)
                } else {
                    state.eventsList = state.eventsList.filter(filteredEvent => filteredEvent.uid != event.uid)
                }
            }
        })
    },
    REMOVE_ALL_FROM_EVENT_LIST: (state, data) => {
        if(data.hasOwnProperty('uid')) {
            state.eventsList = state.eventsList.filter(event => event.uid != data.uid)
        } else {
            state.eventsList = state.eventsList.filter(event => event.master_league_id != data.master_league_id && event.game_schedule != data.game_schedule)
        }
    },
    REMOVE_FROM_EVENT_LIST_BY_LEAGUE: (state, data) => {
        state.eventsList.map(event => {
            if(event.master_league_id == data.master_league_id && !event.hasOwnProperty('watchlist')) {
                state.eventsList = state.eventsList.filter(filteredEvent => filteredEvent.uid != event.uid)
            }
        })
    },
    CLEAR_EVENTS_LIST: (state) => {
        state.eventsList = []
    },
    REMOVE_FROM_WATCHLIST: (state, uid) => {
        state.eventsList.map(event => {
            if(event.uid == uid) {
                Vue.delete(event, 'watchlist')
            }
        })
    },
    CLEAR_WATCHLIST: (state) => {
        state.eventsList = state.eventsList.filter(event => !event.hasOwnProperty('watchlist'))
    },
    OPEN_BETSLIP: (state, data) => {
        let openedBetSlips = state.openedBetSlips.map(betSlips => betSlips.betslip_id)
        let betslipId = `${data.game.uid}-${data.odd.market_id}`
        if(!openedBetSlips.includes(betslipId)) {
            let betslip = {
                odd: data.odd,
                game: data.game,
                betslip_id: betslipId,
                has_bet: false
            }
            state.openedBetSlips.push(betslip)
        }
    },
    CLOSE_BETSLIP: (state, betslip_id) => {
        state.openedBetSlips = state.openedBetSlips.filter(openedBetSlip => openedBetSlip.betslip_id != betslip_id)
    },
    SHOW_BET_MATRIX_IN_BETSLIP: (state, data) => {
        if(!_.isEmpty(state.openedBetSlips)) {
            state.openedBetSlips.map(betSlip => {
                if(betSlip.odd.market_id == data.market_id) {
                    Vue.set(betSlip, 'has_bet', data.has_bet)
                }
            })
        }
    },
    SET_BOOKIES: (state, bookies) => {
        state.bookies = bookies
    },
    SET_BETS: (state, bets) => {
        state.bets = bets
    },
    SET_TRADE_PAGE_SETTINGS: (state, tradePageSettings) => {
        state.tradePageSettings = tradePageSettings
    },
    SET_BET_SLIP_SETTINGS: (state, betSlipSettings) => {
        state.betSlipSettings = betSlipSettings
    },
    UPDATE_BET_SLIP_SETTINGS: (state, data) => {
        state.betSlipSettings[data.key] = data.value
    },
    SHOW_SEARCH: (state, data) => {
        state.showSearch = data
    },
    SET_ACTIVE_POPUP: (state, data) => {
        state.activePopup = data
    },
    SET_WALLET: (state, data) => {
        state.wallet = data
    },
    SET_IS_LOADING_LEAGUES: (state, data) => {
        state.isLoadingLeagues = data
    },
    SET_IS_LOADING_EVENTS: (state, data) => {
        state.isLoadingEvents = data
    },
    SET_EVENTS_ERROR: (state, data) => {
        state.eventsError = data
    },
    ADD_TO_UNDER_MAINTENANCE_PROVIDERS: (state, provider) => {
        if(!state.underMaintenanceProviders.includes(provider)) {
            state.underMaintenanceProviders.push(provider)
        }
    },
    REMOVE_FROM_UNDER_MAINTENANCE_PROVIDERS: (state, provider) => {
        if(state.underMaintenanceProviders.includes(provider)) {
            state.underMaintenanceProviders = state.underMaintenanceProviders.filter(underMaintenanceProvider => underMaintenanceProvider != provider)
        }
    },
    SET_POPUP_ZINDEX: (state) => {
        state.popupZIndex++
    },
    SET_RECEIVED_ORDER_STATUS_ID: (state, id) => {
        state.receivedOrderStatusIds.push(id)
    }
}

const actions = {
    async getBetColumns({commit, dispatch, state, rootState}, selectedSport) {
        try {
            let response = await axios.get('v1/sports/odds', { headers: { 'Authorization': `Bearer ${token}` }})
            let settings = await dispatch('settings/getUserSettingsConfig', 'bet-columns',  { root: true })
            let betColumns = response.data.data
            let { disabled_columns } = settings
            commit('settings/FETCH_DISABLED_COLUMNS', disabled_columns, { root:true })
            betColumns.filter(column => column.sport_id == selectedSport).map(column => state.filteredColumnsBySport = column.odds)
            state.columnsToDisplay = state.filteredColumnsBySport.filter(column => !rootState.settings.disabledBetColumns.includes(column.sport_odd_type_id))
            state.oddsTypeBySport = state.filteredColumnsBySport.filter(column => !rootState.settings.disabledBetColumns.includes(column.sport_odd_type_id)).map(column => column.type)
            state.checkedColumns = state.columnsToDisplay.map(column => column.sport_odd_type_id)
        } catch(err) {
            dispatch('auth/checkIfTokenIsValid', err.response.status,  { root: true })
        }
    },
    getBookies({commit, dispatch}) {
        return axios.get('v1/bookies', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                commit('SET_BOOKIES', response.data.data)
            })
            .catch(err => {
                dispatch('auth/checkIfTokenIsValid', err.response.status, { root: true })
            })
    },
    getSports({commit, dispatch}) {
        return axios.get('v1/sports', { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                commit('SET_SPORTS', response.data.data)
                commit('SET_SELECTED_SPORT', response.data.default_sport)
            })
            .catch(err => {
                dispatch('auth/checkIfTokenIsValid', err.response.status, { root: true })
            })
    },
    getInitialLeagues({commit, dispatch, state}, updatedLeagues = false) {
        return axios.get('v1/trade/leagues', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                if(response.data.sport_id == state.selectedSport) {
                    Object.keys(state.leagues).map(schedule => {
                        let leagueIds = state.leagues[schedule].map(league => league.master_league_id)
                        let newLeagueIds = response.data.data[schedule].map(league => league.master_league_id)
                        let selectedLeaguesIds = state.selectedLeagues[schedule].map(league => league.master_league_id)
                        leagueIds.map(master_league_id => {
                            if(!newLeagueIds.includes(master_league_id)) {
                                if(selectedLeaguesIds.includes(master_league_id)) {
                                    commit('REMOVE_SELECTED_LEAGUE', { schedule: schedule, league: master_league_id })
                                    dispatch('toggleLeague', { action: 'remove', sport_id: state.selectedSport, schedule: schedule, master_league_id: master_league_id  })
                                }
                                commit('REMOVE_FROM_EVENT_LIST', { master_league_id: master_league_id, game_schedule: schedule })
                            }
                        })
                    })
                    commit('SET_LEAGUES', response.data.data)
                }
                commit('SET_IS_LOADING_LEAGUES', false)
            })
            .catch(err => {
                dispatch('auth/checkIfTokenIsValid', err.response.status, { root: true })
            })
    },
    getInitialEvents({commit, dispatch, state}) {
        axios.get('v1/trade/events', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                Object.keys(response.data.data.user_selected).map(schedule => {
                    Object.keys(response.data.data.user_selected[schedule]).map(league => {
                        response.data.data.user_selected[schedule][league].map(event => {
                            commit('SET_EVENTS_LIST', event)
                        })
                    })
                })
                Object.keys(response.data.data.user_watchlist).map(league => {
                    response.data.data.user_watchlist[league].map(event => {
                        Vue.set(event, 'watchlist', true)
                        commit('SET_EVENTS_LIST', event)
                    })
                })
                commit('SET_IS_LOADING_EVENTS', false)
            })
            .catch(err => {
                dispatch('auth/checkIfTokenIsValid', err.response.status, { root: true })
                commit('SET_IS_LOADING_EVENTS', false)
                commit('SET_EVENTS_ERROR', true)
            })
    },
    async getTradeWindowData({dispatch}) {
        await dispatch('getSports')
        await dispatch('getBetColumns', state.selectedSport)
        await dispatch('getInitialLeagues')
        await dispatch('getInitialEvents')
        await dispatch('getBetbarData')
        Vue.prototype.$socket.send(`getSelectedLeagues_${state.selectedSport}`)
    },
    async loadTradeWindow({dispatch, commit}) {
        if(Cookies.get('under_maintenance')) {
            Swal.fire({
                icon: 'warning',
                text: 'No Available Bookmaker.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false
            })
        }
        dispatch('getTradeWindowData')
    },
    getBetbarData({commit, state, dispatch}, market_id = null) {
        return axios.get('v1/trade/betbar', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                commit('SET_BETS', response.data.data)
                if(state.bets.length != 0) {
                    commit('TOGGLE_BETBAR', true)
                }

                if(market_id) {
                    let betToUpdate = state.bets.filter(bet => bet.market_id == market_id && !state.failedBetStatus.includes(bet.status))
                    if(betToUpdate.length != 0) {
                        commit('SHOW_BET_MATRIX_IN_BETSLIP', { market_id: market_id, has_bet: true })
                    }
                }
            })
            .catch(err => {
                dispatch('auth/checkIfTokenIsValid', err.response.status, { root: true })
            })
    },
    getWalletData({commit, dispatch}) {
        axios.get('v1/user/wallet', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                commit('SET_WALLET', response.data.data)
            })
            .catch(err => {
                dispatch('auth/checkIfTokenIsValid', err.response.status, { root: true })
            })
    },
    async getTradePageSettings({dispatch, commit}) {
        let tradePageSettings = await dispatch('settings/getUserSettingsConfig', 'trade-page', { root: true })
        commit('SET_TRADE_PAGE_SETTINGS', tradePageSettings)
        commit('SET_TRADE_LAYOUT', tradePageSettings.trade_layout)
    },
    async getBetSlipSettings({dispatch, commit}) {
        let betSlipSettings = await dispatch('settings/getUserSettingsConfig', 'bet-slip', { root: true })
        commit('SET_BET_SLIP_SETTINGS', betSlipSettings)
    },
    toggleLeague({dispatch}, data) {
        axios.post(`v1/trade/leagues/toggle/${data.action}`, { sport_id: data.sport_id, schedule: data.schedule, master_league_id: data.master_league_id }, { headers: { 'Authorization': `Bearer ${token}` } })
            .catch(err => {
                dispatch('auth/checkIfTokenIsValid', err.response.status, { root: true })
            })
    },
    toggleLeagueByName({dispatch}, data) {
        let schedule = ['inplay', 'today', 'early']
        schedule.map(schedule => {
            let selectedLeaguesIds = state.selectedLeagues[schedule].map(league => league.master_league_id)
            if(selectedLeaguesIds.length != 0 && selectedLeaguesIds.includes(data.master_league_id)) {
                dispatch('toggleLeague', { action: data.action, master_league_id: data.master_league_id, sport_id: data.sport_id, schedule: schedule })
            }
        })
    },
    async addToWatchlist({dispatch, state, commit}, data) {
        try {
            await axios.post('v1/trade/watchlist/add', { type: data.type, data: data.data }, { headers: { 'Authorization': `Bearer ${token}` }})
            Vue.prototype.$socket.send('getWatchlist')
            if(data.type=='league') {
                await dispatch('toggleLeagueByName', { action: 'remove', master_league_id: data.data, sport_id: state.selectedSport })
                commit('REMOVE_SELECTED_LEAGUE_BY_NAME', data.data)
                commit('REMOVE_FROM_LEAGUE_BY_NAME', { league: data.data })
                commit('REMOVE_FROM_EVENT_LIST_BY_LEAGUE', { master_league_id: data.data })
            } else if(data.type=='event') {
                if(data.payload) {
                    commit('REMOVE_FROM_EVENT_LIST',  { master_league_id: data.payload.master_league_id, game_schedule: data.payload.game_schedule, uid: data.data })
                    let leagueMatchCount = state.eventsList.filter(event => event.master_league_id == data.payload.master_league_id && event.game_schedule == data.payload.game_schedule && !event.hasOwnProperty('watchlist')).length
                    if(leagueMatchCount == 0) {
                        await dispatch('toggleLeague', { action: 'remove', master_league_id: data.payload.master_league_id,  schedule: data.payload.game_schedule, sport_id: state.selectedSport })
                        commit('REMOVE_SELECTED_LEAGUE', {schedule: data.payload.game_schedule, league: data.payload.master_league_id })
                        commit('REMOVE_FROM_LEAGUE', {schedule: data.payload.game_schedule, league: data.payload.master_league_id })
                    } else {
                        dispatch('updateLeagueMatchCount', { schedule: data.payload.game_schedule, league: { name: data.payload.league_name, master_league_id: data.payload.master_league_id }, match_count: leagueMatchCount })
                    }
                }
            }
        } catch(err) {
            dispatch('auth/checkIfTokenIsValid', err.response.status, { root: true })
        }
    },
    getOrderLogs({dispatch}, event_id) {
        return new Promise((resolve, reject) => {
            axios.get(`v1/orders/logs/${event_id}`, { headers: { 'Authorization': `Bearer ${token}` }})
                .then(response => {
                    resolve(response.data.data)
                })
                .catch(err => {
                    dispatch('auth/checkIfTokenIsValid', err.response.status,  { root: true })
                    reject(err)
                })
        })
    },
    updateOdds({state}, data) {
        let team = ['HOME', 'AWAY', 'DRAW']
        state.eventsList.map(event => {
            if(event.hasOwnProperty('market_odds')) {
                state.oddsTypeBySport.map(oddType => {
                    team.map(team => {
                        if(oddType in event.market_odds.main && team in event.market_odds.main[oddType]) {
                            if(event.market_odds.main[oddType][team].market_id === data.market_id) {
                                if(event.market_odds.main[oddType][team].odds != data.odds) {
                                    Vue.set(event.market_odds.main[oddType][team], 'odds', data.odds)
                                }
                                if(event.market_odds.main[oddType][team].hasOwnProperty('points') && event.market_odds.main[oddType][team].points != data.points && data.hasOwnProperty('points')) {
                                    Vue.set(event.market_odds.main[oddType][team], 'points', data.points)
                                }
                            }
                        }
                    })
                })
                if('other' in event.market_odds) {
                    Object.keys(event.market_odds.other).map(otherMarket => {
                        state.oddsTypeBySport.map(oddType => {
                            team.map(team => {
                                if(oddType in event.market_odds.other[otherMarket] && team in event.market_odds.other[otherMarket][oddType]) {
                                    if(event.market_odds.other[otherMarket][oddType][team].market_id === data.market_id) {
                                        if(event.market_odds.other[otherMarket][oddType][team].odds != data.odds) {
                                            Vue.set(event.market_odds.other[otherMarket][oddType][team], 'odds', data.odds)
                                        }
                                        if(event.market_odds.other[otherMarket][oddType][team].hasOwnProperty('points') && event.market_odds.other[otherMarket][oddType][team].points != data.points && data.hasOwnProperty('points')) {
                                            Vue.set(event.market_odds.other[otherMarket][oddType][team], 'points', data.points)
                                        }
                                    }
                                }
                            })
                        })
                    })
                }
            }
        })
    },
    setActivePopup({commit}, data) {
        commit('SET_ACTIVE_POPUP', data)
        commit('SET_POPUP_ZINDEX')
    },
    updateLeagueMatchCount({state, commit, dispatch}, data) {
        let isNotLeagueFound = true;
        state.leagues[data.schedule].map(league => {
            if(league.master_league_id == data.league.master_league_id) {
                isNotLeagueFound = false;
                if(data.hasOwnProperty('match_count')) {
                    Vue.set(league, 'match_count', data.match_count)
                } else {
                    let match_count = state.eventsList.filter(event => event.master_league_id == data.league.master_league_id && event.game_schedule == data.schedule && !event.hasOwnProperty('watchlist')).length
                    Vue.set(league, 'match_count', match_count)
                }
            }
        })
        if (isNotLeagueFound) {
            commit('ADD_TO_LEAGUES', { schedule: data.schedule, league: { name: data.league.name, master_league_id: data.league.master_league_id, match_count: 1 } })
            dispatch('toggleLeague', { action: 'add', sport_id: state.selectedSport, schedule: data.schedule, master_league_id: data.league.master_league_id  })
            commit('ADD_TO_SELECTED_LEAGUE', { schedule: data.schedule, league: data.league })
        }
    }
}

export default {
    state, getters, mutations, actions, namespaced: true
}
