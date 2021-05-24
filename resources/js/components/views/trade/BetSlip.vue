<template>
    <div class="betslip absolute flex justify-center items-center">
        <dialog-drag :title="'Bet Slip - '+market_id" :options="options" @close="closeBetSlip($vnode.key)" @mousedown.native="$store.dispatch('trade/setActivePopup', $vnode.key)" v-betslip="activePopup==$vnode.key">
            <div class="flex flex-col justify-center items-center w-full h-full absolute top-0 left-0 bg-gray-200 z-10" :class="{'hidden': !isLoadingMarketDetailsAndProviders}">
                <span class="betSlipSpinner"><i class="fas fa-circle-notch fa-spin"></i></span>
                <span class="text-center mt-2">Loading Market Details...</span>
            </div>
            <div class="container mx-auto p-2">
                <div class="flex justify-between items-center w-full leagueAndTeamDetails">
                    <div class="flex items-center w-3/4">
                        <span class="text-white uppercase font-bold mr-2 my-2 px-2 bg-orange-500">{{market_details.odd_type}}</span>
                        <span class="text-gray-800 font-bold my-2 pr-6">{{market_details.league_name}}</span>
                        <a href="#" @click.prevent="openBetMatrix(`${odd_details.betslip_id}-betmatrix`)" class="text-center py-1 pr-1" title="Bet Matrix" v-if="oddTypesWithSpreads.includes(market_details.odd_type) && odd_details.has_bet"><i class="fas fa-chart-area"></i></a>
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
                                <a href="#" class="m-1 w-16 text-center text-sm" :class="[spread.market_id == market_id ? 'text-white bg-orange-500 px-1 py-1' : 'text-gray-800']" v-for="(spread, index) in spreads" :key="index" @click="changePoint(spread.points, spread.market_id, spread.odds)">{{spread.points}}</a>
                            </div>
                        </div>
                        <div class="flex flex-col bg-white shadow-xl">
                            <div class="flex justify-between items-center py-2 bg-orange-500 text-white">
                                <span class="w-1/5"></span>
                                <span class="w-1/5 text-sm font-bold text-center">Min</span>
                                <span class="w-1/5 text-sm font-bold text-center">Max</span>
                                <span class="w-1/5 text-sm font-bold text-center">Price</span>
                                <span class="w-1/5"></span>
                            </div>
                            <div class="flex items-center py-2" v-for="minmax in minMaxProviders" :key="minmax.provider_id">
                                <span class="w-1/5 text-sm font-bold text-center pl-3">
                                    <label class="text-gray-500 font-bold">
                                        <input class="mr-2 leading-tight" type="checkbox" @change="toggleMinmaxProviders(minmax, minmax.provider_id)" :checked="selectedProviders.includes(minmax.provider_id) && minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())" :disabled="!minmax.hasMarketData || underMaintenanceProviders.includes(minmax.provider.toLowerCase())">
                                    </label>
                                    {{minmax.provider}}
                                </span>
                                <span class="w-1/5 text-sm text-center" v-if="minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">{{minmax.min | moneyFormat}}</span>
                                <span class="w-1/5 text-sm text-center" v-if="minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">{{minmax.max | moneyFormat}}</span>
                                <a href="#" @click.prevent="updatePrice(minmax.price)" class="w-1/5 text-sm font-bold underline text-center" v-if="minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">{{minmax.price | twoDecimalPlacesFormat}}</a>
                                <span class="w-1/5 text-sm text-center" v-if="minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">{{minmax.age}}</span>
                                <div class="text-sm text-center" v-if="!minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">
                                    <div v-show="market_details.providers.includes(minmax.provider_id) && !isEventNotAvailable && odd_details.odd.market_id">Retrieving Market<span class="pl-1"><i class="fas fa-circle-notch fa-spin"></i></span></div>
                                    <div v-show="!market_details.providers.includes(minmax.provider_id) || isEventNotAvailable || !odd_details.odd.market_id">
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
                                <span class="text-sm">Min Stake</span>
                                <span class="text-sm">{{lowestMin | moneyFormat}}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm">Max Stake</span>
                                <span class="text-sm">{{highestMax | moneyFormat}}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm">Average Price</span>
                                <span class="text-sm">{{displayedAveragePrice | twoDecimalPlacesFormat}}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm">To Win</span>
                                <span class="text-sm">{{towin | moneyFormat}}</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm">{{market_details.odd_type}}</span>
                            <span class="text-sm">{{points}}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <label class="text-sm">Stake</label>
                            <input type="text" style="padding-right: 4rem;" class="w-40 shadow appearance-none border rounded text-sm py-1 pl-3 pr-16 text-gray-700 leading-tight focus:outline-none" v-model="$v.orderForm.stake.$model" @keyup="clearOrderMessage">
                            <button class="absolute bg-primary-500 right-0 mr-5 px-3 text-white rounded text-xs uppercase focus:outline-none hover:bg-primary-600" style="padding: 0.1rem 0.5rem;padding" @click="sumOfMaxStake"><i class="fa fa-angle-double-up" aria-hidden="true"></i>&nbsp;&nbsp;&nbsp;MAX</button>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <label class="text-sm">Price</label>
                            <input class="w-40 shadow appearance-none border rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="text" v-model="$v.inputPrice.$model" @keyup="clearOrderMessage">
                        </div>
                        <div class="flex justify-between items-center py-2" :class="{'hidden': betSlipSettings.adv_placement_opt == 0, 'block': betSlipSettings.adv_placement_opt == 1}">
                            <label class="text-sm">Order Expiry</label>
                            <div class="relative w-40">
                                <select class="shadow appearance-none border rounded text-sm w-full py-1 px-3 text-gray-700 leading-tight focus:outline-none" v-model="orderForm.orderExpiry" @change="clearOrderMessage">
                                    <option value="30">30 secs</option>
                                    <option value="120">2 mins</option>
                                    <option value="300">5 mins</option>
                                    <option value="600">10 mins</option>
                                    <option value="1800">30 mins</option>
                                    <option value="3600">1 hour</option>
                                    <option value="7200">2 hours</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
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
                        <div v-if="!isPlacingOrder && isDoneBetting" class="orderMessage relative flex justify-center items-center text-white py-1 px-2 mt-2 w-full rounded" :class="{'failed': !isBetSuccessful, 'success': isBetSuccessful}">
                            <span class="text-xs mr-1" v-show="!isBetSuccessful"><i class="fas fa-exclamation-triangle"></i></span>
                            <span class="text-xs mr-1" v-show="isBetSuccessful"><i class="fas fa-check"></i></span>
                            <span class="px-2" v-if="(!$v.orderForm.stake.required || !$v.inputPrice.required) && !isBetSuccessful && hasErrorOnInput">Please input stake and price.</span>
                            <span class="px-2" v-else-if="(!$v.orderForm.stake.decimal || !$v.inputPrice.decimal)  && !isBetSuccessful && hasErrorOnInput">Stake and price should have a numeric value.</span>
                            <span class="px-2" v-else-if="(!$v.orderForm.stake.minValue || !$v.inputPrice.minValue)  && !isBetSuccessful && hasErrorOnInput">Input a valid stake and price. Stake Min: 1, Price Min: 0</span>
                            <span class="px-2" v-else v-html="orderMessage"></span>
                            <span class="absolute clearOrderMessage float-right cursor-pointer text-xs" @click="isDoneBetting = false"><i class="fas fa-times-circle"></i></span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center w-full" v-if="market_details.user_status == 1">
                    <button v-if="isPlacingOrder" @click="placeOrder" class="bg-orange-500 text-white rounded-lg w-full text-sm uppercase p-2 mt-2 opacity-75" disabled>Placing Order... <span class="text-sm"><i class="fas fa-circle-notch fa-spin"></i></span></button>
                    <button v-if="!isPlacingOrder" @click="placeOrder" class="bg-orange-500 text-white rounded-lg w-full text-sm uppercase p-2 mt-2 focus:outline-none" :class="[!retrievedMarketData || minMaxData.length == 0 ? 'opacity-75' : 'hover:bg-orange-600']" :disabled="!retrievedMarketData || minMaxData.length == 0">Place Order</button>
                </div>
            </div>
        </dialog-drag>
        <odds-history v-if="showOddsHistory" @close="closeOddsHistory" :market_id="market_id" :event_id="odd_details.game.uid" :key="`${odd_details.betslip_id}-orderlogs`"></odds-history>
        <bet-matrix v-if="showBetMatrix" @close="closeBetMatrix" :market_id="market_id" :analysis-data="analysisData" :event_id="odd_details.game.uid" :key="`${odd_details.betslip_id}-betmatrix`"></bet-matrix>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import _ from 'lodash'
