<template>
    <div class="flex border-b text-white text-sm">
        <div class="w-3/12 py-1 px-3">{{bet.league_name}}</div>
        <div class="w-5/12 py-1 px-3">{{bet.home}} vs {{bet.away}}</div>
        <div class="w-3/12 py-1 px-3">{{bet.created_at}}</div>
        <div class="w-3/12 py-1 px-3">
            <span v-if="bet.bet_info[6] === '' && bet.bet_info[0] === 'HOME'">{{bet.home}}</span>
            <span v-if="bet.bet_info[6] === '' && bet.bet_info[0] === 'AWAY'">{{bet.away}}</span>
            <span v-if="bet.bet_info[6] === '' && bet.bet_info[0] === 'DRAW'">Draw</span>
            <span v-if="bet.bet_info[6] != ''">{{ bet.bet_info[6] }}</span>
        </div>
        <div class="w-3/12 py-1 px-3" v-if="bet.bet_info[6] == ''">
            {{bet.bet_info[1]}} {{defaultPriceFormat}} {{bet.bet_info[4]}} {{ `(${bet.score_on_bet})` }}
        </div>
        <div class="w-3/12 py-1 px-3" v-if="bet.bet_info[6] != ''">
            {{ bet.bet_info[1].indexOf("FT") >= 0 ? "FT " : (bet.bet_info[1].indexOf("HT") >= 0 ? "HT " : "") }}{{ bet.bet_info[6] }} {{ defaultPriceFormat }} {{ `(${bet.score_on_bet})` }}
        </div>
        <div class="w-4/12 py-1 text-center" :class="{'success': bet.status==='SUCCESS', 'failed': bet.status==='FAILED', 'processing': bet.status==='PENDING'}">
            {{bet.provider_alias}} - {{Number(bet.bet_info[3]) | moneyFormat}}@{{Number(bet.bet_info[2]) | twoDecimalPlacesFormat}} - {{ bet.status == 'SUCCESS' ? 'PLACED' : bet.status }}
        </div>
        <div class="flex items-center w-20 px-1">
            <a href="#" @click.prevent="openBetMatrix(`${bet.order_id}-betmatrix`)" class="text-center py-1 w-1/2" title="Bet Matrix" v-if="!failedBetStatus.includes(bet.status)"><i class="fas fa-chart-area"></i></a>
            <a href="#" @click.prevent="openOddsHistory(`${bet.order_id}-orderlogs`)" class="text-center py-1 w-1/2" :class="{'ml-5': failedBetStatus.includes(bet.status)}" title="Odds History"><i class="fas fa-bars"></i></a>
        </div>
        <odds-history v-if="showOddsHistory" @close="closeOddsHistory" :market_id="bet.market_id" :event_id="bet.event_id" :key="`${bet.order_id}-orderlogs`"></odds-history>
        <bet-matrix v-if="showBetMatrix" @close="closeBetMatrix" :market_id="bet.market_id" :event_id="bet.event_id" :key="`${bet.order_id}-betmatrix`"></bet-matrix>
    </div>
</template>

<script>
import OddsHistory from './OddsHistory'
import BetMatrix from './BetMatrix'
import { mapState } from 'vuex'
import { twoDecimalPlacesFormat, moneyFormat } from '../../../helpers/numberFormat'

export default {
    props: ['bet'],
    components: {
        OddsHistory,
        BetMatrix
    },
    data() {
        return {
            showOddsHistory: false,
            showBetMatrix: false
        }
    },
    computed: {
        ...mapState('settings', ['defaultPriceFormat']),
        ...mapState('trade', ['wallet', 'failedBetStatus']),
    },
    methods: {
        openOddsHistory(data) {
            this.showOddsHistory = true
            this.$store.dispatch('trade/setActivePopup', data)
        },
        openBetMatrix(data) {
            this.showBetMatrix = true
            this.$store.dispatch('trade/setActivePopup', data)
        },
        closeOddsHistory() {
            this.showOddsHistory = false
        },
        closeBetMatrix() {
            this.showBetMatrix = false
        }
    },
    filters: {
        twoDecimalPlacesFormat,
        moneyFormat
    }
}
</script>
