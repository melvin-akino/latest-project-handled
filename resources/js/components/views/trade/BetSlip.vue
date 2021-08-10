<template>
    <div class="betslip absolute flex justify-center items-center">
        <dialog-drag :title="'Bet Slip - '+market_id" :options="options" @close="closeBetSlip($vnode.key)" @mousedown.native="$store.dispatch('trade/setActivePopup', $vnode.key)" v-betslip="activePopup==$vnode.key">
            <div class="flex flex-col justify-center items-center w-full h-full absolute top-0 left-0 bg-gray-200 z-10" :class="{'hidden': !isLoadingMarketDetailsAndProviders}">
                <span class="betSlipSpinner"><i class="fas fa-circle-notch fa-spin"></i></span>
                <span class="text-center mt-2">{{loadingMessage}}</span>
            </div>
            <div class="container mx-auto p-2">
                <div class="flex justify-between items-center w-full leagueAndTeamDetails">
                    <div class="flex items-center w-3/4">
                        <span class="text-white uppercase font-bold mr-2 my-2 px-2 bg-orange-500">{{market_details.odd_type}}</span>
                        <span class="text-gray-800 font-bold my-2 pr-6">{{market_details.league_name}}</span>
                        <a href="#" @click.prevent="openBetMatrix(`${odd_details.betslip_id}-betmatrix`)" class="text-center py-1 pr-1" title="Bet Matrix" v-if="odd_details.has_bet"><i class="fas fa-chart-area"></i></a>
                        <a href="#" @click.prevent="openOddsHistory(`${odd_details.betslip_id}-orderlogs`)" class="text-center py-1" title="Odds History"><i class="fas fa-bars"></i></a>
                    </div>
                    <div class="flex items-center">
                        <span class="text-center py-1 pr-1 mr-2"><i class="far fa-calendar-alt"></i> {{formattedRefSchedule[0]}}</span>
                        <span class="text-center py-1"><i class="far fa-clock"></i> {{formattedRefSchedule[1]}}</span>
                    </div>
                </div>
                <div class="flex items-center w-full leagueAndTeamDetails">
                    <div class="home py-3" :class="[market_details.market_flag==='HOME' ? 'bg-white shadow-xl p-3' : '']">
                        <span class="font-bold bg-green-500 text-white mr-1 p-2 rounded-lg">Home</span>
                        <span class="w-full text-gray-800 font-bold">{{market_details.home}}</span>
                    </div>
                    <span class="text-sm text-gray-800 px-3">VS.</span>
                    <div class="away py-3" :class="[market_details.market_flag==='AWAY' ? 'bg-white shadow-xl p-3' : '']">
                        <span class="font-bold bg-red-600 text-white mr-1 p-2 rounded-lg">Away</span>
                        <span class="w-full text-gray-800 font-bold">{{market_details.away}}</span>
                    </div>
                </div>
                <div class="flex w-full">
                    <div class="flex flex-col mt-4 mr-3 w-3/5 h-full">
                        <span class="spread-refresh"><a href="#" @click="reloadSpread()"><i class="fas fa-retweet"></i></a></span>
                        <div class="flex flex-col items-center bg-white shadow-xl mb-2" v-if="oddTypesWithSpreads.includes(market_details.odd_type)">
                            <div class="text-white uppercase font-bold p-2 bg-orange-500 w-full text-center">{{market_details.odd_type}}</div>
                            <div class="relative flex justify-center items-center p-2" v-if="spreads.length != 0">
                                <a href="#" class="m-1 w-16 text-center text-sm" :class="[spread.points == points ? 'text-white bg-orange-500 px-1 py-1' : 'text-gray-800']" v-for="(spread, index) in spreads" :key="index" @click="changePoint(spread.points, spread.market_id, spread.odds)">{{spread.points}}</a>
                            </div>
                        </div>
                        <div class="flex flex-col bg-white shadow-xl">
                            <div class="flex justify-between items-center py-2 bg-orange-500 text-white">
                                <span class="relative w-1/5 text-sm font-bold text-center pl-3">
                                    <label class="selectAll absolute text-gray-500 font-bold">
                                        <input class="mr-2 leading-tight" type="checkbox" v-model="selectAllProviders" @change="toggleAllProviders" :disabled="!retrievedMarketData || minMaxProviders.filter(minmax => minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())).length <= 1">
                                    </label>
                                    <span class="selectAllLabel relative">Select All</span>
                                </span>
                                <span class="w-1/5 text-sm font-bold text-center">Min</span>
                                <span class="w-1/5 text-sm font-bold text-center">Max</span>
                                <span class="w-1/5 text-sm font-bold text-center">Price</span>
                                <span class="w-1/5"></span>
                            </div>
                            <div class="flex items-center py-2" v-for="minmax in minMaxProviders" :key="minmax.provider_id">
                                <span class="relative w-1/5 text-sm font-bold text-center pl-3">
                                    <label class="providerCheckbox absolute text-gray-500 font-bold">
                                        <input class="mr-2 leading-tight" type="checkbox" @change="toggleMinmaxProviders(minmax, minmax.provider_id)" :checked="selectedProviders.includes(minmax.provider_id) && minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())" :disabled="!minmax.hasMarketData || underMaintenanceProviders.includes(minmax.provider.toLowerCase())">
                                    </label>
                                    {{minmax.provider}}
                                </span>
                                <span class="w-1/5 text-sm text-center" v-if="minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">{{minmax.min | moneyFormat}}</span>
                                <span class="w-1/5 text-sm text-center" v-if="minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">{{minmax.max | moneyFormat}}</span>
                                <a href="#" @click.prevent="updatePrice(minmax.price)" class="w-1/5 text-sm font-bold underline text-center" v-if="minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">{{minmax.price | twoDecimalPlacesFormat}}</a>
                                <span class="w-1/5 text-sm text-center" v-if="minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">{{minmax.age}}</span>
                                <div class="text-sm text-center" v-if="!minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">
                                    <div v-show="market_details.providers.includes(minmax.provider_id) && odd_details.odd.market_id && !minmax.noMarketAvailable">Retrieving Market<span class="pl-1"><i class="fas fa-circle-notch fa-spin"></i></span></div>
                                    <div v-show="!market_details.providers.includes(minmax.provider_id) || minmax.noMarketAvailable || !odd_details.odd.market_id">
                                        <span v-if="hasNewOddsInTradeWindow">This market is now unavailable please refresh the bet slip</span>
                                        <span v-else>No Market Available</span>
                                    </div>
                                </div>
                                <div class="text-sm text-center" v-if="underMaintenanceProviders.includes(minmax.provider.toLowerCase())">Provider under maintenance</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col mt-4 p-2 shadow-xl bg-white w-2/5 h-full">
                        <div class="advanceBetSlipInfo" :class="{'hidden': betSlipSettings.adv_betslip_info == 0, 'block': betSlipSettings.adv_betslip_info == 1}">
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm">Minimum Stake</span>
                                <span class="text-sm" v-if="!$v.inputPrice.$dirty">{{lowestMin ? lowestMin : 0 | moneyFormat}}</span>
                                <span class="text-sm" v-else>{{lowestMinByValidPrice ? lowestMinByValidPrice : 0 | moneyFormat}}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm">Maximum Stake</span>
                                <span class="text-sm" v-if="!$v.inputPrice.$dirty">{{highestMax ? highestMax : 0 | moneyFormat}}</span>
                                <span class="text-sm" v-else>{{highestMaxByValidPrice ? highestMaxByValidPrice : 0 | moneyFormat}}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm">{{market_details.odd_type}}</span>
                                <span class="text-sm">{{points}}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm">To Win</span>
                                <span class="text-sm">{{towin | moneyFormat}}</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <div class="flex justify-start items-center">
                                <label class="text-sm">
                                    Min Price
                                    <tooltip icon="fas fa-info-circle" text="Minimum Accepted Price" color="text-gray-700"></tooltip>
                                </label>
                            </div>
                            <div class="flex justify-end items-center">
                                <input type="text" class="betslipInput w-40 shadow appearance-none border rounded text-sm text-right py-1 px-3 text-gray-700 leading-tight focus:outline-none" v-model="$v.inputPrice.$model" @keyup="priceInput" :disabled="!retrievedMarketData || minMaxData.length == 0">
                            </div>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <div class="flex justify-start items-center">
                                <label class="text-sm">
                                    Stake
                                    <tooltip icon="fas fa-info-circle" text="By clicking the 'MIN' button this inputs your MINIMUM possible stake across all providers selected. By clicking the 'MAX' button, this inputs your MAXIMUM possible stake across all providers selected." color="text-gray-700"></tooltip>
                                </label>
                            </div>
                            <div class="relative flex justify-end items-center">
                                <button class="minMaxBtn minStake absolute bg-primary-500 px-3 text-white rounded text-xs uppercase focus:outline-none hover:bg-primary-600" @click="setMinStake">MIN</button>
                                <input ref="stake" type="text" class="betslipInput w-40 shadow appearance-none border rounded text-sm text-green-600 text-right py-1 pr-10 leading-tight focus:outline-none" v-model="$v.orderForm.stake.$model" @keyup="stakeInput" :disabled="qualifiedProviders.length == 0">
                                <button class="minMaxBtn absolute bg-primary-500 px-3 text-white rounded text-xs uppercase focus:outline-none hover:bg-primary-600" @click="setMaxStake">MAX</button>
                            </div>
                        </div>
                        <div class="flex justify-between items-center py-2 text-green-600">
                            <span class="text-sm mr-12">Average Price</span>
                            <span class="text-sm">{{averagePrice | formatAverage}}</span>
                        </div>
                        <div class="justify-between items-center py-2 hidden" :class="{'hidden': betSlipSettings.adv_placement_opt == 0, 'flex': betSlipSettings.adv_placement_opt == 1}">
                            <label class="text-sm flex items-center">
                                <span class="mr-4">Fast Bet</span>
                                <input class="outline-none rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="radio" value="FAST_BET" v-model="orderForm.betType" @change="clearOrderMessage">
                            </label>
                            <label class="text-sm flex items-center">
                                <span class="mr-4">Best Price</span>
                                <input class="outline-none rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="radio" value="BEST_PRICE" v-model="orderForm.betType" @change="clearOrderMessage">
                            </label>
                        </div>
                        <span v-if="isPlacingOrder" class="text-sm text-gray-700">Placing bet, please check the recent orders</span>
                        <div v-if="!isPlacingOrder && isDoneBetting" class="orderMessage relative flex justify-center items-center text-white py-1 px-2 mt-2 w-full rounded" :class="betMessageColor">
                            <span class="text-xs mr-1" v-show="!isBetSuccessful || betMessageColor == 'warning'"><i class="fas fa-exclamation-triangle"></i></span>
                            <span class="text-xs mr-1" v-show="isBetSuccessful && betMessageColor != 'warning'"><i class="fas fa-check"></i></span>
                            <span class="px-2" v-if="(!$v.orderForm.stake.decimal || !$v.inputPrice.decimal)  && !isBetSuccessful && hasErrorOnInput">Stake and price should have a numeric value.</span>
                            <span class="px-2" v-else-if="(!$v.orderForm.stake.required || !$v.inputPrice.required) && !isBetSuccessful && hasErrorOnInput">Please input stake and price.</span>
                            <span class="px-2" v-else-if="(!$v.orderForm.stake.minValue || !$v.inputPrice.minValue)  && !isBetSuccessful && hasErrorOnInput">Input a valid stake and price. Stake Min: 1, Price Min: 0</span>
                            <span class="px-2" v-else v-html="orderMessage"></span>
                            <span class="absolute clearOrderMessage float-right cursor-pointer text-xs" @click="isDoneBetting = false"><i class="fas fa-times-circle"></i></span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center w-full" v-if="market_details.user_status == 1">
                    <button v-if="isPlacingOrder" class="bg-orange-500 text-white rounded-lg w-full text-sm uppercase p-2 mt-2 opacity-75" disabled>Placing Order... <span class="text-sm"><i class="fas fa-circle-notch fa-spin"></i></span></button>
                    <button v-if="!isPlacingOrder" @click="bet" class="bg-orange-500 text-white rounded-lg w-full text-sm uppercase p-2 mt-2 focus:outline-none" :class="[!retrievedMarketData || minMaxData.length == 0 || showAwaitingPlacement ? 'opacity-75' : 'hover:bg-orange-600']" :disabled="!retrievedMarketData || minMaxData.length == 0 || showAwaitingPlacement">Place Order</button>
                </div>
            </div>
        </dialog-drag>
        <odds-history v-if="showOddsHistory" @close="closeOddsHistory" :market_id="market_id" :event_id="odd_details.game.uid" :key="`${odd_details.betslip_id}-orderlogs`"></odds-history>
        <bet-matrix v-if="showBetMatrix" @close="closeBetMatrix" :market_id="market_id" :event_id="odd_details.game.uid" :key="`${odd_details.betslip_id}-betmatrix`"></bet-matrix>
        <bet-dialog v-if="showAwaitingPlacement && pendingBet" message="You have bet(s) currently awaiting placement" mode="awaitingPlacement" :key="`awaitingPlacement-${odd_details.betslip_id}`" :bet="pendingBet" @close="closeAwaitingPlacement" @confirm="placeOrder"></bet-dialog>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import _ from 'lodash'