import OddsHistory from './OddsHistory'
import BetMatrix from './BetMatrix'
import DialogDrag from 'vue-dialog-drag'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'
import { twoDecimalPlacesFormat, moneyFormat } from '../../../helpers/numberFormat'
import { moveToFirstElement } from '../../../helpers/array'
import { required, decimal, minValue } from 'vuelidate/lib/validators'

export default {
    props: ['odd_details'],
    components: {
        DialogDrag,
        OddsHistory,
        BetMatrix
    },
    data() {
        return {
            market_details: {},
            formattedRefSchedule: [],
            inputPrice: null || twoDecimalPlacesFormat(this.odd_details.odd.odds),
            points: null || this.odd_details.odd.points,
            market_id: this.odd_details.odd.market_id,
            orderForm: {
                stake: '',
                orderExpiry: 30,
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
            disabledBookies: [],
            retrievedMarketData: false,
            startPointIndex: 0,
            endPointIndex: 5,
            isEventNotAvailable: null,
            minMaxUpdateCounter: 0,
            hasNewOddsInTradeWindow: false
        }
    },
    validations: {
        inputPrice: { required, minValue: minValue(0), decimal },
        orderForm: {
            stake:  { required, minValue: minValue(1), decimal }
        }
    },
    computed: {
        ...mapState('trade', ['activePopup', 'popupZIndex', 'bookies', 'betSlipSettings', 'wallet', 'underMaintenanceProviders']),
        ...mapState('settings', ['defaultPriceFormat']),
        lowestMin() {
            if(!_.isEmpty(this.minMaxData)) {
                let minValues = this.minMaxData.filter(minmax => minmax.min != null).map(minmax => minmax.min)
                if(!_.isEmpty(minValues)) {
                    return Math.max(...minValues)
                } else {
                    return 0
                }
            } else {
                return 0
            }
        },
        highestMax() {
            if(!_.isEmpty(this.minMaxData)) {
                let maxValues = this.minMaxData.filter(minmax => minmax.max != null).map(minmax => minmax.max)
                if(!_.isEmpty(maxValues)) {
                    return maxValues.reduce((firstMax, secondMax) => firstMax + secondMax, 0)
                } else {
                    return 0
                }
            } else {
                return 0
            }
        },
        displayedAveragePrice() {
            if(!_.isEmpty(this.minMaxData)) {
                let prices = this.minMaxData.filter(minmax => minmax.price != null).map(minmax => minmax.price)
                let sumOfPrices = prices.reduce((firstPrice, secondPrice) => firstPrice + secondPrice, 0)
                if(!_.isEmpty(prices)) {
                    return sumOfPrices / prices.length
                } else {
                    return 0
                }
            } else {
                return 0
            }
        },
        initialPrice() {
            return Number(this.inputPrice)
        },
        towin() {
            if(!this.$v.$invalid) {
                if (this.market_details.odd_type == '1X2' || this.market_details.odd_type == 'HT 1X2' || this.market_details.odd_type == 'OE') {
                    return this.orderForm.stake * (this.initialPrice - 1)
                } else {
                    return this.orderForm.stake * this.initialPrice
                }
            } else {
                return 0
            }
        },
        numberOfQualifiedProviders() {
            if(!_.isEmpty(this.minMaxData)) {
                let prices = this.minMaxData.map(minmax => minmax.price)
                let qualifiedPrices = []
                prices.map(price => {
                    if(Number(twoDecimalPlacesFormat(price)) >= this.initialPrice) {
                        qualifiedPrices.push(price)
                    }
                })
                return qualifiedPrices.length
            } else {
                return 0
            }
        },
        analysisData() {
            return {
                home_score: this.market_details.score.split(' - ')[0],
                away_score: this.market_details.score.split(' - ')[1]
            }
        },
        tradeWindowOdds() {
            return this.getTradeWindowData('odds')
        },
        tradeWindowPoints() {
            return this.getTradeWindowData('points') || null
        },
        spreads() {
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
                            if(spread.market_id == point.market_id) {
                                this.$set(spread, 'points', point.points)
                                this.$set(spread, 'odds', point.odds)
                            }
                            if(spread.points == point.points) {
                                this.$set(spread, 'market_id', point.market_id)
                            }
                        })
                    })

                    if(this.odd_details.game.market_odds.hasOwnProperty('other')) {
                        return moveToFirstElement(points, 'market_id', 'points', this.odd_details.odd.market_id)
                    } else {
                        return moveToFirstElement(this.market_details.spreads, 'market_id', 'points', this.odd_details.odd.market_id)
                    }
                } else {
                    return points
                }
            }
        },
        hasValidPriceInput() {
            let minPrice = Math.min(...this.minMaxData.filter(minmax => minmax.price != null).map(minmax => minmax.price))
            let maxPrice = Math.max(...this.minMaxData.filter(minmax => minmax.price != null).map(minmax => minmax.price))
            if(this.initialPrice >= (minPrice - 0.10) && this.initialPrice <= (maxPrice + 0.10)) {
                return true
            }
            return false
        }
    },
    watch: {
        minMaxProviders: {
            deep: true,
            handler(value) {
                this.minMaxUpdateCounter = this.minMaxUpdateCounter + 1
                if(this.minMaxUpdateCounter <= (this.market_details.providers.length + 1)) {
                    let minMaxPrices = value.filter(minmax => minmax.price != null && !this.underMaintenanceProviders.includes(minmax.provider.toLowerCase())).map(minmax => minmax.price)
                    if(minMaxPrices.length > 0) {
                        this.inputPrice = twoDecimalPlacesFormat(Math.max(...minMaxPrices))
                        this.minMaxData = value.filter(minmax => minmax.price == Math.max(...minMaxPrices) && !this.underMaintenanceProviders.includes(minmax.provider.toLowerCase()))
                        this.selectedProviders = this.minMaxData.filter(minmax => minmax.price != null).map(minmax => minmax.provider_id)
                    }
                }

                if(!_.isEmpty(this.minMaxData)) {
                    let selectedMinmaxDataPrices = this.minMaxData.filter(minmax => minmax.price != null).map(minmax => minmax.price)
                    this.inputPrice = twoDecimalPlacesFormat(Math.min(...selectedMinmaxDataPrices))
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
        }
    },
    mounted() {
        this.getMarketDetails()
        this.$store.dispatch('trade/getBetSlipSettings')
    },
    methods: {
        getTradeWindowData(key) {
            if(!_.isEmpty(this.market_details)) {
                let odd_type = this.market_details.odd_type
                let market_flag = this.market_details.market_flag
                let market_type = this.odd_details.marketType
                let event_identifier = this.odd_details.eventIdentifier
                if(this.odd_details.game.hasOwnProperty('market_odds')) {
                    if(market_type == 'main') {
                        if(this.odd_details.game.market_odds.main.hasOwnProperty(odd_type) && this.odd_details.game.market_odds.main[odd_type].hasOwnProperty(market_flag) && this.odd_details.game.market_odds.main[odd_type][market_flag].hasOwnProperty(key)) {
                            return this.odd_details.game.market_odds.main[odd_type][market_flag][key]
                        }
                    } else {
                        if(this.odd_details.game.market_odds.hasOwnProperty('other') && this.odd_details.game.market_odds.other.hasOwnProperty(event_identifier) && this.odd_details.game.market_odds.other[event_identifier].hasOwnProperty(odd_type) && this.odd_details.game.market_odds.other[event_identifier][odd_type].hasOwnProperty(market_flag) && this.odd_details.game.market_odds.other[event_identifier][odd_type][market_flag].hasOwnProperty(key)) {
                            return this.odd_details.game.market_odds.other[event_identifier][odd_type][market_flag][key]
                        }
                    }
                }
            } else {
                return this.odd_details.odd[key]
            }
        },
        reloadSpread() {
            this.isLoadingMarketDetailsAndProviders = true
            this.isEventNotAvailable = false
            this.hasNewOddsInTradeWindow = false
            this.minMaxUpdateCounter = 0
            this.clearOrderMessage();
            this.getMarketDetails(false)
        },
        sumOfMaxStake() {
            let maxs = this.minMaxData.map(minmax => minmax.max)
            let maxAmount = 0;
            maxs.map(max => {
                maxAmount += max
            })
            this.orderForm.stake = maxAmount
        },
        getMarketDetails(setMinMaxProviders = true, updatedPoints = true) {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/${this.market_id}`, { headers: { 'Authorization': `Bearer ${token}` }})
                .then(response => {
                    this.market_details = response.data.data
                    this.formattedRefSchedule = response.data.data.ref_schedule.split(' ')
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
            enabledBookies.map(bookie => this.minMaxProviders.push({ provider_id: bookie.id, provider: bookie.alias, min: null, max: null, price: null, age: null, hasMarketData: false }))
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
            this.isEventNotAvailable = false
            this.clearOrderMessage()
            this.getMarketDetails(false, false)
            this.points = points
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
        updateMinMaxData(minmax, hasMarketData) {
            if(minmax.market_id == this.market_id) {
                if(!_.isEmpty(this.minMaxProviders)) {
                    let minMaxProviderIds = this.minMaxProviders.map(provider => provider.provider_id)
                    if(minMaxProviderIds.includes(minmax.provider_id)) {
                        this.minMaxProviders.map(provider => {
                            if(provider.provider_id == minmax.provider_id) {
                                provider.min = Number(twoDecimalPlacesFormat(minmax.min)) || null
                                provider.max = Number(twoDecimalPlacesFormat(minmax.max)) || null
                                provider.price = Number(twoDecimalPlacesFormat(minmax.price)) || null
                                provider.age = minmax.age || null
                                provider.hasMarketData = hasMarketData
                            }
                        })

                        let selectedMinMaxPrices = this.minMaxData.filter(minmax => minmax.price != null).map(minmax => minmax.price)
                        if(selectedMinMaxPrices.length != 0) {
                            if(this.minMaxData.length > 1) {
                                this.inputPrice = twoDecimalPlacesFormat(Math.min(...selectedMinMaxPrices))
                            } else {
                                this.inputPrice = twoDecimalPlacesFormat(Math.max(...selectedMinMaxPrices))
                            }
                        }
                    }
                }
            }
        },
        getMinMaxData() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getMinMax') {
                    let minmax = getSocketValue(response.data, 'getMinMax')
                    if(minmax.message == '') {
                        this.updateMinMaxData(minmax, true)
                        this.isEventNotAvailable = false
                        this.$store.dispatch('trade/updateOdds', { market_id: minmax.market_id, odds: minmax.price })
                    } else {
                        this.minMaxData = this.minMaxData.filter(provider => provider.provider_id != minmax.provider_id)
                        this.selectedProviders = this.selectedProviders.filter(provider => provider != minmax.provider_id)
                        this.isEventNotAvailable = true
                        this.updateMinMaxData(minmax, false)
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
        clearOrderMessage() {
            this.orderMessage = ''
            this.isDoneBetting = false
        },
        updatePrice(price) {
            this.inputPrice = twoDecimalPlacesFormat(price)
            this.clearOrderMessage()
        },
        toggleMinmaxProviders(minmax, provider_id) {
            this.clearOrderMessage()
            if(this.selectedProviders.includes(provider_id)) {
                this.selectedProviders = this.selectedProviders.filter(provider => provider != provider_id)
                this.minMaxData = this.minMaxData.filter(minmax => minmax.provider_id != provider_id)
            } else {
                this.selectedProviders.push(provider_id)
                this.minMaxData.push(minmax)
            }

            if(this.minMaxData.length != 0) {
                let minmaxPrices = this.minMaxData.filter(minmax => minmax.price != null).map(minmax => minmax.price)
                this.inputPrice = twoDecimalPlacesFormat(Math.min(...minmaxPrices))
            } else {
                this.inputPrice = null
            }
        },
        placeOrder() {
            this.isDoneBetting = true
            if(this.$v.$invalid) {
                this.isBetSuccessful = false
                this.hasErrorOnInput = true
            } else if(this.numberOfQualifiedProviders == 0) {
                this.orderMessage = 'Available markets are too low.'
                this.isBetSuccessful = false
                this.hasErrorOnInput = false
            } else if(!this.hasValidPriceInput) {
                this.orderMessage = 'Invalid price input.'
                this.isBetSuccessful = false
            } else {
                this.isPlacingOrder = true
                this.hasErrorOnInput = false
                let data = {
                    betType: this.orderForm.betType,
                    stake: this.orderForm.stake,
                    orderExpiry: this.orderForm.orderExpiry,
                    market_id: this.market_id,
                    odds: this.inputPrice
                }

                if(this.orderForm.betType == 'FAST_BET') {
                    let greaterThanOrEqualThanPriceArray = []
                    this.orderForm.markets = []
                    this.minMaxData.map(minmax => {
                        if(minmax.price >= this.initialPrice) {
                            greaterThanOrEqualThanPriceArray.push(minmax)
                        }
                    })

                    let sortedByPriceArray = greaterThanOrEqualThanPriceArray.sort((a, b) => (a.price < b.price) ? 1 : -1)

                    if(this.orderForm.stake > this.wallet.credit) {
                        this.orderMessage = 'Insufficient wallet balance.'
                        this.isBetSuccessful = false
                    } else {
                        this.orderForm.stake = 0
                        this.orderForm.markets = sortedByPriceArray
                    }
                } else if(this.orderForm.betType == 'BEST_PRICE') {
                    let greaterThanOrEqualThanPriceArray = []
                    this.minMaxData.map(minmax => {
                        if(minmax.price >= this.initialPrice) {
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
                            this.isBetSuccessful = true
                            this.orderMessage = response.data.data
                            this.$store.dispatch('trade/getBetbarData', this.market_id)
                            this.$store.commit('trade/TOGGLE_BETBAR', true)
                            this.$store.dispatch('trade/getWalletData')

                            if(this.betSlipSettings.bets_to_fav == 1 && this.isBetSuccessful) {
                                this.$store.dispatch('trade/addToWatchlist', { type: 'event', data: this.odd_details.game.uid, payload: this.odd_details.game })
                            }
                            this.isPlacingOrder = false
                        })
                        .catch(err => {
                            this.isBetSuccessful = false
                            this.isPlacingOrder = false
                            if(this.orderMessage == '') {
                                this.orderMessage = err.response.data.message
                            }
                            this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
                        })
                } else {
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
        moneyFormat
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
</style>
