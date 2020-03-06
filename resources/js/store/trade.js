import Vue from 'vue'
//import Cookies from 'js-cookie'
//const token = Cookies.get('mltoken')
const token ='abc'

const state = {
    selectedSport: null,
    selectedLeagues:{
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
    CLEAR_EVENTS_LIST: (state, event) => {
        state.eventsList = []
    },
    SET_EVENTS: (state, data) => {
        state.events[data.schedule] = data.events
    },
    ADD_TO_EVENTS: (state, data) => {
        Vue.set(state.events[data.schedule], data.league, data.events)
    },
    ADD_TO_EVENTS_LIST: (state, event) => {
        state.eventsList.push(event)
    },
    REMOVE_FROM_EVENTS: (state, data) => {
        Vue.delete(state.events[data.schedule], data.removedLeague)
        if(data.schedule != 'watchlist') {
            state.eventsList = state.eventsList.filter(event => event.league_name != data.removedLeague)
        }
    },
    REMOVE_EVENT: (state, data) => {
        state.events[data.schedule][data.removedLeague] = state.events[data.schedule][data.removedLeague].filter(event => event.uid != data.removedEvent)
    },
    REMOVE_FROM_EVENT_LIST: (state, data) => {
        state.eventsList = state.eventsList.filter(event => event[data.type] != data.data)
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
    toggleLeague(context, data) {
        axios.post('v1/trade/leagues/toggle', { league_name: data.league_name, sport_id: data.sport_id, schedule: data.schedule}, { headers: { 'Authorization': `Bearer ${token}` } })
        .catch(err => {
            this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
        })
    }
}

export default {
    state, mutations, actions, namespaced: true
}
