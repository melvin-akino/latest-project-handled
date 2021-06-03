<template>
    <div class="betData">
        <odds-history v-if="openedOddsHistory.includes(order.order_id)" @close="$emit('closeOddsHistory', order.order_id)" :market_id="order.market_id" :event_id="order.event_id" :key="`${order.order_id}-orderlogs`"></odds-history>
        <bet-matrix v-if="openedBetMatrix.includes(order.order_id)" @close="$emit('closeBetMatrix', order.order_id)" :market_id="order.market_id" :event_id="order.event_id" :key="`${order.order_id}-betmatrix`"></bet-matrix>
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
