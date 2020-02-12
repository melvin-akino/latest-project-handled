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
                            <div class="py-1"><span class="font-bold text-green-400 mr-2">H</span>{{game.home_team_name}}</div>
                            <div class="py-1"><span class="font-bold text-red-600 mr-2">A</span>{{game.away_team_name}}</div>
                            <div class="py-1"><span class="mr-3">&nbsp;</span>Draw</div>
                        </div>
                        <div class="flex justify-center w-1/12">
                            <span class="py-1">Soccer</span>
                        </div>
                        <div class="flex flex-col items-center w-1/12">
                            <span class="py-1">2 - 0</span>
                            <span class="py-1">2 H : 58 M</span>
                        </div>
                        <div class="flex flex-col items-center w-1/12">
                            <p class="py-1 px-3 rounded-lg">{{game.ft_1x2.home}}</p>
                            <p class="py-1 px-3 rounded-lg">{{game.ft_1x2.away}}</p>
                            <p class="py-1 px-3 rounded-lg">{{game.ft_1x2.draw}}</p>
                        </div>
                        <div class="flex flex-col items-center w-1/12">
                            <p class="py-1 px-3 rounded-lg">{{game.ft_hdp.home}}</p>
                            <p class="py-1 px-3 rounded-lg">{{game.ft_hdp.away}}</p>
                        </div>
                        <div class="flex flex-col items-center w-1/12">
                            <p class="py-1 px-3 rounded-lg">{{game.ft_ou.home}}</p>
                            <p class="py-1 px-3 rounded-lg">{{game.ft_ou.away}}</p>
                        </div>
                        <div class="flex flex-col items-center w-1/12">
                            <p class="py-1 px-3 rounded-lg">{{game.ft_ou.home}}</p>
                            <p class="py-1 px-3 rounded-lg">{{game.ft_ou.away}}</p>
                        </div>
                        <div class="flex flex-col items-center w-1/12">
                            <p class="py-1 px-3 rounded-lg">{{game.ft_ou.home}}</p>
                            <p class="py-1 px-3 rounded-lg">{{game.ft_ou.away}}</p>
                        </div>
                        <div class="flex flex-col items-center w-1/12">
                            <p class="py-1 px-3 rounded-lg">{{game.ft_ou.home}}</p>
                            <p class="py-1 px-3 rounded-lg">{{game.ft_ou.away}}</p>
                        </div>
                        <div class="flex flex-col items-center w-1/12">
                            <p class="py-1 px-3 rounded-lg">{{game.ft_ou.home}}</p>
                            <p class="py-1 px-3 rounded-lg">{{game.ft_ou.away}}</p>
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

    .game p:hover {
        color: #ffffff;
        background-color: #ed8936;
        cursor: pointer;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .fa-star:hover {
        color: #fff200
    }
</style>