import OddsHistory from './OddsHistory'
import BetMatrix from './BetMatrix'
import DialogDrag from 'vue-dialog-drag'
import Tooltip from '../../component/Tooltip'
import BetDialog from '../../component/BetDialog'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'
import { twoDecimalPlacesFormat, moneyFormat, formatAverage } from '../../../helpers/numberFormat'
import { moveToFirstElement } from '../../../helpers/array'
import { required, decimal, minValue } from 'vuelidate/lib/validators'

export default {
    props: ['odd_details'],
    components: {
        DialogDrag,
        OddsHistory,
        BetMatrix,
        Tooltip,
        BetDialog
    },
    data() {
        return {
            market_details: {},
            formattedRefSchedule: [],
            inputPrice: null,
            points: this.odd_details.odd.points || null,
            market_id: this.odd_details.odd.market_id,
            orderForm: {
                stake: '',
                betType: 'FAST_BET',
                markets: []
            },
            minMaxProviders: [],
            selectedProviders: [],
            minMaxData: [],
            oddTypesWithSpreads: ['HDP', 'HT HDP', 'OU', 'HT OU'],
            orderMessage: '',
            options: {
                width: 825,
                buttonPin: false,
            },
            isPlacingOrder: null,
            isDoneBetting: false,
            isLoadingMarketDetailsAndProviders: true,
            isBetSuccessful: null,
            hasErrorOnInput: false,
            showOddsHistory: false,
            showBetMatrix: false,
            showAwaitingPlacement: false,
            disabledBookies: [],
            retrievedMarketData: false,
            startPointIndex: 0,
            endPointIndex: 5,
            minMaxUpdateCounter: 0,
            hasNewOddsInTradeWindow: false,
            toggledProviders: false,
            selectAllProviders: false,
            loadingMessage: 'Loading Market Details...',
            betStatus: null,
            spreads: []
        }
    },
    validations: {
        inputPrice: { required, minValue: minValue(0), decimal },
        orderForm: {
            stake:  { required, minValue: minValue(1), decimal }
        }
    },
    computed: {
        ...mapState('trade', ['activePopup', 'popupZIndex', 'bookies', 'betSlipSettings', 'wallet', 'underMaintenanceProviders', 'receivedOrderStatusIds', 'bets']),
        ...mapState('settings', ['defaultPriceFormat']),
        lowestPrice() {
            if(!_.isEmpty(this.minMaxData)) {
                let minPrices = this.minMaxData.filter(minmax => minmax.price != null).map(minmax => minmax.price)
                if(!_.isEmpty(minPrices)) {
                    return Math.min(...minPrices)
                } else {
                    return null
                }
            } else {
                return null
            }
        },
        lowestMin() {
            if(!_.isEmpty(this.minMaxData)) {
                let minValues = this.minMaxData.filter(minmax => minmax.min != null).map(minmax => minmax.min)
                if(!_.isEmpty(minValues)) {
                    return Math.min(...minValues)
                } else {
                    return null
                }
            } else {
                return null
            }
        },
        highestMax() {
            if(!_.isEmpty(this.minMaxData)) {
                let maxValues = this.minMaxData.filter(minmax => minmax.max != null).map(minmax => minmax.max)
                if(!_.isEmpty(maxValues)) {
                    return maxValues.reduce((firstMax, secondMax) => Number(twoDecimalPlacesFormat(firstMax + secondMax)), 0)
                } else {
                    return null
                }
            } else {
                return null
            }
        },
        lowestMinByValidPrice() {
            if(!_.isEmpty(this.qualifiedProviders)) {
                let minValues = this.qualifiedProviders.filter(minmax => minmax.min != null).map(minmax => minmax.min)
                if(!_.isEmpty(minValues)) {
                    return Math.min(...minValues)
                } else {
                    return null
                }
            } else {
                return null
            }
        },
        highestMaxByValidPrice() {
            if(!_.isEmpty(this.qualifiedProviders)) {
                let maxValues = this.qualifiedProviders.filter(minmax => minmax.max != null).map(minmax => minmax.max)
                if(!_.isEmpty(maxValues)) {
                    return maxValues.reduce((firstMax, secondMax) => Number(twoDecimalPlacesFormat(firstMax + secondMax)), 0)
                } else {
                    return null
                }
            } else {
                return null
            }
        },
        betDetails() {
            if(!_.isEmpty(this.qualifiedProviders) && this.orderForm.stake) {
                let sortedByPriceArray = this.qualifiedProviders.sort((a, b) => (a.price < b.price) ? 1 : -1)
                let towins = []
                let stakes = []
                let rawToWins = []
                let placedStake = this.orderForm.stake
                sortedByPriceArray.map(sortedByPrice => {
                    let price = this.market_details.odd_type.includes('1X2') || this.market_details.odd_type.includes('OE') ? sortedByPrice.price - 1 : sortedByPrice.price
                    if(placedStake > sortedByPrice.max) {
                        stakes.push(sortedByPrice.max)
                        towins.push(sortedByPrice.max * price)
                        rawToWins.push(sortedByPrice.max * sortedByPrice.price)
                        placedStake = placedStake - sortedByPrice.max
                    } else if(placedStake <= sortedByPrice.max && placedStake >= sortedByPrice.min) {
                        stakes.push(placedStake)
                        towins.push(placedStake * price)
                        rawToWins.push(placedStake * sortedByPrice.price)
                        placedStake = 0
                    }
                })
                let totalStake = stakes.reduce((firstStake, secondStake) => firstStake + secondStake, 0)
                let totalTowin = towins.reduce((firstTowin, secondTowin) => firstTowin + secondTowin, 0)
                let totalRawTowin = rawToWins.reduce((firstTowin, secondTowin) => firstTowin + secondTowin, 0)
                if(!_.isEmpty(towins) && !_.isEmpty(stakes) && this.orderForm.stake <= this.highestMaxByValidPrice) {
                    return { totalStake, totalTowin, totalRawTowin }
                } else {
                    return {}
                }
            } else {
                return {}
            }
        },
        price() {
            return Number(this.inputPrice)
        },
        averagePrice() {
            if(this.betDetails.hasOwnProperty('totalStake') && this.betDetails.hasOwnProperty('totalRawTowin')) {
                return this.betDetails.totalRawTowin / this.betDetails.totalStake
            } else {
                return 0
            }
        },
        towin() {
            if(this.betDetails.hasOwnProperty('totalTowin')) {
                return this.betDetails.totalTowin
            } else {
                return 0
            }
        },
        qualifiedProviders() {
            if(!_.isEmpty(this.minMaxData) && !this.$v.inputPrice.$invalid) {
                return this.minMaxData.filter(minmax => minmax.price != null && minmax.price >= this.price)
            } else {
                return []
            }
        },
        betMessageColor() {
            let betStatusColors = {
                200: 'success',
                210: 'continue',
                211: 'warning'
            }

            if(this.isBetSuccessful) {
                return betStatusColors[this.betStatus]
            } else {
                return 'failed'
            }
        },
        tradeWindowOdds() {
            return this.getTradeWindowData('odds')
        },
        tradeWindowPoints() {
            return this.getTradeWindowData('points') || null
        },
        pendingBet() {
            let similarPendingBet = this.bets.filter(bet => bet.status == 'PENDING' && bet.event_id == this.odd_details.game.uid && bet.odd_type == this.market_details.odd_type && bet.odd_label == this.points && bet.market_flag == this.market_details.market_flag)
            if(similarPendingBet.length != 0) {
                return similarPendingBet[0]
            } else {
                return null
            }
        }
    },
    watch: {
        minMaxProviders: {
            deep: true,
            handler(value) {
                this.minMaxUpdateCounter++
                if(this.minMaxUpdateCounter == this.minMaxProviders.length) {
                    this.selectAllProviders = true
                }

                if(!this.toggledProviders) {
                    this.minMaxData = value.filter(minmax => minmax.price != null && !this.underMaintenanceProviders.includes(minmax.provider.toLowerCase()))
                    this.selectedProviders = this.minMaxData.map(minmax => minmax.provider_id)
                }

                if(!this.$v.orderForm.stake.$dirty) {
                    this.orderForm.stake = this.highestMax
                }

                if(!this.$v.inputPrice.$dirty) {
                    this.inputPrice = this.lowestPrice
                } else if(this.$v.inputPrice.$dirty && !this.$v.orderForm.stake.$dirty) {
                    this.orderForm.stake = this.highestMaxByValidPrice
                }

                if(this.qualifiedProviders.length == 0 && this.$v.inputPrice.$dirty && !this.$v.inputPrice.$invalid) {
                    if(this.minMaxData.length != 0) {
                        this.availableMarketsTooLow()
                    } else {
                        this.orderForm.stake = null
                        this.inputPrice = null
                        this.clearOrderMessage()
                    }
                } else {
                    if(!this.isBetSuccessful) {
                        this.clearOrderMessage()
                    }
                }
            }
        },
        tradeWindowOdds(newValue, oldValue) {
            if(!oldValue && newValue) {
                this.hasNewOddsInTradeWindow = true
            } else {
                this.hasNewOddsInTradeWindow = false
            }
        },
        tradeWindowPoints(value) {
            this.points = value
            this.getMarketDetails(false, false)
        },
        'market_details.spreads'() {
            this.initialSpread()
        },
        'odd_details.game.market_odds'() {
            this.getMarketDetails(false, false)
            this.initialSpread()
        },
        pendingBet(value) {
            if(!value) {
                this.showAwaitingPlacement = false
            }
        }
    },
    mounted() {
        this.getMarketDetails()
        this.$store.dispatch('trade/getBetSlipSettings')
    },
    methods: {
        getTradeWindowData(key) {
            if(!_.isEmpty(this.market_details) && this.odd_details.game.hasOwnProperty('market_odds')) {
                let odd_type = this.market_details.odd_type
                let market_flag = this.market_details.market_flag
                let market_common = this.odd_details.odd.market_common
                let odds = []
                if(this.odd_details.game.market_odds.hasOwnProperty('main') && this.odd_details.game.market_odds.main.hasOwnProperty(odd_type) && this.odd_details.game.market_odds.main[odd_type].hasOwnProperty(market_flag)) {
                    odds.push(this.odd_details.game.market_odds.main[odd_type][market_flag])
                }

                if(this.odd_details.game.market_odds.hasOwnProperty('other')) {
                    Object.keys(this.odd_details.game.market_odds.other).map(eventIdentifier => {
                        if(this.odd_details.game.market_odds.other[eventIdentifier].hasOwnProperty(odd_type) && this.odd_details.game.market_odds.other[eventIdentifier][odd_type].hasOwnProperty(market_flag)) {
                            odds.push(this.odd_details.game.market_odds.other[eventIdentifier][odd_type][market_flag])
                        }
                    })
                }

                if(odds.length != 0) {
                    let same_market_common = odds.filter(odd => odd.market_common == market_common)[0]
                    if(same_market_common) {
                        return same_market_common[key]
                    } else {
                        return odds[0][key]
                    }
                } else {
                    return this.odd_details.odd[key]
                }
            } else {
                return this.odd_details.odd[key]
            }
        },
        initialSpread() {
            let points = []

            if(!_.isEmpty(this.market_details)) {
                let odd_type = this.market_details.odd_type
                let market_flag = this.market_details.market_flag

                if(this.odd_details.game.hasOwnProperty('market_odds')) {
                    if(this.odd_details.game.market_odds.main.hasOwnProperty(odd_type) && this.odd_details.game.market_odds.main[odd_type].hasOwnProperty(market_flag)) {
                        points.push(this.odd_details.game.market_odds.main[odd_type][market_flag])
                    }

                    if(this.odd_details.game.market_odds.hasOwnProperty('other')) {
                        Object.keys(this.odd_details.game.market_odds.other).map(key => {
                            if(this.odd_details.game.market_odds.other[key].hasOwnProperty(odd_type) && this.odd_details.game.market_odds.other[key][odd_type].hasOwnProperty(market_flag)) {
                                points.push(this.odd_details.game.market_odds.other[key][odd_type][market_flag])
                            }
                        })
                    }
                }

                if(this.market_details.spreads.length != 0 && this.odd_details.game.has_other_markets) {
                    this.market_details.spreads.map(spread => {
                        points.map(point => {
                            if(spread.points == point.points) {
                                this.$set(spread, 'market_id', point.market_id)
                            }
                        })
                    })

                    if(this.odd_details.game.market_odds.hasOwnProperty('other')) {
                        this.spreads = moveToFirstElement(points, 'market_id', 'points', this.odd_details.odd.market_id, this.odd_details.odd.points)
                    } else {
                        this.spreads = moveToFirstElement(this.market_details.spreads, 'market_id', 'points', this.odd_details.odd.market_id, this.odd_details.odd.points)
                    }
                } else {
                    this.spreads = points
                }
            }
        },
        reloadSpread(placeOrder = false) {
            this.getMarketDetails(false)
            this.hasNewOddsInTradeWindow = false

            if(!placeOrder) {
                this.isLoadingMarketDetailsAndProviders = true
                this.clearOrderMessage()
            }

            if(this.$v.inputPrice.$invalid) {
                this.$v.inputPrice.$reset()
                this.inputPrice = this.lowestPrice
            }

            if(this.$v.orderForm.stake.$invalid) {
                this.$v.orderForm.stake.$reset()
                this.orderForm.stake = this.highestMaxByValidPrice
            }
        },
        setMinStake() {
            this.orderForm.stake = twoDecimalPlacesFormat(this.lowestMinByValidPrice)
            this.$v.orderForm.stake.$touch()
            this.clearOrderMessage()
        },
        setMaxStake() {
            this.orderForm.stake = twoDecimalPlacesFormat(this.highestMaxByValidPrice)
            this.$v.orderForm.stake.$touch()
            this.clearOrderMessage()
        },
        getMarketDetails(setMinMaxProviders = true, updatedPoints = true, changedPoints = false) {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/${this.market_id}`, { headers: { 'Authorization': `Bearer ${token}` }})
                .then(response => {
                    if(changedPoints) {
                        this.market_details.providers = response.data.data.providers
                    } else {
                        this.market_details = response.data.data
                    }
                    this.formattedRefSchedule = response.data.data.ref_schedule.split(' ')
                    this.loadingMessage = 'Loading Market Details...'
                    if(updatedPoints) {
                        if (setMinMaxProviders) {
                            this.setMinMaxProviders()
                        } else {
                            this.isLoadingMarketDetailsAndProviders = false;
                            this.minmax(this.market_id)
                        }
                    }
                    this.$store.commit('trade/SHOW_BET_MATRIX_IN_BETSLIP', { market_id: this.market_id, has_bet: response.data.data.has_bets })
                })
                .catch(err => {
                    this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
                })
        },
        openBetMatrix(data) {
            this.showBetMatrix = true
            this.$store.dispatch('trade/setActivePopup', data)
        },
        openOddsHistory(data) {
            this.showOddsHistory = true
            this.$store.dispatch('trade/setActivePopup', data)
        },
        async setMinMaxProviders() {
            await this.$store.dispatch('trade/getBookies')
            let settingsConfig = await this.$store.dispatch('settings/getUserSettingsConfig', 'bookies')
            this.disabledBookies = settingsConfig.disabled_bookies
            let enabledBookies = this.bookies.filter(bookie => !this.disabledBookies.includes(bookie.id))
            enabledBookies.map(bookie => this.minMaxProviders.push({ provider_id: bookie.id, provider: bookie.alias, min: null, max: null, price: null, points: null, age: null, hasMarketData: false, noMarketAvailable: false }))
            this.isLoadingMarketDetailsAndProviders = false
            this.minmax(this.market_id)
        },
        changePoint(points, market_id, odds) {
            this.emptyMinMax(this.market_id)
            this.market_id = market_id
            this.inputPrice = twoDecimalPlacesFormat(Number(odds))
            this.minmax(market_id)
            this.showBetMatrix = false
            this.minMaxUpdateCounter = 0
            this.clearOrderMessage()
            this.getMarketDetails(false, false, true)
            this.initialSpread()
            this.points = points
            this.orderForm.stake = ''
            this.$v.$reset()
            this.toggledProviders = false
            this.selectAllProviders = false
        },
        sendMinMax(market_id) {
            return new Promise((resolve) => {
                this.$socket.send(`getMinMax_${market_id}`)
                resolve()
            })
        },
        removeMinMax(market_id) {
            return new Promise((resolve) => {
                this.$socket.send(`removeMinMax_${market_id}`)
                resolve()
            })
        },
        updateMinMaxData(minmax, hasMarketData, noMarketAvailable) {
            if(minmax.market_id == this.market_id && !_.isEmpty(this.minMaxProviders)) {
                let minMaxProviderIds = this.minMaxProviders.map(provider => provider.provider_id)
                if(minMaxProviderIds.includes(minmax.provider_id)) {
                    this.minMaxProviders.map(provider => {
                        if(provider.provider_id == minmax.provider_id) {
                            provider.hasMarketData = hasMarketData
                            provider.noMarketAvailable = noMarketAvailable
                            if(provider.hasMarketData) {
                                provider.min = Number(twoDecimalPlacesFormat(minmax.min)) || null
                                provider.max = Number(twoDecimalPlacesFormat(minmax.max)) || null
                                provider.price = Number(twoDecimalPlacesFormat(minmax.price)) || null
                                provider.points = Number(twoDecimalPlacesFormat(minmax.points)) || null
                                provider.age = minmax.age || null
                            } else {
                                this.minMaxData = this.minMaxData.filter(provider => provider.provider_id != minmax.provider_id)
                                this.selectedProviders = this.selectedProviders.filter(provider => provider != minmax.provider_id)
                                provider.min = null
                                provider.max = null
                                provider.price = null
                                provider.points =  null
                                provider.age = null
                            }
                        }
                    })
                }
            }
        },
        getMinMaxData() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getMinMax') {
                    let minmax = getSocketValue(response.data, 'getMinMax')
                    if(minmax.message == '') {
                        if(this.market_details.odd_type.includes('1X2') || this.market_details.odd_type.includes('OE')) {
                            this.updateMinMaxData(minmax, true, false)
                            this.$store.dispatch('trade/updateOdds', { market_id: minmax.market_id, odds: minmax.price })
                        } else {
                            if(this.points == minmax.points) {
                                this.updateMinMaxData(minmax, true, false)
                                this.$store.dispatch('trade/updateOdds', { market_id: minmax.market_id, odds: minmax.price })
                            } else {
                                this.updateMinMaxData(minmax, false, true)

                                let spreadPoints = this.market_details.spreads.length != 0 ? this.market_details.spreads.map(spread => spread.points) : []
                                if(!spreadPoints.includes(minmax.points) && minmax.points && minmax.market_id == this.market_id) {
                                    this.market_details.spreads.push({
                                        market_id: minmax.market_id,
                                        odds: minmax.price,
                                        points: minmax.points,
                                        provider_id: minmax.provider_id
                                    })
                                }
                            }
                        }
                    } else {
                        this.updateMinMaxData(minmax, false, true)
                    }
                    this.retrievedMarketData = true
                }
            })
        },
        getRemoveMinMax() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'removeMinMax') {
                    let removeMinMax = getSocketValue(response.data, 'removeMinMax')
                    if(removeMinMax.status) {
                        this.minMaxProviders.map(provider => {
                            provider.min = null
                            provider.max = null
                            provider.price = null
                            provider.age = null
                            provider.hasMarketData = false
                            provider.noMarketAvailable = false
                        })
                        this.retrievedMarketData = false
                        this.minMaxData = []
                    }
                }
            })
        },
        emptyMinMax(market_id) {
            this.removeMinMax(market_id)
                .then(() => {
                    this.getRemoveMinMax()
                })
        },
        minmax(market_id) {
            this.sendMinMax(market_id)
                .then(() => {
                    this.getMinMaxData()
                })
        },
        closeBetSlip(betslip_id) {
            this.$store.commit('trade/CLOSE_BETSLIP', betslip_id)
            this.emptyMinMax(this.market_id)
        },
        closeOddsHistory() {
            this.showOddsHistory = false
        },
        closeBetMatrix() {
            this.showBetMatrix = false
        },
        closeAwaitingPlacement() {
            this.showAwaitingPlacement = false
        },
        clearOrderMessage() {
            this.orderMessage = ''
            this.isDoneBetting = false
        },
        availableMarketsTooLow() {
            this.orderMessage = 'Available markets are too low.'
            this.isDoneBetting = true
            this.isBetSuccessful = false
            this.orderForm.stake = null
        },
        priceInput() {
            if(!this.$v.inputPrice.$invalid) {
                if(this.qualifiedProviders.length != 0) {
                    this.clearOrderMessage()
                    this.$v.orderForm.stake.$reset()
                    this.orderForm.stake = this.highestMaxByValidPrice
                } else {
                    this.availableMarketsTooLow()
                    this.$v.orderForm.stake.$touch()
                }
            } else {
                this.clearOrderMessage()
                this.orderForm.stake = ''
                this.$v.orderForm.stake.$touch()
            }
        },
        stakeInput() {
            if(Number(this.orderForm.stake) > twoDecimalPlacesFormat(this.highestMaxByValidPrice)) {
                this.orderMessage = 'Cannot input more than the combined max stakes of selected bookmakers!'
                this.isDoneBetting = true
                this.isBetSuccessful = false
                this.orderForm.stake = this.highestMaxByValidPrice
            } else {
                this.clearOrderMessage()
            }
        },
        updatePrice(price) {
            this.inputPrice = twoDecimalPlacesFormat(price)
            this.$v.$touch()
            this.orderForm.stake = this.highestMaxByValidPrice
            this.clearOrderMessage()
        },
        toggleMinmaxProviders(minmax, provider_id) {
            this.toggledProviders = true
            this.$v.$reset()
            if(this.selectedProviders.includes(provider_id)) {
                this.selectedProviders = this.selectedProviders.filter(provider => provider != provider_id)
                this.minMaxData = this.minMaxData.filter(minmax => minmax.provider_id != provider_id)
            } else {
                this.selectedProviders.push(provider_id)
                this.minMaxData.push(minmax)
            }

            if(this.minMaxData.length != 0) {
                this.inputPrice = this.lowestPrice
                this.orderForm.stake = this.highestMax
            } else {
                this.inputPrice = null
                this.orderForm.stake = null
            }

            let minMaxProviders = this.minMaxProviders.filter(minmax => minmax.price != null && !this.underMaintenanceProviders.includes(minmax.provider.toLowerCase()))
            if(minMaxProviders.length == this.minMaxData.length) {
                this.selectAllProviders = true
            } else {
                this.selectAllProviders = false
            }
            this.clearOrderMessage()
        },
        toggleAllProviders() {
            this.toggledProviders = true
            this.$v.$reset()

            let minMaxProviders = this.minMaxProviders.filter(minmax => minmax.price != null && !this.underMaintenanceProviders.includes(minmax.provider.toLowerCase()))
            if(this.selectAllProviders) {
                this.minMaxData = minMaxProviders
            } else {
                let bestPrice = Math.max(...minMaxProviders.map(minmax => minmax.price))
                let providersWithBestPrice = this.minMaxProviders.filter(minmax => minmax.price == bestPrice && !this.underMaintenanceProviders.includes(minmax.provider.toLowerCase()))
                if(providersWithBestPrice.length == 1) {
                    this.minMaxData = providersWithBestPrice
                } else {
                    let primaryProvider = this.bookies.filter(provider => provider.is_primary)[0]
                    this.minMaxData = this.minMaxProviders.filter(minmax => minmax.provider_id == primaryProvider.id && !this.underMaintenanceProviders.includes(minmax.provider.toLowerCase()))
                }
            }
            this.selectedProviders = this.minMaxData.map(minmax => minmax.provider_id)
            this.inputPrice = this.lowestPrice
            this.orderForm.stake = this.highestMax
        },
        bet() {
            if(this.pendingBet && this.betSlipSettings.awaiting_placement_msg == '1') {
                this.showAwaitingPlacement = true
            } else {
                this.placeOrder()
            }
        },
        placeOrder() {
            this.isDoneBetting = true
            this.showAwaitingPlacement = false
            if(this.$v.$invalid) {
                this.isBetSuccessful = false
                this.hasErrorOnInput = true
            } else if(this.qualifiedProviders.length == 0) {
                this.orderMessage = 'Available markets are too low.'
                this.isBetSuccessful = false
                this.hasErrorOnInput = false
            } else if(Number(this.orderForm.stake) > twoDecimalPlacesFormat(this.highestMax)) {
                this.orderMessage = 'Cannot input more than the combined max stakes of selected bookmakers!'
                this.isBetSuccessful = false
            } else {
                this.isPlacingOrder = true
                this.hasErrorOnInput = false
                this.$v.orderForm.stake.$touch()
                this.isLoadingMarketDetailsAndProviders = true
                this.loadingMessage = 'Placing bet, please check the recent orders'
                let data = {
                    betType: this.orderForm.betType,
                    stake: this.orderForm.stake,
                    price: this.inputPrice,
                    market_id: this.market_id,
                    points: this.points
                }

                if(this.orderForm.betType == 'FAST_BET') {
                    this.orderForm.markets = []
                    let sortedByPriceArray = this.qualifiedProviders.sort((a, b) => (a.price < b.price) ? 1 : -1)
                    let placedStake = this.orderForm.stake
                    sortedByPriceArray.map(sortedByPrice => {
                        if(placedStake > sortedByPrice.max) {
                            if(this.wallet.credit >= sortedByPrice.max) {
                                placedStake = Number(twoDecimalPlacesFormat(placedStake - sortedByPrice.max))
                                this.orderForm.markets.push(sortedByPrice)
                                this.orderMessage = ''
                            } else {
                                this.orderMessage = 'Insufficient wallet balance.'
                                this.isBetSuccessful = false
                            }
                        } else if(placedStake <= sortedByPrice.max && placedStake >= sortedByPrice.min) {
                            if(this.wallet.credit >= placedStake) {
                                placedStake = 0
                                this.orderForm.markets.push(sortedByPrice)
                                this.orderMessage = ''
                            } else {
                                this.orderMessage = 'Insufficient wallet balance.'
                                this.isBetSuccessful = false
                            }
                        } else if(placedStake < sortedByPrice.min && placedStake != 0) {
                            this.orderForm.stake = placedStake
                            this.orderMessage = 'Stake is lower than minimum stake or cannot proceed to next provider.'
                            this.isBetSuccessful = false
                        }
                    })
                } else if(this.orderForm.betType == 'BEST_PRICE') {
                    let greaterThanOrEqualThanPriceArray = []
                    this.minMaxData.map(minmax => {
                        if(minmax.price >= this.price) {
                            greaterThanOrEqualThanPriceArray.push(minmax.price)
                        }
                    })
                    let bestPricesArray = this.minMaxData.filter(minmax => minmax.price == Math.max(...greaterThanOrEqualThanPriceArray))
                    bestPricesArray.map(bestPrice => {
                        if(this.orderForm.stake > bestPrice.max) {
                            if(this.wallet.credit >= bestPrice.max) {
                                this.orderForm.stake = twoDecimalPlacesFormat(this.orderForm.stake - bestPrice.max)
                                this.orderForm.markets = bestPricesArray
                                this.orderMessage = ''
                            } else {
                                this.orderMessage = 'Insufficient wallet balance.'
                                this.isBetSuccessful = false
                            }
                        } else if(this.orderForm.stake <= bestPrice.max && this.orderForm.stake >= bestPrice.min) {
                            if(this.wallet.credit >= this.orderForm.stake) {
                                this.orderForm.stake = 0
                                this.orderForm.markets = bestPricesArray
                                this.orderMessage = ''
                            } else {
                                this.orderMessage = 'Insufficient wallet balance.'
                                this.isBetSuccessful = false
                            }
                        } else if(this.orderForm.stake < bestPrice.min && this.orderForm.stake != 0) {
                            this.orderMessage = 'Stake lower than minimum stake.'
                            this.isBetSuccessful = false
                        }
                    })
                }
                this.$set(data, 'markets', this.orderForm.markets)
                let token = Cookies.get('mltoken')
                if(this.orderForm.markets.length != 0) {
                    axios.post('v1/orders/bet', data, { headers: { 'Authorization': `Bearer ${token}` }})
                        .then(response => {
                            this.reloadSpread(true)
                            this.isBetSuccessful = true
                            this.betStatus = response.data.status_code
                            this.orderMessage = response.data.data
                            this.$store.dispatch('trade/getBetbarData', this.market_id)
                            this.$store.commit('trade/TOGGLE_BETBAR', true)
                            this.$store.dispatch('trade/getWalletData')

                            if(this.betSlipSettings.bets_to_fav == 1 && this.isBetSuccessful) {
                                this.$store.dispatch('trade/addToWatchlist', { type: 'event', data: this.odd_details.game.uid, payload: this.odd_details.game })
                            }
                            this.isPlacingOrder = false

                            setTimeout(() => {
                                if(!this.receivedOrderStatusIds.includes(response.data.order_id)) {
                                    this.$store.dispatch('trade/getBetbarData', this.market_id)
                                }
                            }, 15000)
                        })
                        .catch(err => {
                            this.isBetSuccessful = false
                            this.betStatus = err.response.status
                            this.isLoadingMarketDetailsAndProviders = false
                            this.loadingMessage = 'Loading Market Details...'
                            this.isPlacingOrder = false
                            if(this.orderMessage == '') {
                                this.orderMessage = err.response.data.message
                            }
                            this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
                        })
                } else {
                    this.isLoadingMarketDetailsAndProviders = false
                    this.loadingMessage = 'Loading Market Details...'
                    this.isPlacingOrder = false
                }
            }
        }
    },
    directives: {
        betslip: {
            bind(el, binding, vnode) {
                let { $set, options, popupZIndex } = vnode.context
                $set(options, 'top', window.innerHeight / 2)
                $set(options, 'left', window.innerWidth / 2)
                el.style.zIndex = popupZIndex
            },
            componentUpdated(el, binding, vnode)  {
                if(binding.value) {
                    el.style.zIndex = vnode.context.popupZIndex
                }
                el.style.marginTop = 'calc(556px / 2 * -1)'
                el.style.marginLeft = `calc(${el.offsetWidth}px / 2 * -1)`
            }
        }
    },
    filters: {
        twoDecimalPlacesFormat,
        moneyFormat,
        formatAverage
    }
}
</script>

<style lang="scss">
.betslip {
  input:not([disabled]), select {
    background-color: #ffffff !important;
    border-style: solid !important;
  }
  select {
    appearance: none !important;
  }
  input:disabled {
    background: transparent !important;
  }
}
.leagueAndTeamDetails {
    font-size: 15px;
}

.loader {
    height: 510px;
}

.betSlipSpinner {
    font-size: 120px;
}

.success {
    background-color: #5cb85c;
}

.failed {
    background-color: #d9534f;
}

.warning {
    color: #664d03 !important;
    background-color: #fff3cd;
    border: solid #ffecb5 1px;
}

.continue {
    color: #084298 !important;
    background-color: #cfe2ff;
    border: solid #b6d4fe 1px;
}

.orderMessage {
    font-size: 14px;
}

.clearOrderMessage {
    right: 5px;
}

.previousPoint {
    left: -30px;
}

.nextPoint {
    right: -30px;
}

.spread-refresh {
    position: absolute;
    text-align: right;
    margin: 5px 0;
    width: 57%;
    color: #FFF;
}

.spread-refresh a {
    padding: 0 5px;
    background-color: #ce6a17;
    font-size: 20px;
}

.minMaxBtn {
    padding: 0.1rem 0.5rem;
}

.minMaxBtn.minStake {
    right: 125px;
}

.selectAllLabel {
    left: 19px;
}

.selectAll, .providerCheckbox {
    left: 24px;
}
</style>
