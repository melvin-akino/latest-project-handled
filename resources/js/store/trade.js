import Vue from 'vue'
import _ from 'lodash'
import Cookies from 'js-cookie'
const token = Cookies.get('mltoken')

const state = {
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
    initialLeagues: [],
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
    openedOddsHistory: [],
    openedBetMatrix: [],
    bookies: [],
    bets: [],
    tradePageSettings: {},
    betSlipSettings: {}
}

const mutations = {
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
    SET_INITIAL_LEAGUES: (state, leagues) => {
        state.initialLeagues = leagues
    },
    SET_EVENTS_LIST: (state, event) => {
        state.eventsList.push(event)
    },
    SET_ALL_EVENTS_LIST: (state, event) => {
        state.allEventsList.push(event)
    },
    SET_PREVIOUSLY_SELECTED_EVENTS: (state, event) => {
        state.previouslySelectedEvents.push(event)
    },
    REMOVE_FROM_EVENT_LIST: (state, data) => {
        state.eventsList = state.eventsList.filter(event => event[data.type] != data.data)
    },
    REMOVE_FROM_ALL_EVENT_LIST: (state, data) => {
        state.allEventsList = state.allEventsList.filter(event => event[data.type] != data.data)
    },
    REMOVE_FROM_PREVIOUSLY_SELECTED_EVENT_LIST: (state, data) => {
        state.previouslySelectedEvents = state.previouslySelectedEvents.filter(uid => uid != data)
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
    },
    REMOVE_FROM_EVENTS: (state, data) => {
        Vue.delete(state.events[data.schedule], data.removedLeague)
        if(data.schedule != 'watchlist') {
            state.eventsList = state.eventsList.filter(event => event.league_name != data.removedLeague)
        }
    },
    REMOVE_FROM_EVENTS_BY_LEAGUE: (state, removedLeague) => {
        let schedule = ['inplay', 'today', 'early']
        schedule.map(schedule => {
            Vue.delete(state.events[schedule], removedLeague)
        })
    },
    REMOVE_EVENT: (state, data) => {
        state.events[data.schedule][data.removedLeague] = state.events[data.schedule][data.removedLeague].filter(event => event.uid != data.removedEvent)
    },
    SET_WATCHLIST: (state, watchlist) => {
        Vue.set(state.events, 'watchlist', watchlist)
    },
    OPEN_BETSLIP: (state, odd) => {
        state.openedBetSlips.push(odd)
    },
    CLOSE_BETSLIP: (state, market_id) => {
        state.openedBetSlips = state.openedBetSlips.filter(openedBetSlip => openedBetSlip.market_id != market_id)
    },
    OPEN_BET_MATRIX: (state, odd) => {
        state.openedBetMatrix.push(odd)
    },
    CLOSE_BET_MATRIX: (state, market_id) => {
        state.openedBetMatrix = state.openedBetMatrix.filter(betMatrix => betMatrix.market_id != market_id)
    },
    OPEN_ODDS_HISTORY: (state, odd) => {
        state.openedOddsHistory.push(odd)
    },
    CLOSE_ODDS_HISTORY: (state, market_id) => {
        state.openedOddsHistory = state.openedOddsHistory.filter(oddHistory => oddHistory.market_id != market_id)
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
        axios.get('v1/bookies', { headers: { 'Authorization': `Bearer ${token}` }})
        .then(response => {
            commit('SET_BOOKIES', response.data.data)
        })
        .catch(err => {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
        })
    },
    getInitialLeagues({commit, dispatch, state}) {
        return new Promise((resolve, reject) => {
            axios.get('v1/trade/leagues', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                if(response.data.sport_id == state.selectedSport) {
                    commit('SET_INITIAL_LEAGUES', response.data.data)
                    resolve(state.initialLeagues)
                }
            })
            .catch(err => {
                dispatch('auth/checkIfTokenIsValid', err.response.data.status_code,  { root: true })
                reject(err)
            })
        })
    },
    getInitialEvents({commit, dispatch, state}) {
        axios.get('v1/trade/events', { headers: { 'Authorization': `Bearer ${token}` }})
        .then(response => {
            if('user_selected' in response.data.data) {
                let schedule = ['inplay', 'today', 'early']
                schedule.map(schedule => {
                    if(schedule in response.data.data.user_selected) {
                        Object.keys(response.data.data.user_selected[schedule]).map(league => {
                            response.data.data.user_selected[schedule][league].map(event => {
                                if(event.sport_id == state.selectedSport) {
                                    commit('SET_EVENTS', { schedule: schedule, events: response.data.data.user_selected[schedule]})
                                    commit('SET_EVENTS_LIST', event)
                                    commit('SET_ALL_EVENTS_LIST', event)
                                }
                            })
                        })
                    }
                })
            }

            if('user_watchlist' in response.data.data) {
                commit('SET_WATCHLIST', response.data.data.user_watchlist)
                Object.keys(response.data.data.user_watchlist).map(league => {
                    response.data.data.user_watchlist[league].map(event => {
                        commit('SET_ALL_EVENTS_LIST', event)
                    })
                })
            }
        })
        .catch(err => {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
        })
    },
    getBetbarData({commit, state, dispatch}) {
        axios.get('v1/trade/betbar', { headers: { 'Authorization': `Bearer ${token}` }})
        .then(response => {
            commit('SET_BETS', response.data.data.reverse())
            if(state.bets.length != 0) {
                commit('TOGGLE_BETBAR', true)
            }
        })
        .catch(err => {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code, { root: true })
        })
    },
    async getTradePageSettings({dispatch, commit}) {
        let tradePageSettings = await dispatch('settings/getUserSettingsConfig', 'trade-page', { root: true })
        commit('SET_TRADE_PAGE_SETTINGS', tradePageSettings)
    },
    async getBetSlipSettings({dispatch, commit}) {
        let betSlipSettings = await dispatch('settings/getUserSettingsConfig', 'bet-slip', { root: true })
        commit('SET_BET_SLIP_SETTINGS', betSlipSettings)
    },
    toggleLeague(context, data) {
        axios.post('v1/trade/leagues/toggle', { league_name: data.league_name, sport_id: data.sport_id, schedule: data.schedule}, { headers: { 'Authorization': `Bearer ${token}` } })
        .catch(err => {
            this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
        })
    },
    toggleLeagueByName({dispatch}, data) {
        let schedule = ['inplay', 'today', 'early']
        schedule.map(schedule => {
            if(state.selectedLeagues[schedule].length != 0 && state.selectedLeagues[schedule].includes(data.league_name)) {
                dispatch('toggleLeague', { league_name: data.league_name, sport_id: data.sport_id, schedule: schedule })
            }
        })
    }
}

export default {
    state, mutations, actions, namespaced: true
}
