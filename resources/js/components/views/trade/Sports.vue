<template>
    <div class="flex justify-between items-center shadow-inner mb-2 ml-4">
        <div class="sport relative" v-for="sport in sports" :key="sport.id">
            <button type="button" class="appearance-none text-xl px-3 py-1 rounded-lg focus:outline-none" :class="[selectedSport === sport.id ? 'bg-orange-500 text-white' : 'text-gray-500']" @click="selectSport(sport.id)" :disabled="!sport.is_enabled"><i class="fas" :class="sport.icon"></i></button>
            <div class="absolute text-white text-xs p-1 bg-gray-900 hidden sporttooltip z-10" :class="[sport.is_enabled ? 'availablesport' : 'unavailablesport']">{{sport.sport}} <span v-if="!sport.is_enabled">is not yet available.</span></div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'

export default {
    data() {
        return {
            sports: []
        }
    },
    computed: {
        ...mapState('trade', ['selectedSport'])
    },
    mounted() {
        this.$store.commit('trade/SET_SELECTED_SPORT', 1)
        this.getSports()
    },
    methods: {
        selectSport(sport) {
            this.$store.commit('trade/SET_SELECTED_SPORT', sport)
        },
        getSports() {
            let token = Cookies.get('mltoken')

            axios.get('v1/sports', { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => this.sports = response.data.data)
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
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
