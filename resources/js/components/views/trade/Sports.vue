<template>
    <div class="text-white mb-2 pl-4 shadow-xl">
        <div class="flex flex-col overflow-hidden">
            <div class="text-center text-white text-sm cursor-pointer py-2 bg-orange-500">Sports</div>
            <div class="sports overflow-y-auto flex flex-col bg-white text-gray-700">
                <div class="sport" v-for="sport in sports" :key="sport.id">
                    <div class="flex items-center w-full text-left text-sm py-1 px-6"  :class="[selectedSport === sport.id ? 'bg-gray-900 text-white' : '',  { 'text-gray-600' : !sport.is_enabled }]" >
                        <i class="material-icons sportsIcon pr-2">{{sport.icon}}</i>
                        <button type="button" class="focus:outline-none"@click="selectSport(sport.id)" :disabled="!sport.is_enabled"><span class="pb-1">{{sport.sport}}</span></button>
                    </div>
                    <div class="leagues" v-if="selectedSport === sport.id">
                        <Leagues></Leagues>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import Leagues from './Leagues'

export default {
    data() {
        return {
            sports: [],
        }
    },
    components: {
        Leagues
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
    .sports {
        max-height:420px;
    }

    .sportsIcon {
        font-size: 18px !important;
    }
</style>
