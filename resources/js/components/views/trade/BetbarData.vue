<template>
    <div class="flex border-b text-white text-sm">
        <div class="w-3/12 py-1 px-3">{{bet.league_name}}</div>
        <div class="w-4/12 py-1 px-3">{{bet.home}} vs {{bet.away}}</div>
        <div class="w-3/12 py-1 px-3">{{bet.created_at}}</div>
        <div class="w-3/12 py-1 px-3">
            <span v-if="bet.bet_info[0]==='HOME'">{{bet.home}}</span>
            <span v-if="bet.bet_info[0]==='AWAY'">{{bet.away}}</span>
            <span v-if="bet.bet_info[0]==='DRAW'">Draw</span>
        </div>
        <div class="w-4/12 py-1 px-3">{{bet.bet_info[1]}} {{defaultPriceFormat}} {{oddTypesWithBetMatrix.includes(bet.odd_type_id) ? bet.bet_info[4] : ''}} {{bet.game_schedule == 'inplay' ? `(${bet.score})` : ''}}</div>
        <div class="w-4/12 py-1 text-center" :class="{'success': bet.status==='SUCCESS', 'failed': bet.status==='FAILED', 'processing': bet.status==='PENDING'}">
            {{bet.provider_alias}} - {{Number(bet.bet_info[3]) | moneyFormat}}@{{bet.bet_info[2]}} - {{bet.status}}
        </div>
        <div class="flex items-center w-24">
            <a href="#" @click.prevent="showBetMatrix = true" class="text-center py-1 w-1/2" title="Bet Matrix"><i v-if="oddTypesWithBetMatrix.includes(bet.odd_type_id)" class="fas fa-chart-area"></i></a>
            <a href="#" @click.prevent="showOddsHistory = true" class="text-center py-1 w-1/2" title="Odds History"><i class="fas fa-bars"></i></a>
        </div>
        <odds-history v-if="showOddsHistory" @close="closeOddsHistory" :market_id="bet.market_id" :logs="orderLogs"></odds-history>
        <bet-matrix v-if="showBetMatrix" @close="closeBetMatrix" :market_id="bet.market_id" :analysis-data="analysisData"></bet-matrix>
    </div>
</template>

<script>
import OddsHistory from './OddsHistory'
import BetMatrix from './BetMatrix'
import { mapState } from 'vuex'
import { moneyFormat } from '../../../helpers/numberFormat'

export default {
    props: ['bet'],
    components: {
        OddsHistory,
        BetMatrix
    },
    data() {
        return {
            orderLogs: [],
            showOddsHistory: false,
            showBetMatrix: false,
            oddTypesWithBetMatrix: [3, 4, 11, 12]
        }
    },
    computed: {
        ...mapState('settings', ['defaultPriceFormat']),
        ...mapState('trade', ['wallet']),
        analysisData() {
            return {
                stake: Number(this.bet.bet_info[3]).toFixed(2),
                points: this.bet.bet_info[4],
                price:  this.bet.bet_info[2],
                bet_score:  this.bet.bet_score,
                against_score:  this.bet.against_score,
                odd_type: this.bet.odd_type_id,
                created_at: this.bet.created_at,
                bet_team: this.bet.bet_info[5],
                price_format: this.defaultPriceFormat,
                currency_symbol: this.wallet.currency_symbol
            }
        }
    },
    mounted() {
        this.setOrderLogs(this.bet.market_id)
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
    },
    filters: {
        moneyFormat
    }
}
</script>
