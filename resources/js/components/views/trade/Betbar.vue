<template>
    <div class="betbar flex flex-col w-full bg-gray-800 left-0 bottom-0 fixed shadow-inner" :class="{'openBetBar': isBetBarOpen}">
        <div class="text-center text-white h-10 pt-2 cursor-pointer bg-orange-500" @click="toggleBetBar()">
            Recent Orders
            <span v-show="isBetBarOpen"><i class="fas fa-chevron-down"></i></span>
            <span v-show="!isBetBarOpen"><i class="fas fa-chevron-up"></i></span>
        </div>
        <div class="overflow-y-auto" v-show="isBetBarOpen">
            <betbar-data v-for="bet in bets" :key="bet.order_id" :bet="bet"></betbar-data>
        </div>
    </div>
</template>

<script>
import BetbarData from './BetbarData'
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'

export default {
    components: {
        BetbarData
    },
    computed: {
        ...mapState('trade', ['isBetBarOpen', 'bets'])
    },
    mounted() {
        this.getPriceFormat()
        this.$store.dispatch('trade/getBetbarData')
        this.getOrderStatus()
    },
    watch: {
        bets() {
            this.getOrders()
            this.getOrderStatus()
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
                            this.$set(bet, 'bet_info', [
                                bet.bet_info[0],
                                bet.bet_info[1],
                                orderStatus.odds,
                                bet.bet_info[3],
                                bet.bet_info[4],
                                bet.bet_info[5],
                            ])
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
