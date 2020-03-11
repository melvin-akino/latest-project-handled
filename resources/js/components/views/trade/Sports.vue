<template>
    <div class="text-white mb-2 pl-4 shadow-xl">
        <div class="flex flex-col overflow-hidden">
            <div class="sports overflow-y-auto flex flex-col bg-white text-gray-700">
                <div class="sport" v-for="(sport, index) in sportsList" :key="sport.id">
                    <div class="flex text-left text-sm py-1 px-6"  :class="[selectedSport == sport.id ? 'bg-gray-900 text-white' : '']" >
                        <button type="button" class="flex justify-between items-center w-full focus:outline-none" @click="selectSport(sport.id)">
                            <div class="sportBtn">
                                <i class="material-icons sportsIcon pr-2">{{sport.icon}}</i>
                                <span>{{sport.sport}}</span>
                            </div>
                            <div class="sportsListToggle" v-if="index===0">
                                <span v-show="isSportsListOpen"><i class="fas fa-chevron-down"></i></span>
                                <span v-show="!isSportsListOpen"><i class="fas fa-chevron-up"></i></span>
                            </div>
                        </button>
                    </div>
                    <div class="leagues" v-if="!isSportsListOpen">
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
import { getSocketKey, getSocketValue } from '../../../helpers/socket.js'
import _ from 'lodash'

export default {
    data() {
        return {
            sports: [],
            leagues: {},
            isSportsListOpen: false,
            isSelectingSport: false
        }
    },
    components: {
        Leagues
    },
    computed: {
        ...mapState('trade', ['selectedSport', 'selectedLeagues']),
        sportsList() {
            if(this.isSportsListOpen) {
                let sports = this.sports.filter(sport => sport.id != this.selectedSport)
                let selectedSport = this.sports.filter(sport => sport.id == this.selectedSport)[0]
                sports.unshift(selectedSport)
                return sports
            } else {
                return this.sports.filter(sport => sport.id == this.selectedSport)
            }
        }
    },
    mounted() {
        this.getSports()
    },
    methods: {
        selectSport(sport) {
            this.isSportsListOpen = !this.isSportsListOpen
            this.isSelectingSport = !this.isSelectingSport
            if(!this.isSelectingSport) {
                this.$store.commit('trade/SET_EVENTS', { schedule: 'inplay', events: [] })
                this.$store.commit('trade/SET_EVENTS', { schedule: 'today', events: [] })
                this.$store.commit('trade/SET_EVENTS', { schedule: 'early', events: [] })
                this.$store.commit('trade/CLEAR_EVENTS_LIST')
                this.$store.commit('trade/SET_SELECTED_SPORT', sport)
                this.$store.dispatch('trade/getBetColumns', this.selectedSport)
                this.$socket.send(`getSelectedSport_${sport}`)
            }
        },
        getSports() {
            let token = Cookies.get('mltoken')

            axios.get('v1/sports', { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                this.sports = response.data.data
                this.$store.commit('trade/SET_SELECTED_SPORT', response.data.default_sport)
                this.$store.dispatch('trade/getBetColumns', response.data.default_sport)
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        }
    }
}
</script>

<style>
    .sports {
        max-height:440px;
        overflow-y:auto;
    }

    .sportsIcon {
        font-size: 18px !important;
    }
</style>
