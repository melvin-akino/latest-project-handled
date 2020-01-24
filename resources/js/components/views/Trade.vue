<template>
    <div class="trade flex">
        <div class="flex flex-col items-center bg-white h-screen shadow-inner sportstab py-10">
            <!-- This section should also be child components -->
            <a href="#" class="mb-4 text-2xl px-4 py-2" :class="[selectedSport === 1 ? 'bg-orange-500 text-white' : 'text-gray-500']" @click="selectSport(1)"><i class="fas fa-futbol"></i></a>
            <a href="#" class="mb-4 text-2xl px-4 py-2" :class="[selectedSport === 2 ? 'bg-orange-500 text-white' : 'text-gray-500']" @click="selectSport(2)"><i class="fas fa-basketball-ball"></i></a>
            <a href="#" class="mb-4 text-2xl px-4 py-2" :class="[selectedSport === 3 ? 'bg-orange-500 text-white' : 'text-gray-500']" @click="selectSport(3)"><i class="fas fa-baseball-ball"></i></a>
            <a href="#" class="mb-4 text-2xl px-4 py-2" :class="[selectedSport === 4 ? 'bg-orange-500 text-white' : 'text-gray-500']" @click="selectSport(4)"><i class="fas fa-bowling-ball"></i></a>
            <a href="#" class="mb-4 text-2xl px-4 py-2" :class="[selectedSport === 5 ? 'bg-orange-500 text-white' : 'text-gray-500']" @click="selectSport(5)"><i class="fas fa-volleyball-ball"></i></a>
            <a href="#" class="mb-4 text-2xl px-4 py-2" :class="[selectedSport === 6 ? 'bg-orange-500 text-white' : 'text-gray-500']" @click="selectSport(6)"><i class="fas fa-football-ball"></i></a>
        </div>

        <div class="flex flex-col w-3/12 h-full p-8 xl:w-2/12">
            <div class="flex justify-around">
                <!-- This section should also be child components -->
                <a href="#" class="text-sm uppercase font-bold py-2 px-4" :class="[selectedLeagueSchedMode === index ? 'bg-orange-500 text-white rounded-lg shadow-lg' : 'text-gray-700']" @click="selectLeagueSchedMode(index)" v-for="(leagueSchedMode, index) in leagueSchedModes" :key="index">{{leagueSchedMode}}</a>
            </div>

            <div class="flex flex-col pt-2">
                <!-- Static at first, the actual league datas should be child components -->
                <a href="#" class="text-gray-700 text-sm capitalize font-bold py-3 px-4 w-full" :class="{'bg-white rounded-lg shadow-lg': selectedLeague === index}" @click="selectLeague(index)" v-for="(league, index) in leagues" :key="index">{{league}}</a>
            </div>
        </div>

        <div class="w-full h-full">
            <!-- League and games data here -->
        </div>
    </div>
</template>

<script>
export default {
    data() {
        // Mock Data these or some of these should come from the database through API response
        return {
            leagueSchedModes: ['Live', 'Today', 'Early'],
            leagues: ['Austria 2 Liga', 'Belgium First Division A', 'Belgium First Division B', 'Denmark Super League', 'Indian Super League', 'Major Soccer League'],
            selectedSport: 1,
            selectedLeagueSchedMode: 1,
            selectedLeague: null
        }
    },
    head: {
        title() {
            return {
                inner: 'Trade'
            }
        }
    },
    methods: {
        selectSport(sport) {
            this.selectedSport = sport
        },
        selectLeagueSchedMode(schedMode) {
            this.selectedLeagueSchedMode = schedMode
        },
        selectLeague(league) {
            this.selectedLeague = league
        }
    }
}
</script>

<style lang="scss">
    .sportstab {
        width: 65px;
    }
</style>
