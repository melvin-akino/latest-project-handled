<template>
    <div class="betData">
        <odds-history v-if="openedOddsHistory.includes(order.order_id)" @close="$emit('closeOddsHistory', order.order_id)" :market_id="order.market_id"></odds-history>
        <bet-matrix v-if="openedBetMatrix.includes(order.order_id)" @close="$emit('closeBetMatrix', order.order_id)" :market_id="order.market_id" :analysis-data="analysisData" :event_id="order.event_id"></bet-matrix>
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
            showOddsHistory: false,
            showBetMatrix: false
        }
    },
    computed: {
        ...mapState('settings', ['defaultPriceFormat']),
        ...mapState('trade', ['wallet']),
        analysisData() {
            return {
                stake: this.order.stake,
                points: this.order.points,
                price: this.order.odds,
                home_score: this.order.home_score,
                away_score: this.order.away_score,
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
        }
    }
}
</script>
