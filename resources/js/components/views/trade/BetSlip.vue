<template>
    <div class="betslip flex justify-center items-center">
        <dialog-drag :title="'Bet Slip - '+market_id" :options="options" @close="closeBetSlip(odd_details.betslip_id)" @click.native="setActiveBetSlip(market_id)" v-betslip="activeBetSlip==market_id">
            <div class="flex flex-col justify-center items-center w-full h-full absolute top-0 left-0 bg-gray-200 z-10" :class="{'hidden': !isLoadingMarketDetailsAndProviders}">
                <span class="betSlipSpinner"><i class="fas fa-circle-notch fa-spin"></i></span>
                <span class="text-center mt-2">Loading Market Details...</span>
            </div>
            <div class="container mx-auto p-2">
                <div class="flex justify-between items-center w-full leagueAndTeamDetails">
                    <div class="flex items-center">
                        <span class="text-white uppercase font-bold mr-2 my-2 px-2 bg-orange-500">{{market_details.odd_type}}</span>
                        <span class="text-gray-800 font-bold my-2 pr-6">{{market_details.league_name}}</span>
                        <a href="#" @click.prevent="showBetMatrix = true" class="text-center py-1 pr-1" title="Bet Matrix" v-if="oddTypesWithSpreads.includes(market_details.odd_type) && odd_details.has_bet"><i class="fas fa-chart-area"></i></a>
                        <a href="#" @click.prevent="showOddsHistory = true" class="text-center py-1" title="Odds History"><i class="fas fa-bars"></i></a>
                    </div>
                    <div class="flex items-center">
                        <a href="#" class="text-center py-1 pr-1 mr-2"><i class="far fa-calendar-alt"></i> {{formattedRefSchedule[0]}}</a>
                        <a href="#" class="text-center py-1"><i class="far fa-clock"></i> {{formattedRefSchedule[1]}}</a>
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
                            <div class="relative flex justify-center items-center p-2">
                                <a href="#" class="previousPoint absolute m-1 w-12 text-center text-gray-800" @click="previousPoint" v-show="points != spreads[0].points && spreads.length > 2"><i class="fas fa-chevron-left"></i></a>
                                <a href="#" class="m-1 w-16 text-center text-sm" :class="[spread.market_id == market_id ? 'text-white bg-orange-500 px-1 py-1' : 'text-gray-800']" v-for="(spread, index) in displayedSpreads" :key="index" @click="changePoint(spread.points, spread.market_id, spread.odds)">{{spread.points}}</a>
                                <a href="#" class="nextPoint absolute m-1 w-12 text-center text-gray-800" @click="nextPoint" v-show="points != spreads[spreads.length - 1].points && spreads.length > 2"><i class="fas fa-chevron-right"></i></a>
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
                                        <input class="mr-2 leading-tight" type="checkbox" @change="toggleMinmaxProviders(minmax, minmax.provider_id)" :checked="selectedProviders.includes(minmax.provider_id) && minmax.hasMarketData" :disabled="!minmax.hasMarketData">
                                    </label>
                                    {{minmax.provider}}
                                </span>
                                <span class="w-1/5 text-sm text-center" v-if="minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">{{minmax.min | moneyFormat}}</span>
                                <span class="w-1/5 text-sm text-center" v-if="minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">{{minmax.max | moneyFormat}}</span>
                                <a href="#" @click.prevent="updatePrice(minmax.price)" class="w-1/5 text-sm font-bold underline text-center" v-if="minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">{{minmax.price | twoDecimalPlacesFormat}}</a>
                                <span class="w-1/5 text-sm text-center" v-if="minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">{{minmax.age}}</span>
                                <div class="text-sm text-center" v-if="!minmax.hasMarketData && !underMaintenanceProviders.includes(minmax.provider.toLowerCase())">
                                    <div v-show="market_details.providers.includes(minmax.provider_id) && !isEventNotAvailable && odd_details.market_id">Retrieving Market<span class="pl-1"><i class="fas fa-circle-notch fa-spin"></i></span></div>
                                    <div v-show="!market_details.providers.includes(minmax.provider_id) || isEventNotAvailable || !odd_details.market_id">No Market Available</div>
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
                                <span class="text-sm">Towin</span>
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
                            <input class="w-40 shadow appearance-none border rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="text" v-model="$v.inputPrice.$model" @keyup="clearOrderMessage" disabled>
                        </div>
                        <div class="flex justify-between items-center py-2" :class="{'hidden': betSlipSettings.adv_placement_opt == 0, 'block': betSlipSettings.adv_placement_opt == 1}">
                            <label class="text-sm">Order Expiry</label>
                            <div class="relative w-40">
                                <select class="shadow appearance-none border rounded text-sm w-full py-1 px-3 text-gray-700 leading-tight focus:outline-none" v-model="orderForm.orderExpiry" @change="clearOrderMessage">
                                    <option value="30">Now</option>
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
                            <span class="px-2" v-else>{{orderMessage}}</span>
                            <span class="absolute clearOrderMessage float-right cursor-pointer text-xs" @click="isDoneBetting = false"><i class="fas fa-times-circle"></i></span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center w-full">
                    <button v-if="isPlacingOrder" @click="placeOrder" class="bg-orange-500 text-white rounded-lg w-full text-sm uppercase p-2 mt-2 opacity-75" disabled>Placing Order... <span class="text-sm"><i class="fas fa-circle-notch fa-spin"></i></span></button>
                    <button v-if="!isPlacingOrder" @click="placeOrder" class="bg-orange-500 text-white rounded-lg w-full text-sm uppercase p-2 mt-2 focus:outline-none" :class="[!retrievedMarketData || minMaxData.length == 0 ? 'opacity-75' : 'hover:bg-orange-600']" :disabled="!retrievedMarketData || minMaxData.length == 0">Place Order</button>
                </div>
            </div>
        </dialog-drag>
        <odds-history v-if="showOddsHistory" @close="closeOddsHistory" :market_id="market_id" :event_id="odd_details.game.uid"></odds-history>
        <bet-matrix v-if="showBetMatrix" @close="closeBetMatrix" :market_id="market_id" :analysis-data="analysisData" :event_id="odd_details.game.uid"></bet-matrix>
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
            inputPrice: null || twoDecimalPlacesFormat(this.odd_details.odds),
            points: null,
            selectedPoint: {},
            market_id: this.odd_details.market_id,
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
            spreads: [],
            displayedSpreads: [],
            startPointIndex: 0,
            endPointIndex: 5,
            isEventNotAvailable: null,
            minMaxUpdateCounter: 0
        }
    },
    validations: {
        inputPrice: { required, minValue: minValue(0), decimal },
        orderForm: {
            stake:  { required, minValue: minValue(1), decimal }
        }
    },
    computed: {
        ...mapState('trade', ['activeBetSlip', 'bookies', 'betSlipSettings', 'wallet', 'underMaintenanceProviders']),
        ...mapState('settings', ['defaultPriceFormat']),
        activePointIndex() {
            if(!_.isEmpty(this.spreads)) {
                return this.spreads.findIndex(spread => spread.points == this.points)
            }
        },
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
        }
    },
    watch: {
        minMaxProviders: {
            deep: true,
            handler(value) {
                this.minMaxUpdateCounter = this.minMaxUpdateCounter + 1
                if(this.minMaxUpdateCounter <= (this.market_details.providers.length + 1)) {
                    let minMaxPrices = value.filter(minmax => minmax.price != null).map(minmax => minmax.price)
                    if(minMaxPrices.length > 0) {
                        this.inputPrice = twoDecimalPlacesFormat(Math.max(...minMaxPrices))
                        this.minMaxData = value.filter(minmax => minmax.price == Math.max(...minMaxPrices))
                        this.selectedProviders = this.minMaxData.map(minmax => minmax.provider_id)
                    }
                }

                if(!_.isEmpty(this.minMaxData)) {
                    let selectedMinmaxDataPrices = this.minMaxData.map(minmax => minmax.price)
                    this.inputPrice = twoDecimalPlacesFormat(Math.min(...selectedMinmaxDataPrices))
                }
            }
        }
    },
    mounted() {
        this.getMarketDetails(true)
        this.$store.dispatch('trade/getBetSlipSettings')
    },
    methods: {
        reloadSpread() {
            this.isLoadingMarketDetailsAndProviders = true;
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
        getMarketDetails(setMinMaxProviders) {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/${this.market_id}`, { headers: { 'Authorization': `Bearer ${token}` }})
                .then(response => {
                    this.market_details = response.data.data
                    this.formattedRefSchedule = response.data.data.ref_schedule.split(' ')
                    this.points = this.odd_details.points || null
                    let spreadsMemUID = response.data.data.spreads.map(spread => spread.market_id)
                    if(response.data.data.spreads.length != 0) {
                        if(spreadsMemUID.includes(this.market_id)) {
                            this.spreads = moveToFirstElement(response.data.data.spreads, 'market_id', this.market_id)
                        } else {
                            this.spreads = [this.odd_details]
                        }
                    } else {
                        this.spreads = [this.odd_details]
                    }
                    this.displaySpreadsByFive()
                    if (setMinMaxProviders) {
                        this.setMinMaxProviders()
                    } else {
                        this.isLoadingMarketDetailsAndProviders = false;
                        this.minmax(this.market_id)
                    }
                    this.$store.commit('trade/SHOW_BET_MATRIX_IN_BETSLIP', { market_id: this.market_id, has_bet: response.data.data.has_bets })
                })
                .catch(err => {
                    this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
                })
        },
        setActiveBetSlip(market_id) {
            this.$store.commit('trade/SET_ACTIVE_BETSLIP', market_id)
        },
        async setMinMaxProviders() {
            await this.$store.dispatch('trade/getBookies')
            let settingsConfig = await this.$store.dispatch('settings/getUserSettingsConfig', 'bookies')
            this.disabledBookies = settingsConfig.disabled_bookies
            let enabledBookies = this.bookies.filter(bookie => !this.disabledBookies.includes(bookie.id))
            enabledBookies.map(bookie => this.minMaxProviders.push({ provider_id: bookie.id, provider: bookie.alias, min: null, max: null, price: null, priority: bookie.priority, age: null, hasMarketData: false }))
            this.isLoadingMarketDetailsAndProviders = false
            this.minmax(this.market_id)
        },
        displaySpreadsByFive() {
            this.displayedSpreads = this.spreads.slice(this.startPointIndex, this.endPointIndex)
        },
        changePoint(points, market_id, odds) {
            this.emptyMinMax(this.market_id)
            this.points = points
            this.market_id = market_id
            this.inputPrice = twoDecimalPlacesFormat(Number(odds))
            this.setActiveBetSlip(market_id)
            this.minmax(market_id)
            this.showBetMatrix = false
            this.minMaxUpdateCounter = 0
            this.isEventNotAvailable = false
            this.clearOrderMessage()
        },
        previousPoint() {
            if(this.activePointIndex != 0) {
                let previousSpread = this.spreads[this.activePointIndex - 1]
                this.changePoint(previousSpread.points, previousSpread.market_id, previousSpread.odds)
            }

            if(this.spreads.length > 5) {
                if(this.startPointIndex !== 0) {
                    this.startPointIndex = this.startPointIndex - 1;
                    this.endPointIndex = this.endPointIndex - 1;
                    this.displaySpreadsByFive();
                }
            }
        },
        nextPoint() {
            if(this.activePointIndex != (this.spreads.length - 1)) {
                let nextSpread = this.spreads[this.activePointIndex + 1]
                this.changePoint(nextSpread.points, nextSpread.market_id, nextSpread.odds)
            }

            if(this.spreads.length > 5) {
                if(this.endPointIndex !== this.spreads.length && this.displayedSpreads[0].points != this.spreads[this.spreads.length - 5].points) {
                    this.startPointIndex = this.startPointIndex + 1;
                    this.endPointIndex = this.endPointIndex + 1;
                    this.displaySpreadsByFive();
                }
            }
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
                                provider.priority = Number(minmax.priority) || provider.priority
                                provider.age = minmax.age || null
                                provider.hasMarketData = hasMarketData
                            }
                        })
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
                        this.inputPrice = null
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
                let minmaxPrices = this.minMaxData.map(minmax => minmax.price)
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
            } else {
                this.isPlacingOrder = true
                this.hasErrorOnInput = false
                let data = {
                    betType: this.orderForm.betType,
                    stake: this.orderForm.stake,
                    orderExpiry: this.orderForm.orderExpiry,
                    market_id: this.market_id
                }

                if(this.orderForm.betType == 'FAST_BET') {
                    let greaterThanOrEqualThanPriceArray = []
                    this.orderForm.markets = []
                    this.minMaxData.map(minmax => {
                        if(minmax.price >= this.initialPrice) {
                            greaterThanOrEqualThanPriceArray.push(minmax)
                        }
                    })
                    let sortedByPriorityArray = greaterThanOrEqualThanPriceArray.sort((a, b) => (a.priority > b.priority) ? 1 : -1)
                    sortedByPriorityArray.map(sortedByPriority => {
                        if(this.orderForm.stake > sortedByPriority.max) {
                            if(this.wallet.credit >= sortedByPriority.max) {
                                this.orderForm.stake = twoDecimalPlacesFormat(this.orderForm.stake - sortedByPriority.max)
                                this.orderForm.markets.push(sortedByPriority)
                                this.orderMessage = ''
                            } else {
                                this.orderMessage = 'Insufficient wallet balance.'
                                this.isBetSuccessful = false
                            }
                        } else if(this.orderForm.stake <= sortedByPriority.max && this.orderForm.stake >= sortedByPriority.min) {
                            if(this.wallet.credit >= this.orderForm.stake) {
                                this.orderForm.stake = 0
                                this.orderForm.markets.push(sortedByPriority)
                                this.orderMessage = ''
                            } else {
                                this.orderMessage = 'Insufficient wallet balance.'
                                this.isBetSuccessful = false
                            }
                        } else if(this.orderForm.stake < sortedByPriority.min && this.orderForm.stake != 0) {
                            this.orderMessage = 'Stake lower than minimum stake or cannot proceed to next provider.'
                            this.isBetSuccessful = false
                        }
                    })
                } else if(this.orderForm.betType == 'BEST_PRICE') {
                    let greaterThanOrEqualThanPriceArray = []
                    this.minMaxData.map(minmax => {
                        if(minmax.price >= this.initialPrice) {
                            greaterThanOrEqualThanPriceArray.push(minmax.price)
                        }
                    })
                    let bestPricesArray = this.minMaxData.filter(minmax => minmax.price == Math.max(...greaterThanOrEqualThanPriceArray))
                    let bestPricesPriorityArray = bestPricesArray.map(bestPrices => bestPrices.priority)
                    let mostPriorityArray = bestPricesArray.filter(bestPrices => bestPrices.priority == Math.min(...bestPricesPriorityArray))
                    mostPriorityArray.map(mostPriority => {
                        if(this.orderForm.stake > mostPriority.max) {
                            if(this.wallet.credit >= mostPriority.max) {
                                this.orderForm.stake = twoDecimalPlacesFormat(this.orderForm.stake - mostPriority.max)
                                this.orderForm.markets = mostPriorityArray
                                this.orderMessage = ''
                            } else {
                                this.orderMessage = 'Insufficient wallet balance.'
                                this.isBetSuccessful = false
                            }
                        } else if(this.orderForm.stake <= mostPriority.max && this.orderForm.stake >= mostPriority.min) {
                            if(this.wallet.credit >= this.orderForm.stake) {
                                this.orderForm.stake = 0
                                this.orderForm.markets = mostPriorityArray
                                this.orderMessage = ''
                            } else {
                                this.orderMessage = 'Insufficient wallet balance.'
                                this.isBetSuccessful = false
                            }
                        } else if(this.orderForm.stake < mostPriority.min && this.orderForm.stake != 0) {
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
                            this.$store.dispatch('trade/getBetbarData')
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
                            if(err.response.data.status_code != 404) {
                                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
                            }
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
                let { $set, options } = vnode.context
                $set(options, 'top', window.innerHeight / 2)
                $set(options, 'left', window.innerWidth / 2)
            },
            componentUpdated(el, binding, vnode)  {
                if(binding.value) {
                    el.style.zIndex = '150'
                } else {
                    el.style.zIndex = '101'
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

<style>
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
