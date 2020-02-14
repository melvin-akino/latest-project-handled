<template>
    <div class="gameSchedType">
        <div class="text-white text-center bg-orange-400 cursor-pointer capitalize" :class="{'gameSchedPanel' : !isGameSchedTypeOpen}" @click="isGameSchedTypeOpen = !isGameSchedTypeOpen">
            {{gameSchedType}}
            <span v-show="isGameSchedTypeOpen"><i class="fas fa-chevron-down"></i></span>
            <span v-show="!isGameSchedTypeOpen"><i class="fas fa-chevron-right"></i></span>
        </div>
        <div class="games" :class="{'hidden': !isGameSchedTypeOpen}">
            <p class="text-sm text-gray-500 text-center pt-2 noeventspanel" v-if="!games">No competitions watched in this market.</p>
            <div v-else>
                <div class="bg-white text-white text-sm text-gray-700" v-for="(league, index) in games" :key="index">
                    <div class="flex justify-between py-1 pl-4 pr-4 font-bold border-t border-orange-500 leaguePanel">
                        <div>
                            <button type="button" class="mt-1 pr-1 text-red-600 focus:outline-none"><i class="fas fa-times-circle"></i></button>
                            <button type="button" class="mt-1 pr-1 text-orange-500 focus:outline-none" @click="toggleLeague(index)">
                                <span v-show="closedLeagues.includes(index)"><i class="fas fa-chevron-down"></i></span>
                                <span v-show="!closedLeagues.includes(index)"><i class="fas fa-chevron-up"></i></span>
                            </button>
                            {{index}}
                        </div>
                        <div class="text-white"><i class="fas fa-star"></i></div>
                    </div>
                    <div class="gamesWrapper" :class="!closedLeagues.includes(index) ? 'h-full' : 'h-0 overflow-hidden'">
                        <div class="flex justify-around py-4 px-4 game" :class="[index % 2 != 0 ? 'alternateEvent' : '']" v-for="(game, index) in league" :key="game.uid">
                            <div class="flex flex-col w-2/12">
                                <div><span class="font-bold text-green-400 mr-2">H</span>{{game.home_team_name}}</div>
                                <div><span class="font-bold text-red-600 mr-2">A</span>{{game.away_team_name}}</div>
                                <div><span class="mr-3">&nbsp;</span>Draw</div>
                            </div>
                            <div class="flex justify-center w-1/12">
                                <span>Soccer</span>
                            </div>
                            <div class="flex flex-col items-center w-1/12">
                                <span>2 - 0</span>
                                <span>2 H : 58 M</span>
                            </div>
                            <div class="w-1/12"></div>
                            <div class="flex flex-col items-center w-1/12 ft_1x2" :class="{'hidden': disabledBetColumns.includes(1)}">
                                <p class="px-3 rounded-lg">{{game.ft_1x2.home}}</p>
                                <p class="px-3 rounded-lg">{{game.ft_1x2.away}}</p>
                                <p class="px-3 rounded-lg">{{game.ft_1x2.draw}}</p>
                            </div>
                            <div class="flex flex-col items-center w-1/12 ft_hdp" :class="{'hidden': disabledBetColumns.includes(2)}">
                                <p class="relative px-3 rounded-lg">
                                    <span class="absolute text-gray-500 odds-label left-label">- 2.5</span>
                                    <span class="bet-click">{{game.ft_hdp.home}}</span>
                                </p>
                                <p class="relative px-3 rounded-lg">
                                     <span class="absolute text-gray-500 odds-label left-label">2.5</span>
                                    <span class="bet-click">{{game.ft_hdp.away}}</span>
                                </p>
                            </div>
                            <div class="flex flex-col items-center w-1/12 ft_ou" :class="{'hidden': disabledBetColumns.includes(3)}">
                                <p class="relative px-3 rounded-lg">
                                     <span class="absolute text-gray-500 odds-label left-label">O</span>
                                     <span class="bet-click">{{game.ft_ou.home}}</span>
                                     <span class="absolute text-gray-500 odds-label right-label">2.5</span>
                                </p>
                                <p class="relative px-3 rounded-lg">
                                     <span class="absolute text-gray-500 odds-label left-label">U</span>
                                     <span class="bet-click">{{game.ft_ou.away}}</span>
                                     <span class="absolute text-gray-500 odds-label right-label">2.5</span>
                                </p>
                            </div>
                            <div class="flex flex-col items-center w-1/12 ft_oe" :class="{'hidden': disabledBetColumns.includes(4)}">
                                <p class="relative px-3 rounded-lg">
                                    <span class="absolute text-gray-500 odds-label left-label">O</span>
                                    <span class="bet-click">{{game.ft_1x2.home}}</span>
                                </p>
                                <p class="relative px-3 rounded-lg">
                                    <span class="absolute text-gray-500 odds-label left-label">E</span>
                                    <span class="bet-click">{{game.ft_1x2.away}}</span>
                                </p>
                            </div>
                            <div class="flex flex-col items-center w-1/12 1h_1x2" :class="{'hidden': disabledBetColumns.includes(5)}">
                                <p class="px-3 rounded-lg">{{game.ft_1x2.home}}</p>
                                <p class="px-3 rounded-lg">{{game.ft_1x2.away}}</p>
                                <p class="px-3 rounded-lg">{{game.ft_1x2.draw}}</p>
                            </div>
                            <div class="flex flex-col items-center w-1/12 1h_hdp" :class="{'hidden': disabledBetColumns.includes(6)}">
                                <p class="relative px-3 rounded-lg">
                                    <span class="absolute text-gray-500 odds-label left-label">- 2.5</span>
                                    <span class="bet-click">{{game.ft_hdp.home}}</span>
                                </p>
                                <p class="relative px-3 rounded-lg">
                                     <span class="absolute text-gray-500 odds-label left-label">2.5</span>
                                    <span class="bet-click">{{game.ft_hdp.away}}</span>
                                </p>
                            </div>
                            <div class="flex flex-col items-center w-1/12 1h_ou" :class="{'hidden': disabledBetColumns.includes(7)}">
                                <p class="relative px-3 rounded-lg">
                                     <span class="absolute text-gray-500 odds-label left-label">O</span>
                                     <span class="bet-click">{{game.ft_ou.home}}</span>
                                     <span class="absolute text-gray-500 odds-label right-label">2.5</span>
                                </p>
                                <p class="relative px-3 rounded-lg">
                                     <span class="absolute text-gray-500 odds-label left-label">U</span>
                                     <span class="bet-click">{{game.ft_ou.away}}</span>
                                     <span class="absolute text-gray-500 odds-label right-label">2.5</span>
                                </p>
                            </div>
                            <div class="text-white">
                                <i class="fas fa-star"></i>
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

