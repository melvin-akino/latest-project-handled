const state = {
    selectedSport: null,
    selectedLeague: null,
    leaguesData: {},
    isBetBarOpen: false,
    tradeLayout: null
}

const mutations = {
    FETCH_LEAGUES_DATA:(state, data) => {
        state.leaguesData = data
    },
    SET_SELECTED_SPORT: (state, selectedSport) => {
        state.selectedSport = selectedSport
    },
    SET_SELECTED_LEAGUE: (state, selectedLeague) => {
        state.selectedLeague = selectedLeague
    },
    TOGGLE_BETBAR: (state, status) => {
        state.isBetBarOpen = status
    },
    SET_TRADE_LAYOUT: (state, layout) => {
        state.tradeLayout = layout
    }
}

export default {
    state, mutations, namespaced: true
}
