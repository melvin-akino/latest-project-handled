<template>
    <div class="flex justify-between items-center shadow-inner mb-2 ml-4">
        <div class="sport relative" v-for="sport in sports" :key="sport.id">
            <button type="button" class="appearance-none text-xl px-3 py-1 rounded-lg focus:outline-none" :class="[selectedSport === sport.id ? 'bg-orange-500 text-white' : 'text-gray-500']" @click="selectSport(sport.id)" :disabled="!sport.isEnabled"><i :class="sport.icon"></i></button>
            <div class="absolute text-white text-xs p-1 bg-gray-900 hidden sporttooltip z-10" :class="[sport.isEnabled ? 'availablesport' : 'unavailablesport']">{{sport.sport}} <span v-if="!sport.isEnabled">is not yet available.</span></div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
    data() {
        return {
            sports: [
                { "id": 1, "sport": "Soccer", icon: "fas fa-futbol", isEnabled: true },
                { "id": 2, "sport": "Basketball", icon: "fas fa-basketball-ball", isEnabled: false},
                { "id": 3, "sport": "Football", icon: "fas fa-football-ball", isEnabled: false },
                { "id": 4, "sport": "Baseball", icon: "fas fa-baseball-ball", isEnabled: false },
                { "id": 5, "sport": "E-Sports", icon: "fab fa-steam", isEnabled: false },
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
    .sporttooltip {
        width: 70px;
        text-align: center;
        left: 24px;
        top: 39px;
    }

    .availablesport {
        width: 70px;
    }

    .unavailablesport {
        width: 157px;
    }

    .sport:hover .sporttooltip {
        display: block;
    }

    .comingsoonbadge {
        font-size: 20px;
        width: 64px;
        transform: rotate(-45deg);
        bottom: 15px;
        left: 24px;
    }
</style>