export default {
    props: ['gameSchedType', 'games'],
    data() {
        return {
            isGameSchedTypeOpen: true,
            closedLeagues: []
        }
    },
    computed: {
        ...mapState('trade', ['selectedSport']),
        ...mapState('settings', ['disabledBetColumns'])
    },
    methods: {
        toggleLeague(index) {
            if(this.closedLeagues.includes(index)) {
                this.closedLeagues = this.closedLeagues.filter(league => index != league)
            } else {
                this.closedLeagues.push(index)
            }
        }
    }
}
</script>

<style>
    .gameSchedPanel {
        border-bottom: solid #ffe8cc 1px;
    }

    .noeventspanel {
        height:34px;
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
        color: #fff200
    }

    .game:not(:last-child) {
        border-bottom: solid #edf2f7 1px;
    }

    .bet-click:hover {
        color: #ffffff;
        background-color: #ed8936;
        cursor: pointer;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .odds-label {
        width: 50px;
    }
        .left-label {
            left: -55px;
            text-align: right;
        }

        .right-label {
            right: -55px;
            text-align: left;
        }

    .bet-click.ping-danger {
        animation-name: ping-danger;
        animation-duration: 5s;
        animation-iteration-count: 1;
    }

    .bet-click.ping-success {
        animation-name: ping-success;
        animation-duration: 3s;
        animation-iteration-count: 1;
    }

    @keyframes ping-danger{
        from{
            box-shadow: 0px 0px 0px 2px rgba(227, 52, 47, 1);
            color: rgba(227, 52, 47, 1);
            font-weight: 700;
        } to{
            box-shadow: none;
            color: rgba(50, 50, 50, 1);
            font-weight: normal;
        }
    }

    @keyframes ping-success{
        from{
            box-shadow: 0px 0px 0px 2px rgba(56, 193, 114, 1);
            color: rgba(56, 193, 114, 1);
            font-weight: 700;
        } to{
            box-shadow: none;
            color: rgba(50, 50, 50, 1);
            font-weight: normal;
        }
    }
</style>
