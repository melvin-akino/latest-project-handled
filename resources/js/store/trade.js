const state = {
    selectedSport: null,
    leagues: [],
    selectedLeague: null,
    games: [],
    leaguesData: {},
    isBetBarOpen: false
}

const mutations = {
    FETCH_LEAGUES_DATA:(state, data) => {
        state.leaguesData = data
    },
    FETCH_SPORTS: (state, sports) => {
        state.sports = sports
    },
    SET_SELECTED_SPORT: (state, selectedSport) => {
        state.selectedSport = selectedSport
    },
    FETCH_LEAGUES: (state, leagues) => {
        state.leagues = leagues
    },
    SET_SELECTED_LEAGUE: (state, selectedLeague) => {
        state.selectedLeague = selectedLeague
    },
    FETCH_GAMES: (state, games) => {
        state.games.push(games)
    },
    TOGGLE_BETBAR: (state, status) => {
        state.isBetBarOpen = status
    }
}

export default {
    state, mutations, namespaced: true
}
