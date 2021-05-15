<template>
    <div class="flex items-center border-b text-white text-sm">
        <div class="w-2/12 py-1 px-3">{{ bet.created_at }}</div>
        <div class="w-4/12 py-1 px-3">
            <p>{{ bet.league_name }}</p>
            <p>{{ bet.home }} vs {{ bet.away }}</p>
        </div>
        <div class="w-4/12 py-1 px-3">
            <p v-if="bet.bet_info.betting_team">{{ bet.bet_info.betting_team }} {{ defaultPriceFormat }} ({{ bet.score_on_bet.home }} - {{ bet.score_on_bet.away }})</p>
            <p v-else>{{ bet.bet_info.market_flag == 'HOME' ? bet.home : bet.away }} {{ bet.bet_info.odd_type }} {{ defaultPriceFormat }} {{ bet.bet_info.odds_label }} ({{bet.score_on_bet.home}} - {{bet.score_on_bet.away}})</p>
        </div>
        <div class="flex items-center w-7/12 py-1 px-3 parent-bet-bar">
            <div v-for="(stake, status) in status" :key="status" class="flex items-center justify-center h-8 child-bet-bar" :class="status" :style="`width:${statusWidth(status)}; margin-right: 1px;`">
                <span class="uppercase font-semibold">{{ statusRename(status) }}</span> <span class="px-2">-</span> {{stake}}
            </div>
        </div>
        <div class="w-1/12 py-1 px-3">
            <a href="#" @click.prevent="openBetMatrix(`${bet.order_id}-betmatrix`)" class="text-center py-1 w-1/2 mr-1" title="Bet Matrix" v-if="oddTypesWithBetMatrix.includes(bet.odd_type_id) && !failedBetStatus.includes(bet.status)"><i class="fas fa-chart-area"></i></a>
            <a href="#" @click.prevent="openOddsHistory(`${bet.order_id}-orderlogs`)" class="text-center py-1 w-1/2 mr-1" :class="{'ml-5': !oddTypesWithBetMatrix.includes(bet.odd_type_id) || failedBetStatus.includes(bet.status)}" title="Odds History"><i class="fas fa-bars"></i></a>
        </div>
        <odds-history v-if="showOddsHistory" @close="closeOddsHistory" :market_id="bet.market_id" :event_id="bet.event_id" :key="`${bet.bet_id}-orderlogs`"></odds-history>
        <bet-matrix v-if="showBetMatrix" @close="closeBetMatrix" :market_id="bet.market_id" :analysis-data="analysisData" :event_id="bet.event_id" :key="`${bet.bet_id}-betmatrix`"></bet-matrix>
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
            showBetMatrix: false,
            oddTypesWithBetMatrix: [3, 4, 11, 12]
        }
    },
    computed: {
        ...mapState('settings', ['defaultPriceFormat']),
        ...mapState('trade', ['wallet', 'failedBetStatus']),
        analysisData() {
            return {
                home_score:  this.bet.score_on_bet.home,
                away_score:  this.bet.score_on_bet.away,
            }
        },
        status() {
            let data = {}
            Object.keys(this.bet.bet_status).map(key => {
                if(this.bet.bet_status[key]) {
                    this.$set(data, key, this.bet.bet_status[key])
                }
            })
            return data
        }
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
        },
        statusWidth(status) {
            let statusLength = Object.keys(this.status).length
            if(statusLength == 1) {
                return '100%'
            } else {
                if(status == 'placed') {
                    return '60%'
                } else {
                    return '40%'
                }
            }
        },
        statusRename(status) {
            let statusOverride = {
                placed: "PLACED",
                queued: "ON QUEUE",
                failed: "FAILED"
            }

            return statusOverride[status]
        }
    },
    filters: {
        twoDecimalPlacesFormat,
        moneyFormat
    }
}
</script>

<style lang="scss">
    .parent-bet-bar {
        .child-bet-bar:first-child {
            border-radius: 5px 0px 0px 5px;
        }

        .child-bet-bar:last-child {
            border-radius: 0px 5px 5px 0px;
        }
    }
</style>