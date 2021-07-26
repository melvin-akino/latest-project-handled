<template>
    <div class="betbar flex flex-col w-full bg-gray-800 left-0 bottom-0 fixed shadow-inner z-30">
        <slot></slot>
        <div class="text-center text-white h-10 pt-2 cursor-pointer bg-orange-500" @click="toggleBetBar()">
            Recent Orders
            <span v-show="isBetBarOpen"><i class="fas fa-chevron-down"></i></span>
            <span v-show="!isBetBarOpen"><i class="fas fa-chevron-up"></i></span>
        </div>
        <div class="bets overflow-y-auto" :class="[isBetBarOpen ? 'showBets' : 'hideBets']">
            <div v-if="bets.length == 0">
                <div class="text-base text-white text-center">No recent orders.</div>
            </div>
            <div v-else>
                <betbar-data v-for="bet in bets" :key="bet.order_id" :bet="bet"></betbar-data>
            </div>
        </div>
    </div>
</template>

<script>
import BetbarData from './BetbarData'
import { mapState } from 'vuex'
import _ from 'lodash'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'

export default {
    components: {
        BetbarData
    },
    computed: {
        ...mapState('trade', ['isBetBarOpen', 'bets', 'failedBetStatus'])
    },
    mounted() {
        this.modifyBetBarFromSocket()
    },
    methods: {
        toggleBetBar() {
            this.$store.commit('trade/TOGGLE_BETBAR', !this.isBetBarOpen)
        },
        modifyBetBarFromSocket() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getOrderStatus') {
                    let orderStatus = getSocketValue(response.data, 'getOrderStatus')
                    this.$store.commit('trade/SET_RECEIVED_ORDER_STATUS_ID', orderStatus.order_id)
                    this.bets.map(bet => {
                        if(bet.order_id == orderStatus.order_id) {
                            this.$set(bet, 'status', orderStatus.status)
                            this.$set(bet, 'odds', orderStatus.odds)
                            this.$set(bet, 'retry_type', orderStatus.retry_type)
                            this.$set(bet, 'odds_have_changed', orderStatus.odds_have_changed)
                            this.$set(bet, 'error', orderStatus.error)
                            if(!this.failedBetStatus.includes(orderStatus.status)) {
                                this.$store.commit('trade/SHOW_BET_MATRIX_IN_BETSLIP', { market_id: bet.market_id, has_bet: true })
                            }
                            this.$store.dispatch('trade/getWalletData')
                        }
                    })
                } else if(getSocketKey(response.data) === 'forBetBarRemoval') {
                    this.$store.dispatch('trade/getBetbarData')
                }
            })
        }
    }
}
</script>

<style>
    .bets {
        transition: all 0.3s;
    }

    .hideBets {
        height: 0;
    }

    .showBets {
        height: 153px !important;
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
