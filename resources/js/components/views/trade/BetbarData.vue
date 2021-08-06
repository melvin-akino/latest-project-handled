<template>
    <div class="flex border-b text-white text-sm">
        <div class="w-3/12 py-1 px-3">{{bet.league_name}}</div>
        <div class="w-3/12 py-1 px-3"><a class="text-orange-500 underline" href="#" @click="openBetSlip">{{bet.home}} vs {{bet.away}}</a></div>
        <div class="w-2/12 text-center py-1 px-3">{{bet.created_at}}</div>
        <div class="w-3/12 text-center py-1 px-3">
            <span v-if="bet.bet_team === '' && bet.market_flag === 'HOME'">{{bet.home}}</span>
            <span v-if="bet.bet_team === '' && bet.market_flag === 'AWAY'">{{bet.away}}</span>
            <span v-if="bet.bet_team === '' && bet.market_flag === 'DRAW'">Draw</span>
            <span v-if="bet.bet_team != ''">{{ bet.bet_team }}</span>
        </div>
        <div class="w-2/12 text-center py-1 px-3" v-if="bet.bet_team == ''">
            {{bet.odd_type_name}} {{defaultPriceFormat}} {{bet.odd_label}} {{ `(${bet.score_on_bet})` }}
        </div>
        <div class="w-2/12 text-center py-1 px-3" v-if="bet.bet_team != ''">
            {{ bet.odd_type_name.includes("FT") ? "FT " : (bet.odd_type_name.includes("HT") ? "HT " : "") }}{{ bet.bet_team }} {{ defaultPriceFormat }} {{ `(${bet.score_on_bet})` }}
        </div>
        <div class="relative w-4/12 py-1 text-center" :class="{'success': bet.status==='SUCCESS', 'failed': bet.status==='FAILED', 'processing': bet.status==='PENDING'}">
            {{bet.provider_alias}} - {{Number(bet.stake) | moneyFormat}}@{{Number(bet.odds) | twoDecimalPlacesFormat}} - {{ bet.status == 'SUCCESS' ? 'PLACED' : bet.status }}
            <span v-if="bet.status == 'PENDING'">[Trying to place bet...]</span>
            <tooltip icon="fas fa-info-circle" :text="bet.error" color="text-white" v-if="bet.status == 'FAILED' && bet.error"></tooltip>
        </div>
        <div class="flex items-center w-20 px-1">
            <a href="#" @click.prevent="openBetMatrix(`${bet.order_id}-betmatrix`)" class="text-center py-1 w-1/2" title="Bet Matrix" v-if="!failedBetStatus.includes(bet.status)"><i class="fas fa-chart-area"></i></a>
            <a href="#" @click.prevent="openOddsHistory(`${bet.order_id}-orderlogs`)" class="text-center py-1 w-1/2" :class="{'ml-6': failedBetStatus.includes(bet.status)}" title="Odds History"><i class="fas fa-bars"></i></a>
        </div>
        <odds-history v-if="showOddsHistory" @close="closeOddsHistory" :market_id="bet.market_id" :event_id="bet.event_id" :key="`${bet.order_id}-orderlogs`"></odds-history>
        <bet-matrix v-if="showBetMatrix" @close="closeBetMatrix" :market_id="bet.market_id" :event_id="bet.event_id" :key="`${bet.order_id}-betmatrix`"></bet-matrix>
        <bet-dialog  v-if="showOddsHaveChanged" message="The odds have changed." mode="oddsHaveChanged" :key="`retry-${bet.order_id}`" :oldBet="oldBetData" :bet="newBetData" @close="closeOddsHaveChanged" @confirm="retryBet"></bet-dialog>
    </div>
</template>

