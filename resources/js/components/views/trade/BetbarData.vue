<template>
    <div class="flex border-b text-white text-sm">
        <div class="w-3/12 py-1 px-3">{{bet.league_name}}</div>
        <div class="w-5/12 py-1 px-3"><a class="text-orange-500 underline" href="#" @click="openBetSlip">{{bet.home}} vs {{bet.away}}</a></div>
        <div class="w-3/12 py-1 px-3">{{bet.created_at}}</div>
        <div class="w-3/12 py-1 px-3">
            <span v-if="bet.bet_team === '' && bet.market_flag === 'HOME'">{{bet.home}}</span>
            <span v-if="bet.bet_team === '' && bet.market_flag === 'AWAY'">{{bet.away}}</span>
            <span v-if="bet.bet_team === '' && bet.market_flag === 'DRAW'">Draw</span>
            <span v-if="bet.bet_team != ''">{{ bet.bet_team }}</span>
        </div>
        <div class="w-3/12 py-1 px-3" v-if="bet.bet_team == ''">
            {{bet.odd_type_name}} {{defaultPriceFormat}} {{bet.odd_label}} {{ `(${bet.score_on_bet})` }}
        </div>
        <div class="w-3/12 py-1 px-3" v-if="bet.bet_team != ''">
            {{ bet.odd_type_name.includes("FT") ? "FT " : (bet.odd_type_name.includes("HT") ? "HT " : "") }}{{ bet.bet_team }} {{ defaultPriceFormat }} {{ `(${bet.score_on_bet})` }}
        </div>
        <div class="w-4/12 py-1 text-center" :class="{'success': bet.status==='SUCCESS', 'failed': bet.status==='FAILED', 'processing': bet.status==='PENDING'}">
            {{bet.provider_alias}} - {{Number(bet.stake) | moneyFormat}}@{{Number(bet.odds) | twoDecimalPlacesFormat}} - {{ bet.status == 'SUCCESS' ? 'PLACED' : bet.status }}
            <tooltip icon="fas fa-info-circle" :text="bet.error" color="text-white" v-if="bet.status == 'FAILED'"></tooltip>
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
import Tooltip from '../../component/Tooltip'
import { mapState } from 'vuex'
import { twoDecimalPlacesFormat, moneyFormat } from '../../../helpers/numberFormat'
import Cookies from 'js-cookie'
import Swal from 'sweetalert2'

export default {
    props: ['bet'],
    components: {
        OddsHistory,
        BetMatrix,
        Tooltip
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
        },
        openBetSlip() {
            let token = Cookies.get('mltoken')
            let { order_id, odd_type, market_flag } = this.bet

            axios.get('v1/orders/bet/event-details', { params: { order_id }, headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                let { is_main, market_event_identifier, event } = response.data.data

                let marketType = is_main ? 'main' : 'other'
                let eventIdentifier = is_main ? null : market_event_identifier
                let odd
                if(event.hasOwnProperty('market_odds')) {
                    if(marketType == 'main' && event.market_odds.hasOwnProperty('main') && event.market_odds.main.hasOwnProperty(odd_type) && event.market_odds.main[odd_type].hasOwnProperty(market_flag)) {
                        odd = event.market_odds.main[odd_type][market_flag]
                    } else if(marketType == 'other' && event.market_odds.hasOwnProperty('other') && event.market_odds.other.hasOwnProperty(eventIdentifier) && event.market_odds.other[eventIdentifier].hasOwnProperty(odd_type)&& event.market_odds.other[eventIdentifier][odd_type].hasOwnProperty(market_flag)) {
                        odd = event.market_odds.other[eventIdentifier][odd_type][market_flag]
                    }
                }

                if(response.data.hasOwnProperty('message')) {
                    Swal.fire({
                        icon: 'warning',
                        text: response.data.message
                    })
                }

                this.$store.commit('trade/OPEN_BETSLIP', { odd, game: event, marketType, eventIdentifier })
                this.$store.dispatch('trade/setActivePopup', `${event.uid}-${odd.market_id}`)
            })
            .catch(err => {
                if(err.response.status == 404) {
                    Swal.fire({
                        icon: 'error',
                        text: err.response.data.message
                    })
                }
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        }
    },
    filters: {
        twoDecimalPlacesFormat,
        moneyFormat
    }
}
</script>
