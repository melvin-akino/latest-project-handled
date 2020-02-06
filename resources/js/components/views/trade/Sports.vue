<template>
    <div class="flex flex-col items-center bg-gray-900 h-screen shadow-inner py-6 fixed sportstab">
        <div class="sport mb-4" v-for="sport in sports" :key="sport.id">
            <a href="#" class="text-2xl px-3 py-1 rounded-lg" :class="[selectedSport === sport.id ? 'bg-orange-500 text-white' : 'text-gray-500']" @click="selectSport(sport.id)"><i class="fas" :class="sport.icon"></i></a>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
    data() {
        return {
            sports: [
                { "id": 1, "sport": "Soccer", icon: "fa-futbol" },
                { "id": 2, "sport": "Basketball", icon: " fa-basketball-ball" },
                { "id": 3, "sport": "Football", icon: "fa-football-ball" },
                { "id": 4, "sport": "Baseball", icon: "fa-baseball-ball" },
                { "id": 5, "sport": "E-Sports", icon: "fa-gamepad" },
            ]
        }
    },
    computed: {
        ...mapState('trade', ['selectedSport'])
    },
    mounted() {
        this.$store.commit('trade/SET_SELECTED_SPORT', 1)
    },
    methods: {
        selectSport(sport) {
            this.$store.commit('trade/SET_SELECTED_SPORT', sport)
        }
    }
}
</script>

<style>
    .sportstab {
        width: 65px;
    }
</style>