<script>
import OddsHistory from './OddsHistory'
import BetMatrix from './BetMatrix'
import Tooltip from '../../component/Tooltip'
import BetDialog from '../../component/BetDialog'
import { mapState } from 'vuex'
import { twoDecimalPlacesFormat, moneyFormat } from '../../../helpers/numberFormat'
import Cookies from 'js-cookie'
import Swal from 'sweetalert2'
import bus from '../../../eventBus'

export default {
    props: ['bet'],
    components: {
        OddsHistory,
        BetMatrix,
        Tooltip,
        BetDialog
    },
    data() {
        return {
            showOddsHistory: false,
            showBetMatrix: false,
            showOddsHaveChanged: false,
            oldBetData: null,
            newBetData: null
        }
    },
    computed: {
        ...mapState('settings', ['defaultPriceFormat']),
        ...mapState('trade', ['wallet', 'failedBetStatus']),
        item() {
            return { ...this.bet }
        }
    },
    watch: {
        item: {
            deep: true,
            handler(value, oldValue) {
                this.oldBetData = oldValue
                this.newBetData = value

                if(oldValue.status != value.status && value.status == 'FAILED' && value.retry_type == 'manual-same-account' && value.odds_have_changed) {
                    this.showOddsHaveChanged = true
                }
            }
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
        closeOddsHaveChanged() {
            this.showOddsHaveChanged = false
        },
        openBetSlip() {
            let token = Cookies.get('mltoken')
            let { order_id, odd_type, market_flag } = this.bet

            axios.get('v1/orders/bet/event-details', { params: { order_id }, headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                let { market_common, event } = response.data.data
                let odds = []

                if(event.hasOwnProperty('market_odds')) {
                    if(event.market_odds.hasOwnProperty('main') && event.market_odds.main.hasOwnProperty(odd_type) && event.market_odds.main[odd_type].hasOwnProperty(market_flag)) {
                        odds.push(event.market_odds.main[odd_type][market_flag])
                    }

                    if(event.market_odds.hasOwnProperty('other')) {
                        Object.keys(event.market_odds.other).map(eventIdentifier => {
                            if(event.market_odds.other[eventIdentifier].hasOwnProperty(odd_type) && event.market_odds.other[eventIdentifier][odd_type].hasOwnProperty(market_flag)) {
                                odds.push(event.market_odds.other[eventIdentifier][odd_type][market_flag])
                            }
                        })
                    }

                    if(odds.length != 0) {
                        let odd
                        let same_market_common = odds.filter(odd => odd.market_common == market_common)[0]

                        if(same_market_common) {
                            odd = same_market_common
                        } else {
                            odd = odds[0]
                        }

                        if(response.data.hasOwnProperty('message')) {
                            Swal.fire({
                                icon: 'warning',
                                text: response.data.message
                            })
                        }

                        this.$store.commit('trade/OPEN_BETSLIP', { odd, game: event })
                        this.$store.dispatch('trade/setActivePopup', `${event.uid}-${odd.market_id}`)
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            text: 'Market type is no longer available'
                        })
                    }
                } else {
                    Swal.fire({
                        icon: 'warning',
                        text: 'Market is no longer available'
                    })
                }

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
        },
        retryBet() {
            let token = Cookies.get('mltoken')

            bus.$emit("SHOW_SNACKBAR", {
                id: "retrying-bet",
                color: "success",
                text: "Retrying bet..."
            });

            axios.post('v1/orders/bet/retry', this.bet, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(() => {
                this.closeOddsHaveChanged()
                bus.$emit("REMOVE_PREVIOUS_SNACKBAR")
                bus.$emit("SHOW_SNACKBAR", {
                    id: "retrying-bet-success",
                    color: "success",
                    text: "Bet was successfully retried."
                });
                this.$store.dispatch('trade/getBetbarData')
            })
            .catch(err => {
                bus.$emit("REMOVE_PREVIOUS_SNACKBAR")
                bus.$emit("SHOW_SNACKBAR", {
                    id: "retrying-bet-error",
                    color: "error",
                    text: err.response.data.message
                });
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
