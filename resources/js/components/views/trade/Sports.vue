<template>
    <div class="text-white mb-2 pl-4 shadow-xl">
        <div class="flex flex-col overflow-hidden">
            <div class="sports flex flex-col bg-white text-gray-700">
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
import { getSocketKey, getSocketValue } from '../../../helpers/socket'
import { moveToFirstElement } from '../../../helpers/array'
import _ from 'lodash'

export default {
    data() {
        return {
            isSportsListOpen: false,
            isSelectingSport: false
        }
    },
    components: {
        Leagues
    },
    computed: {
        ...mapState('trade', ['sports', 'selectedSport', 'selectedLeagues']),
        sportsList() {
            if(this.isSportsListOpen) {
                return moveToFirstElement(this.sports, 'id', null, this.selectedSport)
            } else {
                return this.sports.filter(sport => sport.id == this.selectedSport)
            }
        }
    },
    methods: {
        selectSport(sport) {
            this.isSportsListOpen = !this.isSportsListOpen
            this.isSelectingSport = !this.isSelectingSport
            if(!this.isSelectingSport && this.selectedSport != sport) {
                this.$store.commit('trade/CLEAR_EVENTS_LIST')
                this.$store.commit('trade/CLEAR_SELECTED_LEAGUES')
                this.$store.commit('trade/SET_SELECTED_SPORT', sport)
                this.$store.dispatch('trade/getBetColumns', sport)
                this.$socket.send(`getSelectedLeagues_${sport}`)
                this.$socket.send(`getSelectedSport_${sport}`)
                this.$store.dispatch('trade/getInitialLeagues')
                this.$store.dispatch('trade/getInitialEvents')
            }
        }
    }
}
</script>

<style>
.sportsIcon {
    font-size: 18px !important;
}
</style>
