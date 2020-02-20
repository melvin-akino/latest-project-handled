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
                        <div class="asianLayout"  v-if="tradeLayout==='1'">
                            <div class="flex justify-around py-4 px-4 game" :class="[index % 2 != 0 ? 'alternateEvent' : '']" v-for="(game, index) in league" :key="game.uid">
                                <div class="w-2/12 flex flex-col">
                                    <div><span class="font-bold text-green-400 mr-2">H</span>{{game.home_team_name}}</div>
                                    <div><span class="font-bold text-red-600 mr-2">A</span>{{game.away_team_name}}</div>
                                    <div><span class="mr-3">&nbsp;</span>Draw</div>
                                </div>
                                <div class="w-1/12 flex justify-center">
                                    <span>Soccer</span>
                                </div>
                                <div class="w-1/12 flex flex-col items-center">
                                    <span>2 - 0</span>
                                    <span>2 H : 58 M</span>
                                </div>
                                <div class="w-1/12"></div>
                                <div class="w-1/12 flex flex-col items-center ft_1x2" :class="{'hidden': disabledBetColumns.includes(1)}">
                                    <p class="px-2 rounded-lg bet-click">{{game.ft_1x2.home.toFixed(2)}}</p>
                                    <p class="px-2 rounded-lg bet-click">{{game.ft_1x2.away.toFixed(2)}}</p>
                                    <p class="px-2 rounded-lg bet-click">{{game.ft_1x2.draw.toFixed(2)}}</p>
                                </div>
                                <div class="w-1/12 flex flex-col items-center ft_hdp" :class="{'hidden': disabledBetColumns.includes(2)}">
                                    <p class="relative">
                                        <span class="absolute text-gray-500 odds-label left-label">- 2.5</span>
                                        <span class="bet-click px-2 rounded-lg">{{game.ft_hdp.home.toFixed(2)}}</span>
                                    </p>
                                    <p class="relative">
                                        <span class="absolute text-gray-500 odds-label left-label">+ 2.5</span>
                                        <span class="bet-click px-2 rounded-lg">{{game.ft_hdp.away.toFixed(2)}}</span>
                                    </p>
                                </div>
                                <div class="w-1/12 flex flex-col items-center ft_ou" :class="{'hidden': disabledBetColumns.includes(3)}">
                                    <p class="relative">
                                        <span class="absolute text-gray-500 odds-label left-label">O</span>
                                        <span class="bet-click px-2 rounded-lg">{{game.ft_ou.home.toFixed(2)}}</span>
                                        <span class="absolute text-gray-500 odds-label right-label">2.5</span>
                                    </p>
                                    <p class="relative">
                                        <span class="absolute text-gray-500 odds-label left-label">U</span>
                                        <span class="bet-click px-2 rounded-lg">{{game.ft_ou.away.toFixed(2)}}</span>
                                        <span class="absolute text-gray-500 odds-label right-label">2.5</span>
                                    </p>
                                </div>
                                <div class="w-1/12 flex flex-col items-center ft_oe" :class="{'hidden': disabledBetColumns.includes(4)}">
                                    <p class="relative">
                                        <span class="absolute text-gray-500 odds-label left-label">O</span>
                                        <span class="bet-click px-2 rounded-lg">{{game.ft_1x2.home.toFixed(2)}}</span>
                                    </p>
                                    <p class="relative">
                                        <span class="absolute text-gray-500 odds-label left-label">E</span>
                                        <span class="bet-click px-2 rounded-lg">{{game.ft_1x2.away.toFixed(2)}}</span>
                                    </p>
                                </div>
                                <div class="w-1/12 flex flex-col items-center ht_1x2" :class="{'hidden': disabledBetColumns.includes(5)}">
                                    <p class="px-2 rounded-lg bet-click">{{game.ft_1x2.home.toFixed(2)}}</p>
                                    <p class="px-2 rounded-lg bet-click">{{game.ft_1x2.away.toFixed(2)}}</p>
                                    <p class="px-2 rounded-lg bet-click">{{game.ft_1x2.draw.toFixed(2)}}</p>
                                </div>
                                <div class="w-1/12 flex flex-col items-center ht_hdp" :class="{'hidden': disabledBetColumns.includes(6)}">
                                    <p class="relative">
                                        <span class="absolute text-gray-500 odds-label left-label">- 2.5</span>
                                        <span class="bet-click px-2 rounded-lg">{{game.ft_hdp.home.toFixed(2)}}</span>
                                    </p>
                                    <p class="relative">
                                        <span class="absolute text-gray-500 odds-label left-label">+ 2.5</span>
                                        <span class="bet-click px-2 rounded-lg">{{game.ft_hdp.away.toFixed(2)}}</span>
                                    </p>
                                </div>
                                <div class="w-1/12 flex flex-col items-center ht_ou" :class="{'hidden': disabledBetColumns.includes(7)}">
                                    <p class="relative">
                                        <span class="absolute text-gray-500 odds-label left-label">O</span>
                                        <span class="bet-click px-2 rounded-lg">{{game.ft_ou.home.toFixed(2)}}</span>
                                        <span class="absolute text-gray-500 odds-label right-label">2.5</span>
                                    </p>
                                    <p class="relative">
                                        <span class="absolute text-gray-500 odds-label left-label">U</span>
                                        <span class="bet-click px-2 rounded-lg">{{game.ft_ou.away.toFixed(2)}}</span>
                                        <span class="absolute text-gray-500 odds-label right-label">2.5</span>
                                    </p>
                                </div>
                                <div class="text-white">
                                    <span class="eventStar"><i class="fas fa-star"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="europeanLayout" v-if="tradeLayout==='2'">
                            <div class="flex flex-col justify-around pl-4 pr-8 py-4 game" :class="[index % 2 != 0 ? 'alternateEvent' : '']" v-for="(game, index) in league" :key="game.uid">
                                <div class="relative flex justify-center pb-4">
                                    <span class="gameColumn teamColumn">{{game.home_team_name}}</span>
                                    <span class="gameColumn font-bold text-green-400 text-center">H</span>
                                    <span class="gameColumn text-lg text-center">2</span>
                                    <span class="gameColumn text-center">2 H : 58 M</span>
                                    <span class="gameColumn text-lg text-center">0</span>
                                    <span class="gameColumn font-bold text-red-600 text-center">A</span>
                                    <span class="gameColumn teamColumn">{{game.away_team_name}}</span>
                                    <div class="absolute text-white european-game-star">
                                        <span class="eventStar"><i class="fas fa-star"></i></span>
                                    </div>
                                </div>
                                <div class="flex justify-around">
                                    <div class="w-1/12"></div>
                                    <div class="w-1/12 flex justify-between ft_1x2">
                                        <span class="px-1 rounded-lg bet-click">{{game.ft_1x2.home.toFixed(2)}}</span>
                                        <span class="px-1 rounded-lg bet-click">{{game.ft_1x2.draw.toFixed(2)}}</span>
                                        <span class="px-1 rounded-lg bet-click">{{game.ft_1x2.away.toFixed(2)}}</span>
                                    </div>
                                    <div class="w-1/12 flex justify-between ft_hdp">
                                        <p class="relative">
                                            <span class="absolute text-gray-500 text-center odds-label european-left-label">- 2.5</span>
                                            <span class="bet-click px-1 rounded-lg">{{game.ft_hdp.home.toFixed(2)}}</span>
                                        </p>
                                        <p class="relative">
                                            <span class="absolute text-gray-500 text-center odds-label european-left-label">+ 2.5</span>
                                            <span class="bet-click px-1 rounded-lg">{{game.ft_hdp.away.toFixed(2)}}</span>
                                        </p>
                                    </div>
                                    <div class="w-1/12 flex justify-between ft_ou">
                                        <p class="relative">
                                            <span class="absolute text-gray-500 text-center odds-label european-left-label">2.5</span>
                                            <span class="bet-click px-1 rounded-lg">{{game.ft_ou.home.toFixed(2)}}</span>
                                        </p>
                                        <p class="relative">
                                            <span class="absolute text-gray-500 text-center odds-label european-left-label">2.5</span>
                                            <span class="bet-click px-1 rounded-lg">{{game.ft_ou.away.toFixed(2)}}</span>
                                        </p>
                                    </div>
                                    <div class="w-1/12 flex justify-between ft_oe">
                                        <span class="bet-click px-1 rounded-lg">{{game.ft_1x2.home.toFixed(2)}}</span>
                                        <span class="bet-click px-1 rounded-lg">{{game.ft_1x2.away.toFixed(2)}}</span>
                                    </div>
                                    <div class="w-1/12 flex justify-between ht_1x2">
                                        <span class="px-1 rounded-lg bet-click">{{game.ft_1x2.home.toFixed(2)}}</span>
                                        <span class="px-1 rounded-lg bet-click">{{game.ft_1x2.draw.toFixed(2)}}</span>
                                        <span class="px-1 rounded-lg bet-click">{{game.ft_1x2.away.toFixed(2)}}</span>
                                    </div>
                                    <div class="w-1/12 flex justify-between ht_hdp">
                                        <p class="relative">
                                            <span class="absolute text-gray-500 text-center odds-label european-left-label">- 2.5</span>
                                            <span class="bet-click px-1 rounded-lg">{{game.ft_hdp.home.toFixed(2)}}</span>
                                        </p>
                                        <p class="relative">
                                            <span class="absolute text-gray-500 text-center odds-label european-left-label">+ 2.5</span>
                                            <span class="bet-click px-1 rounded-lg">{{game.ft_hdp.away.toFixed(2)}}</span>
                                        </p>
                                    </div>
                                    <div class="w-1/12 flex justify-between ht_ou">
                                        <p class="relative">
                                            <span class="absolute text-gray-500 text-center odds-label european-left-label">2.5</span>
                                            <span class="bet-click px-1 rounded-lg">{{game.ft_hdp.home.toFixed(2)}}</span>
                                        </p>
                                        <p class="relative">
                                            <span class="absolute text-gray-500 text-center odds-label european-left-label">2.5</span>
                                            <span class="bet-click px-1 rounded-lg">{{game.ft_hdp.away.toFixed(2)}}</span>
                                        </p>
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

export default {
    props: ['gameSchedType', 'games'],
    data() {
        return {
            isGameSchedTypeOpen: true,
            closedLeagues: []
        }
    },
    computed: {
        ...mapState('trade', ['selectedSport', 'tradeLayout']),
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
        color: #fff200
    }

    .eventStar {
        right: 15px;
    }

    .european-game-star {
        right:-17px;
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

        .european-left-label {
            right: 35px;
            width: 30px !important;
        }

        .left-label {
            left: -52px;
            text-align: right;
        }

        .right-label {
            right: -52px;
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
