<template>
    <div class="betData">
        <odds-history v-if="openedOddsHistory.includes(order.order_id)" @close="$emit('closeOddsHistory', order.order_id)" :market_id="order.market_id" :logs="orderLogs"></odds-history>
        <bet-matrix v-if="openedBetMatrix.includes(order.order_id)" @close="$emit('closeBetMatrix', order.order_id)" :market_id="order.market_id" :analysis-data="analysisData"></bet-matrix>
    </div>
</template>

<script>
import OddsHistory from '../trade/OddsHistory'
import BetMatrix from '../trade/BetMatrix'
import { mapState } from 'vuex'

export default {
    components: {
        OddsHistory,
        BetMatrix
    },
    props: ['order', 'openedOddsHistory', 'openedBetMatrix'],
    data() {
        return {
            orderLogs: [],
            showOddsHistory: false,
            showBetMatrix: false
        }
    },
    mounted() {
        this.setOrderLogs(this.order.market_id)
    },
    computed: {
        ...mapState('settings', ['defaultPriceFormat']),
        ...mapState('trade', ['wallet']),
        analysisData() {
            return {
                stake: this.order.stake,
                points: this.order.points,
                price: this.order.odds,
                bet_score: this.order.bet_score,
                against_score: this.order.against_score,
                odd_type: this.order.odd_type_id ,
                created_at: this.order.created,
                bet_team: this.order.bet_team,
                price_format: this.defaultPriceFormat,
                currency_symbol: this.wallet.currency_symbol
            }
        }
    },
    methods: {
        closeOddsHistory() {
            this.showOddsHistory = false
        },
        closeBetMatrix() {
            this.showBetMatrix = false
        },
        async setOrderLogs(market_id) {
            let orderLogs = await this.$store.dispatch('trade/getOrderLogs', market_id)
            this.orderLogs = orderLogs
        }
    }
}
</script>
