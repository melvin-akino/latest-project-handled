import Vue from 'vue'
import Cookies from 'js-cookie'
const token = Cookies.get('mltoken')

const state = {
    selectedSport: null,
    selectedLeagues: [],
    leaguesData: {},
    isBetBarOpen: false,
    tradeLayout: null,
    filteredColumnsBySport: [],
    oddsTypeBySport: [],
    columnsToDisplay: [],
    checkedColumns: [],
    initialLeagues: [],
    eventsList: [],
    events: {
        watchlist: [],
        inplay: [],
        today: [],
        early: []
    }
}

const mutations = {
    FETCH_LEAGUES_DATA:(state, data) => {
        state.leaguesData = data
    },
    SET_SELECTED_SPORT: (state, selectedSport) => {
        state.selectedSport = selectedSport
    },
    SET_SELECTED_LEAGUES: (state, selectedLeague) => {
        state.selectedLeagues.push(selectedLeague)
    },
    REMOVE_SELECTED_LEAGUE: (state, removedLeague) => {
        state.selectedLeagues = state.selectedLeagues.filter(league => league != removedLeague)
    },
    CLEAR_SELECTED_LEAGUES: (state) => {
        state.selectedLeagues = []
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
    SET_EVENTS: (state, data) => {
        state.events[data.schedule] = data.events
    },
    REMOVE_FROM_EVENTS: (state, data) => {
        Vue.delete(state.events[data.schedule], data.removedLeague)
        state.eventsList = state.eventsList.filter(event => event.league_name != data.removedLeague)
    },
    REMOVE_EVENT: (state, data) => {
        state.events[data.schedule][data.removedLeague] = state.events[data.schedule][data.removedLeague].filter(event => event.uid != data.removedEvent)
    },
    ADD_TO_WATCHLIST: (state, data) => {
        
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
            betColumns.filter(column => column.sport_id === selectedSport).map(column => state.filteredColumnsBySport = column.odds)
            state.columnsToDisplay = state.filteredColumnsBySport.filter(column => !rootState.settings.disabledBetColumns.includes(column.sport_odd_type_id))
            state.oddsTypeBySport = state.filteredColumnsBySport.filter(column => !rootState.settings.disabledBetColumns.includes(column.sport_odd_type_id)).map(column => column.type)
            state.checkedColumns = state.columnsToDisplay.map(column => column.sport_odd_type_id)
        } catch(err) {
            dispatch('auth/checkIfTokenIsValid', err.response.data.status_code,  { root: true })
        }
    },
    getInitialLeagues({commit, dispatch, state}) {
        return new Promise((resolve, reject) => {
            axios.get('v1/trade/leagues', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                if(response.data.sport_id === state.selectedSport) {
                    commit('SET_INITIAL_LEAGUES', response.data.data)
                    resolve(state.initialLeagues)
                }
            })
            .catch(err => {
                dispatch('auth/checkIfTokenIsValid', err.response.data.status_code,  { root: true })
                reject(err)
            })
        })
    }
}

export default {
    state, mutations, actions, namespaced: true
}
