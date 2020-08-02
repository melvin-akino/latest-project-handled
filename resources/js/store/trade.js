import Vue from 'vue'
import _ from 'lodash'
import { sortByObjectKeys, sortByObjectProperty } from '../helpers/array'
import Cookies from 'js-cookie'
const token = Cookies.get('mltoken')

const state = {
    leagues: [],
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
    allEventsList: [],
    eventsList: [],
    events: {
        watchlist: {},
        inplay: {},
        today: {},
        early: {}
    },
    watchlist: [],
    previouslySelectedEvents: [],
    openedBetSlips: [],
    bookies: [],
    bets: [],
    tradePageSettings: {},
    betSlipSettings: {},
    showSearch: true,
    activeBetSlip: null,
    wallet: {},
    isLoadingLeagues: true,
    isLoadingEvents: true,
    failedBetStatus: ['PENDING', 'FAILED', 'CANCELLED', 'REJECTED', 'VOID', 'ABNORMAL BET', 'REFUNDED'],
    eventsError: false
}

const getters = {
    displayedLeagues: (state) => {
        return state.leagues[state.selectedLeagueSchedMode]
    }
}

const mutations = {
    SET_LEAGUES: (state, leagues) => {
        state.leagues = leagues
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
            state.leagues[data.schedule] = state.leagues[data.schedule].filter(league => league.name != data.league)
        }
    },
    REMOVE_FROM_LEAGUE_BY_NAME: (state, data) => {
        Object.keys(state.leagues).map(schedule => {
            state.leagues[schedule] = state.leagues[schedule].filter(league => league.name != data.league)
        })
    },
    UPDATE_LEAGUE_MATCH_COUNT: (state, data) => {
        state.leagues[data.schedule].map(league => {
            if(league.name == data.league) {
                let events = state.eventsList.filter(event => event.game_schedule == data.schedule && event.league_name == data.league)
                if(events.length > 0) {
                    if(data.watchlist == 'add') {
                        Vue.set(league, 'match_count', events.length - 1)
                    } else if(data.watchlist == 'remove') {
                        Vue.set(league, 'match_count', events.length + 1)
                    } else {
                        Vue.set(league, 'match_count', events.length)
                    }
                } else {
                    Vue.set(league, 'match_count', data.eventsRemaining)
                }
            }
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
        state.selectedLeagues[data.schedule] = state.selectedLeagues[data.schedule].filter(league => league != data.league)
    },
    REMOVE_SELECTED_LEAGUE_BY_NAME: (state, removedLeague) => {
        let schedule = ['inplay', 'today', 'early']
        schedule.map(schedule => {
            state.selectedLeagues[schedule] = state.selectedLeagues[schedule].filter(league => league != removedLeague)
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
    SET_EVENTS_LIST: (state, event) => {
        state.eventsList.push(event)
        state.eventsList = _.uniqBy(state.eventsList, 'uid')
    },
    SET_ALL_EVENTS_LIST: (state, event) => {
        state.allEventsList.push(event)
        state.allEventsList = _.uniqBy(state.allEventsList, 'uid')
    },
    SET_PREVIOUSLY_SELECTED_EVENTS: (state, event) => {
        state.previouslySelectedEvents.push(event)
    },
    REMOVE_FROM_EVENT_LIST: (state, data) => {
        state.eventsList = state.eventsList.filter(event => {
            let _string = `${ data.data }_${ data.game_schedule }`
            let _dataString = `${ event[data.type] }_${ event.game_schedule }`;
            return _string != _dataString
        })
    },
    REMOVE_FROM_ALL_EVENT_LIST: (state, data) => {
        state.allEventsList = state.allEventsList.filter(event => {
            let _string = `${ data.data }_${ data.game_schedule }`
            let _dataString = `${ event[data.type] }_${ event.game_schedule }`;
            return _string != _dataString
        })
    },
    REMOVE_FROM_PREVIOUSLY_SELECTED_EVENT_LIST: (state, data) => {
        state.previouslySelectedEvents = state.previouslySelectedEvents.filter(uid => uid != data)
    },
    CLEAR_EVENTS: (state) => {
        Object.keys(state.events).map(key => {
            state.events[key] = {}
        })
    },
    CLEAR_EVENTS_LIST: (state, event) => {
        state.eventsList = []
    },
    CLEAR_ALL_EVENTS_LIST: (state, event) => {
        state.allEventsList = []
    },
    SET_EVENTS: (state, data) => {
        Vue.set(state.events, data.schedule, data.events)
    },
    ADD_TO_EVENTS: (state, data) => {
        if(typeof(state.events[data.schedule][data.league]) == "undefined") {
            state.events[data.schedule][data.league] = []
        }
        state.events[data.schedule][data.league].push(data.event)
        state.events[data.schedule][data.league] = sortByObjectProperty(state.events[data.schedule][data.league], 'ref_schedule')
    },
    REMOVE_FROM_EVENTS: (state, data) => {
        if(state.tradePageSettings.sort_event == 1) {
            Vue.delete(state.events[data.schedule], data.removedLeague)
        } else if(state.tradePageSettings.sort_event == 2) {
            state.allEventsList.map(event => {
                if(event.league_name == data.removedLeague) {
                    let eventSchedLeague = `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`
                    Vue.delete(state.events[data.schedule], eventSchedLeague)
                }
            })
        }
    },
    REMOVE_FROM_EVENTS_BY_LEAGUE: (state, removedLeague) => {
        let schedule = ['inplay', 'today', 'early']
        schedule.map(schedule => {
            if(state.tradePageSettings.sort_event == 1) {
                Vue.delete(state.events[schedule], removedLeague)
            } else if(state.tradePageSettings.sort_event == 2) {
                state.allEventsList.map(event => {
                    if(event.league_name == removedLeague) {
                        let eventSchedLeague = `[${event.ref_schedule.split(' ')[1]}] ${event.league_name}`
                        Vue.delete(state.events[schedule], eventSchedLeague)
                    }
                })
            }
        })
    },
    REMOVE_EVENT: (state, data) => {
        state.events[data.schedule][data.removedLeague] = state.events[data.schedule][data.removedLeague].filter(event => event.uid != data.removedEvent)
    },
    SET_WATCHLIST: (state, watchlist) => {
        Vue.set(state.events, 'watchlist', watchlist)
    },
    OPEN_BETSLIP: (state, data) => {
        Vue.set(data.odd, 'game', data.game)
        Vue.set(data.odd, 'has_bet', false)
        state.openedBetSlips.push(data.odd)
    },
    CLOSE_BETSLIP: (state, market_id) => {
        state.openedBetSlips = state.openedBetSlips.filter(openedBetSlip => openedBetSlip.market_id != market_id)
    },
    SHOW_BET_MATRIX_IN_BETSLIP: (state, data) => {
        if(!_.isEmpty(state.openedBetSlips)) {
            state.openedBetSlips.map(betSlip => {
                if(betSlip.market_id == data.market_id) {
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
    SHOW_SEARCH: (state, data) => {
        state.showSearch = data
    },
    SET_ACTIVE_BETSLIP: (state, data) => {
        state.activeBetSlip = data
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
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code,  { root: true })
        }
    },
    getBookies({commit, dispatch}) {
        return axios.get('v1/bookies', { headers: { 'Authorization': `Bearer ${token}` }})
        .then(response => {
            commit('SET_BOOKIES', response.data.data)
        })
        .catch(err => {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
        })
    },
    getSports({commit, dispatch}) {
        return axios.get('v1/sports', { headers: { 'Authorization': `Bearer ${token}` } })
        .then(response => {
            commit('SET_SPORTS', response.data.data)
            commit('SET_SELECTED_SPORT', response.data.default_sport)
        })
        .catch(err => {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
        })
    },
    getInitialLeagues({commit, dispatch, state}) {
        return axios.get('v1/trade/leagues', { headers: { 'Authorization': `Bearer ${token}` }})
        .then(response => {
            if(response.data.sport_id == state.selectedSport) {
                commit('SET_LEAGUES', response.data.data)
            }
            commit('SET_IS_LOADING_LEAGUES', false)
        })
        .catch(err => {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
        })
    },
    getInitialEvents({commit, dispatch, state}) {
        axios.get('v1/trade/events', { headers: { 'Authorization': `Bearer ${token}` }})
        .then(response => {
            let schedule = ['inplay', 'today', 'early']
            schedule.map(schedule => {
                if(response.data.data.user_selected.hasOwnProperty(schedule)) {
                    let sortedUserSelected = {}
                    Object.keys(response.data.data.user_selected[schedule]).sort().map(league => {
                        if(typeof(sortedUserSelected[schedule]) == "undefined") {
                            sortedUserSelected[schedule] = {}
                        }
                        sortedUserSelected[schedule][league] = response.data.data.user_selected[schedule][league]
                        sortedUserSelected[schedule][league] = sortByObjectProperty(sortedUserSelected[schedule][league], 'ref_schedule')
                        sortedUserSelected[schedule][league].map(event => {
                            if(event.sport_id == state.selectedSport) {
                                commit('SET_EVENTS', { schedule: schedule, events: sortedUserSelected[schedule]})
                                commit('SET_EVENTS_LIST', event)
                                commit('SET_ALL_EVENTS_LIST', event)
                            }
                        })
                    })
                }
            })

            let sortedUserWatchlist = {}
            Object.keys(response.data.data.user_watchlist).sort().map(league => {
                if(typeof(sortedUserWatchlist[league]) == "undefined") {
                    sortedUserWatchlist[league] = {}
                }
                sortedUserWatchlist[league] = response.data.data.user_watchlist[league]
                sortedUserWatchlist[league] = sortByObjectProperty(sortedUserWatchlist[league], 'ref_schedule')
                sortedUserWatchlist[league].map(event => {
                    commit('SET_WATCHLIST', sortedUserWatchlist)
                    commit('SET_ALL_EVENTS_LIST', event)
                })
            })
            commit('SET_IS_LOADING_EVENTS', false)
        })
        .catch(err => {
            if(err.response.data.status_code != 500) {
                dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
            } else {
                commit('SET_IS_LOADING_EVENTS', false)
                commit('SET_EVENTS_ERROR', true)
            }
        })
    },
    async getTradeWindowData({dispatch}) {
        await dispatch('getSports')
        await dispatch('getBetColumns', state.selectedSport)
        await dispatch('getInitialLeagues')
        Vue.prototype.$socket.send(`getSelectedLeagues_${state.selectedSport}`)
        dispatch('getInitialEvents')
        await dispatch('getBetbarData')
        dispatch('getOrders')
    },
    getBetbarData({commit, state, dispatch}) {
        return axios.get('v1/trade/betbar', { headers: { 'Authorization': `Bearer ${token}` }})
        .then(response => {
            commit('SET_BETS', response.data.data)
            if(state.bets.length != 0) {
                commit('TOGGLE_BETBAR', true)
            }
        })
        .catch(err => {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
        })
    },
    getOrders() {
        state.bets.map(bet => {
            Vue.prototype.$socket.send(`getOrder_${bet.order_id}`)
        })
    },
    getWalletData({commit, dispatch}) {
        axios.get('v1/user/wallet', { headers: { 'Authorization': `Bearer ${token}` }})
        .then(response => {
            commit('SET_WALLET', response.data.data)
        })
        .catch(err => {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
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
        axios.post(`v1/trade/leagues/toggle/${data.action}`, { league_name: data.league_name, sport_id: data.sport_id, schedule: data.schedule}, { headers: { 'Authorization': `Bearer ${token}` } })
        .catch(err => {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
        })
    },
    toggleLeagueByName({dispatch}, data) {
        let schedule = ['inplay', 'today', 'early']
        schedule.map(schedule => {
            if(state.selectedLeagues[schedule].length != 0 && state.selectedLeagues[schedule].includes(data.league_name)) {
                dispatch('toggleLeague', { action: data.action, league_name: data.league_name, sport_id: data.sport_id, schedule: schedule })
            }
        })
    },
    async addToWatchlist({dispatch, state, commit}, data) {
        try {

            await axios.post('v1/trade/watchlist/add', { type: data.type, data: data.data }, { headers: { 'Authorization': `Bearer ${token}` }})
            Vue.prototype.$socket.send('getWatchlist')
            if(data.type=='league') {
                await dispatch('toggleLeagueByName', { action: 'remove', league_name: data.data, sport_id: state.selectedSport })
                commit('REMOVE_FROM_LEAGUE_BY_NAME', { league: data.data })
                commit('REMOVE_SELECTED_LEAGUE_BY_NAME', data.data)
                commit('REMOVE_FROM_EVENTS_BY_LEAGUE', data.data)
                commit('REMOVE_FROM_EVENT_LIST', { type: 'league_name', data: data.data, game_schedule: data.game_schedule })
            } else if(data.type=='event') {
                if(!_.isEmpty(data.payload)) {
                    let leaguesLength = 0
                    if(state.tradePageSettings.sort_event == 1) {
                        commit('REMOVE_EVENT', { schedule: data.payload.game_schedule, removedLeague: data.payload.league_name, removedEvent: data.payload.uid})
                        leaguesLength = state.events[data.payload.game_schedule][data.payload.league_name].length
                    } else if(state.tradePageSettings.sort_event == 2) {
                        let eventStartTime = `[${data.payload.ref_schedule.split(' ')[1]}] ${data.payload.league_name}`
                        commit('REMOVE_EVENT', { schedule: data.payload.game_schedule, removedLeague: eventStartTime, removedEvent: data.payload.uid})
                        leaguesLength = state.events[data.payload.game_schedule][eventStartTime].length
                    }

                    if(leaguesLength == 0) {
                        await dispatch('toggleLeague', { action: 'remove', league_name: data.payload.league_name,  schedule: data.payload.game_schedule, sport_id: state.selectedSport })
                        commit('REMOVE_SELECTED_LEAGUE', {schedule: data.payload.game_schedule, league: data.payload.league_name })
                        commit('REMOVE_FROM_LEAGUE', {schedule: data.payload.game_schedule, league: data.payload.league_name })
                        commit('REMOVE_FROM_EVENTS', { schedule: data.payload.game_schedule, removedLeague: data.payload.league_name })
                    } else {
                        commit('UPDATE_LEAGUE_MATCH_COUNT', { schedule: data.payload.game_schedule, league: data.payload.league_name, eventsRemaining: leaguesLength, watchlist: 'add' })
                    }
                    commit('REMOVE_FROM_EVENT_LIST', { type: 'uid', data: data.payload.uid, game_schedule: data.payload.game_schedule })
                }
            }
        } catch(err) {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
        }
    },
    transformEvents({commit, state}, data) {
        if(data.league in state.events[data.schedule]) {
            Object.keys(state.events[data.schedule]).map(league => {
                let checkEventUID = state.events[data.schedule][league].findIndex(event => event.uid === data.payload.uid)
                if(data.league == league && checkEventUID === -1) {
                    state.events[data.schedule][league].push(data.payload)
                    let sortedEventObject = {}
                    let sortedEvents = sortByObjectKeys(state.events[data.schedule], sortedEventObject[data.schedule], 'ref_schedule')
                    commit('SET_EVENTS', { schedule: data.schedule, events: sortedEvents })
                }
            })
        } else {
            if(typeof(state.events[data.schedule][data.league]) == "undefined") {
                state.events[data.schedule][data.league] = []
            }
            state.events[data.schedule][data.league].push(data.payload)
            let sortedEventObject = {}
            let sortedEvents = sortByObjectKeys(state.events[data.schedule], sortedEventObject[data.schedule], 'ref_schedule')
            commit('SET_EVENTS', { schedule: data.schedule, events: sortedEvents })
        }
    },
    getOrderLogs({dispatch}, market_id) {
        return new Promise((resolve, reject) => {
            axios.get(`v1/orders/logs/${market_id}`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                resolve(response.data.data)
            })
            .catch(err => {
                dispatch('auth/checkIfTokenIsValid', err.response.data.status_code,  { root: true })
                reject(err)
            })
        })
    }
}

export default {
    state, getters, mutations, actions, namespaced: true
}
