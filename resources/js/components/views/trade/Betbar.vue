<template>
    <div class="betbar flex flex-col w-full bg-gray-800 left-0 bottom-0 fixed shadow-inner" :class="{'openBetBar': isBetBarOpen}">
        <div class="text-center text-white h-10 pt-2 cursor-pointer bg-orange-500" @click="toggleBetBar()">
            Recent Orders
            <span v-show="isBetBarOpen"><i class="fas fa-chevron-down"></i></span>
            <span v-show="!isBetBarOpen"><i class="fas fa-chevron-up"></i></span>
        </div>
        <div class="overflow-y-auto">
            <div class="flex border-b text-white text-sm" v-for="bet in bets" :key="bet.order_id" v-show="isBetBarOpen">
                <div class="w-3/12 py-1 text-center">{{bet.league_name}}</div>
                <div class="w-3/12 py-1 text-center">{{bet.home}} vs {{bet.away}}</div>
                <div class="w-3/12 py-1 text-center">{{bet.create_at}}</div>
                <div class="w-3/12 py-1 text-center">
                    <span v-if="bet.bet_info[0]==='HOME'">{{bet.home}}</span>
                    <span v-if="bet.bet_info[0]==='AWAY'">{{bet.away}}</span>
                    <span v-if="bet.bet_info[0]==='DRAW'">Draw</span>
                </div>
                <div class="w-4/12 py-1 text-center">{{defaultPriceFormat}} {{bet.bet_info[1]}} {{bet.bet_info[2]}}</div>
                <div class="w-4/12 py-1 text-center" :class="{'success': bet.status==='SUCCESS', 'failed': bet.status==='FAILED', 'processing': bet.status==='PENDING'}">
                    {{bet.provider_alias}} - {{Number(bet.bet_info[3]).toFixed(2)}}@{{bet.bet_info[2]}} - {{bet.status}}
                </div>
                <div class="flex justify-center items-center w-1/12">
                    <a href="#" class="text-center py-1 pr-3"><i class="fas fa-chart-area"></i></a>
                    <a href="#" class="text-center py-1"><i class="fas fa-bars"></i></a>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'

export default {
    computed: {
        ...mapState('trade', ['isBetBarOpen', 'bets']),
        ...mapState('settings', ['defaultPriceFormat'])
    },
    mounted() {
        this.getPriceFormat()
        this.$store.dispatch('trade/getBetbarData')
        this.getOrderStatus()
    },
    watch: {
        bets() {
            this.getOrders()
        }
    },
    methods: {
        toggleBetBar() {
            this.$store.commit('trade/TOGGLE_BETBAR', !this.isBetBarOpen)
        },
        getPriceFormat() {
            if(!this.$store.state.settings.defaultPriceFormat) {
                this.$store.dispatch('settings/getDefaultPriceFormat')
                .then(response => {
                    this.$store.commit('settings/SET_DEFAULT_PRICE_FORMAT', response)
                })
            }
        },
        getOrders() {
            this.bets.map(bet => {
                if(this.$socket.readyState == 1) {
                    this.$socket.send(`getOrder_${bet.order_id}`)
                }
            })
        },
        getOrderStatus() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getOrderStatus') {
                    let orderStatus = getSocketValue(response.data, 'getOrderStatus')
                    this.bets.map(bet => {
                        if(bet.order_id == orderStatus.order_id) {
                            this.$set(bet, 'status', orderStatus.status)
                        }
                    })
                }
            })
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
