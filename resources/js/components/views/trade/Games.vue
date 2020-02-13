<template>
    <div class="gameSchedType" :class="[isGameSchedTypeOpen ? 'mb-4' : 'mb-1']">
        <div class="text-white text-center bg-orange-500 cursor-pointer capitalize" @click="isGameSchedTypeOpen = !isGameSchedTypeOpen">
            {{gameSchedType}}
            <span v-show="isGameSchedTypeOpen"><i class="fas fa-chevron-down"></i></span>
            <span v-show="!isGameSchedTypeOpen"><i class="fas fa-chevron-right"></i></span>
        </div>
        <div class="games" :class="{'hidden': !isGameSchedTypeOpen}">
            <p class="text-sm text-gray-500 text-center" v-if="!games">No competitions watched in this market.</p>
            <div class="mt-3" v-else>
                <div class="bg-white shadow-xl rounded-lg mb-3 py-3 px-4 text-sm text-gray-700" v-for="(league, index) in games" :key="index">
                    <p class="py-1 font-bold">{{index}}</p>
                    <div class="flex justify-around py-4 game" v-for="game in league" :key="game.uid">
                        <div class="flex flex-col w-3/12">
                            <div class="my-1 py-1"><span class="font-bold text-green-400 mr-2">H</span>{{game.home_team_name}}</div>
                            <div class="my-1 py-1"><span class="font-bold text-red-600 mr-2">A</span>{{game.away_team_name}}</div>
                            <div class="my-1 py-1"><span class="mr-3">&nbsp;</span>Draw</div>
                        </div>
                        <div class="flex justify-center w-1/12">
                            <span class="my-1 py-1">Soccer</span>
                        </div>
                        <div class="flex flex-col items-center w-1/12">
                            <span class="my-1 py-1">2 - 0</span>
                            <span class="my-1 py-1">2 H : 58 M</span>
                        </div>
                        <div class="flex flex-col items-center w-1/12 ft_1x2">
                            <p class="my-1 py-1 w-12 text-center rounded-lg border border-gray-300 bet-click">{{game.ft_1x2.home}}</p>
                            <p class="my-1 py-1 w-12 text-center rounded-lg border border-gray-300 bet-click">{{game.ft_1x2.away}}</p>
                            <p class="my-1 py-1 w-12 text-center rounded-lg border border-gray-300 bet-click">{{game.ft_1x2.draw}}</p>
                        </div>
                        <div class="flex flex-col items-center w-1/12 ft_hdp">
                            <p class="my-1 w-12 relative text-center">
                                <span class="absolute text-gray-500 odds-label left-label">- 2.5</span>
                                <span class="rounded-lg py-1 block border border-gray-300 bet-click">{{game.ft_ou.home}}</span>
                            </p>
                            <p class="my-1 w-12 relative text-center">
                                <span class="absolute text-gray-500 odds-label left-label">+ 2.5</span>
                                <span class="rounded-lg py-1 block border border-gray-300 bet-click">{{game.ft_ou.away}}</span>
                            </p>
                        </div>
                        <div class="flex flex-col items-center w-1/12 ft_ou">
                            <p class="my-1 w-12 relative text-center">
                                <span class="absolute text-gray-500 odds-label left-label">O</span>
                                <span class="rounded-lg py-1 block border border-gray-300 bet-click">{{game.ft_ou.home}}</span>
                                <span class="absolute text-gray-500 odds-label right-label">2.5</span>
                            </p>
                            <p class="my-1 w-12 relative text-center">
                                <span class="absolute text-gray-500 odds-label left-label">U</span>
                                <span class="rounded-lg py-1 block border border-gray-300 bet-click">{{game.ft_ou.away}}</span>
                                <span class="absolute text-gray-500 odds-label right-label">2.5</span>
                            </p>
                        </div>
                        <div class="flex flex-col items-center w-1/12 ft_oe">
                            <p class="my-1 w-12 relative text-center">
                                <span class="absolute text-gray-500 odds-label left-label">O</span>
                                <span class="rounded-lg py-1 block border border-gray-300 bet-click">{{game.ft_ou.home}}</span>
                            </p>
                            <p class="my-1 w-12 relative text-center">
                                <span class="absolute text-gray-500 odds-label left-label">E</span>
                                <span class="rounded-lg py-1 block border border-gray-300 bet-click">{{game.ft_ou.away}}</span>
                            </p>
                        </div>
                        <div class="flex flex-col items-center w-1/12 ht_1x2">
                            <p class="my-1 py-1 w-12 text-center rounded-lg border border-gray-300 bet-click">{{game.ft_1x2.home}}</p>
                            <p class="my-1 py-1 w-12 text-center rounded-lg border border-gray-300 bet-click">{{game.ft_1x2.away}}</p>
                            <p class="my-1 py-1 w-12 text-center rounded-lg border border-gray-300 bet-click">{{game.ft_1x2.draw}}</p>
                        </div>
                        <div class="flex flex-col items-center w-1/12 ht_hdp">
                            <p class="my-1 w-12 relative text-center">
                                <span class="absolute text-gray-500 odds-label left-label">- 2.5</span>
                                <span class="rounded-lg py-1 block border border-gray-300 bet-click">{{game.ft_ou.home}}</span>
                            </p>
                            <p class="my-1 w-12 relative text-center">
                                <span class="absolute text-gray-500 odds-label left-label">+ 2.5</span>
                                <span class="rounded-lg py-1 block border border-gray-300 bet-click">{{game.ft_ou.away}}</span>
                            </p>
                        </div>
                        <div class="flex flex-col items-center w-1/12 ht_ou">
                            <p class="my-1 w-12 relative text-center">
                                <span class="absolute text-gray-500 odds-label left-label">O</span>
                                <span class="rounded-lg py-1 block border border-gray-300 bet-click">{{game.ft_ou.home}}</span>
                                <span class="absolute text-gray-500 odds-label right-label">2.5</span>
                            </p>
                            <p class="my-1 w-12 relative text-center">
                                <span class="absolute text-gray-500 odds-label left-label">U</span>
                                <span class="rounded-lg py-1 block border border-gray-300 bet-click">{{game.ft_ou.away}}</span>
                                <span class="absolute text-gray-500 odds-label right-label">2.5</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['gameSchedType', 'games'],
    data() {
        return {
            isGameSchedTypeOpen: true
        }
    }

}
</script>

<style>
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
        top: 5px;
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

    .fa-star:hover {
        color: #fff200
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
