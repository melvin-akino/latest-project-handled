<template>
    <div class="betbar flex flex-col w-full bg-gray-800 left-0 bottom-0 fixed overflow-y-auto shadow-inner" :class="{'openBetBar': isBetBarOpen}">
        <div class="text-center text-white h-10 pt-2 cursor-pointer bg-orange-500" @click="toggleBetBar()">
            Recent Orders
            <span v-show="isBetBarOpen"><i class="fas fa-chevron-down"></i></span>
            <span v-show="!isBetBarOpen"><i class="fas fa-chevron-up"></i></span>
        </div>
        <div class="flex border-b text-white text-sm" v-for="(betData, index) in betDatas" :key="index" v-show="isBetBarOpen">
            <div class="w-2/12 py-1 pl-16">{{betData.league_name}}</div>
            <div class="w-4/12 py-1">{{betData.home}} vs {{betData.away}}</div>
            <div class="w-3/12 py-1">
                <span v-if="betData.bet_info[0]==='home'">{{betData.home}}</span>
                <span v-if="betData.bet_info[0]==='away'">{{betData.away}}</span>
            </div>
            <div class="w-4/12 py-1">{{defaultPriceFormat}} {{betData.bet_info[1]}} {{betData.bet_info[2]}}</div>
            <div class="w-4/12 py-1 text-center" :class="{'success': betData.status==='Success', 'failed': betData.status==='Failed', 'processing': betData.status==='Processing'}">
                {{betData.bet_info[3]}}@{{betData.bet_info[2]}} - {{betData.status}}
            </div>
            <div class="flex justify-center items-center w-1/12">
                <a href="#" class="text-center py-1 pr-3"><i class="fas fa-chart-area"></i></a>
                <a href="#" class="text-center py-1"><i class="fas fa-bars"></i></a>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'

export default {
    data() {
        return {
            betDatas: []
        }
    },
    computed: {
        ...mapState('trade', ['isBetBarOpen']),
        ...mapState('settings', ['defaultPriceFormat'])
    },
    mounted() {
        this.getBetbarData()
        this.getPriceFormat()
    },
    methods: {
        toggleBetBar() {
            this.$store.commit('trade/TOGGLE_BETBAR', !this.isBetBarOpen)
        },
        getBetbarData() {
            let token = Cookies.get('mltoken')

            axios.get('v1/trade/betbar', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.betDatas = response.data.data
                if(this.betDatas) {
                    this.$store.commit('trade/TOGGLE_BETBAR', true)
                }
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        },
        getPriceFormat() {
            if(!this.$store.state.settings.defaultPriceFormat) {
                this.$store.dispatch('settings/getDefaultPriceFormat')
                .then(response => {
                    this.$store.commit('settings/SET_DEFAULT_PRICE_FORMAT', response)
                })
            }
        }
    }
}
</script>

<style>
    .betbar {
        transition: all 0.3s;
        height: 40px;
    }

    .openBetBar {
        height: 192px !important;
    }
    .success {
        background-color: #5cb85c;
    }

    .failed {
        background-color: #d9534f;
    }

    .processing {
        background-color: #0275d8;
    }
</style>
