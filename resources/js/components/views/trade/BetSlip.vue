<template>
    <div class="betslip flex justify-center items-center">
        <dialog-drag :title="'Bet Slip - '+market_id" :options="options" @close="closeBetSlip(odd_details.market_id)" @click.native="setActiveBetSlip(odd_details.market_id)" v-overlap-all-betslip="activeBetSlip==odd_details.market_id">
            <div class="flex flex-col justify-center items-center loader" v-if="isLoadingMarketDetails">
                <img :src="loader" />
                <span class="text-center mt-2">Loading Market Details...</span>
            </div>
            <div class="container mx-auto p-2" v-else>
                <div class="flex items-center w-1/2 leagueAndTeamDetails">
                    <span class="text-white uppercase font-bold mr-2 my-2 px-2 bg-orange-500">{{market_details.odd_type}}</span>
                    <span class="text-gray-800 font-bold my-2 pr-6">{{market_details.league_name}}</span>
                    <a href="#" @click.prevent="openBetMatrix(odd_details)" class="text-center py-1 pr-1" title="Bet Matrix" v-if="oddTypesWithSpreads.includes(market_details.odd_type) && isDoneBetting && isBetSuccessful"><i class="fas fa-chart-area"></i></a>
                    <a href="#" @click.prevent="openOddsHistory(odd_details)" lass="text-center py-1" title="Odds History"><i class="fas fa-bars"></i></a>
                </div>
                <div class="flex justify-between items-center w-full leagueAndTeamDetails">
                    <div class="flex w-3/4 items-center">
                        <div class="home p-3" :class="[market_details.market_flag==='HOME' ? 'mr-2 bg-white shadow-xl' : '']">
                            <span class="font-bold bg-green-500 text-white mr-1 p-2 rounded-lg">Home</span>
                            <span class="w-full text-gray-800 font-bold">{{market_details.home}}</span>
                        </div>
                        <span class="text-sm text-gray-800">VS.</span>
                        <div class="away p-3" :class="[market_details.market_flag==='AWAY' ? 'ml-2 bg-white shadow-xl' : '']">
                            <span class="font-bold bg-red-600 text-white mr-1 p-2 rounded-lg">Away</span>
                            <span class="w-full text-gray-800 font-bold">{{market_details.away}}</span>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <a href="#" class="text-center py-1 pr-1 mr-2"><i class="far fa-calendar-alt"></i> {{formattedRefSchedule[0]}}</a>
                        <a href="#" class="text-center py-1"><i class="far fa-clock"></i> {{formattedRefSchedule[1]}}</a>
                    </div>
                </div>
                <div class="flex w-full">
                    <div class="flex flex-col mt-4 mr-3 p-2 shadow shadow-xl bg-white w-2/5 h-full">
                        <div class="advanceBetSlipInfo" :class="{'hidden': betSlipSettings.adv_betslip_info == 0, 'block': betSlipSettings.adv_betslip_info == 1}">
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm">Min Stake</span>
                                <span class="text-sm">{{lowestMin}}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm">Max Stake</span>
                                <span class="text-sm">{{highestMax}}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm">Average Price</span>
                                <span class="text-sm">{{displayedAveragePrice}}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm">Towin</span>
                                <span class="text-sm">{{towin}}</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm">{{market_details.odd_type}}</span>
                            <span class="text-sm">{{points}}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <label class="text-sm">Stake</label>
                            <input class="shadow appearance-none border rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="number" v-model="orderForm.stake" @keyup="clearOrderMessage">
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <label class="text-sm">Price</label>
                            <input class="shadow appearance-none border rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="number" v-model="initialPrice" @keyup="clearOrderMessage">
                        </div>
                        <div class="flex justify-between items-center py-2" :class="{'hidden': betSlipSettings.adv_placement_opt == 0, 'block': betSlipSettings.adv_placement_opt == 1}">
                            <label class="text-sm">Order Expiry</label>
                            <div class="relative orderExpiryInput">
                                <select class="shadow appearance-none border rounded text-sm w-full py-1 px-3 text-gray-700 leading-tight focus:outline-none" v-model="orderForm.orderExpiry">
                                    <option value="Now">Now</option>
                                    <option value="2m">2 mins</option>
                                    <option value="5m">5 mins</option>
                                    <option value="10m">10 mins</option>
                                    <option value="30m">30 mins</option>
                                    <option value="1h">1 hour</option>
                                    <option value="2h">2 hours</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between items-center py-2" :class="{'hidden': betSlipSettings.adv_placement_opt == 0, 'block': betSlipSettings.adv_placement_opt == 1}">
                            <label class="text-sm flex items-center">
                                <span class="mr-4">Fast Bet</span>
                                <input class="outline-none rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="radio" value="FAST_BET" v-model="orderForm.betType">
                            </label>
                            <label class="text-sm flex items-center">
                                <span class="mr-4">Best Price</span>
                                <input class="outline-none rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="radio" value="BEST_PRICE" v-model="orderForm.betType">
                            </label>
                        </div>
                        <span class="text-sm">{{orderPrompt}}</span>
                    </div>
                    <div class="flex flex-col mt-4 w-3/5 h-full">
                        <div class="flex flex-col items-center bg-white shadow shadow-xl mb-2" v-if="oddTypesWithSpreads.includes(market_details.odd_type)">
                            <span class="text-white uppercase font-bold my-3 px-2 bg-orange-500">{{market_details.odd_type}}</span>
                            <div class="flex justify-center items-center">
                                <a href="#" class="m-1 w-12 text-center text-gray-800" @click="previousPoint" v-if="spreads.length > 1"><i class="fas fa-chevron-left"></i></a>
                                <a href="#" class="m-1 w-16 text-center text-sm" :class="[spread.points == points ? 'text-white bg-orange-500 px-1 py-1' : 'text-gray-800']" v-for="(spread, index) in spreads" :key="index" @click="changePoint(spread.points, spread.market_id)">{{spread.points}}</a>
                                <a href="#" class="m-1 w-12 text-center text-gray-800" @click="nextPoint" v-if="spreads.length > 1"><i class="fas fa-chevron-right"></i></a>
                            </div>
                        </div>
                        <div class="flex flex-col bg-white shadow shadow-xl py-8 px-3">
                            <div class="flex justify-between items-center p-1">
                                <span class="w-1/4"></span>
                                <span class="w-1/4 text-sm font-bold">Min</span>
                                <span class="w-1/4 text-sm font-bold">Max</span>
                                <span class="w-1/4 text-sm font-bold">Price</span>
                            </div>
                            <div v-if="minMaxData.length != 0">
                                <div class="flex justify-between items-center p-1" v-for="minmax in minMaxData" :key="minmax.provider_id">
                                    <span class="w-1/4 text-sm font-bold">{{minmax.provider}}</span>
                                    <span class="w-1/4 text-sm">{{minmax.min}}</span>
                                    <span class="w-1/4 text-sm">{{minmax.max}}</span>
                                    <a href="#" @click.prevent="updatePrice(minmax.price)" class="w-1/4 text-sm font-bold underline">{{minmax.price}}</a>
                                </div>
                            </div>
                            <div v-else class="flex justify-center">
                                <span class="text-sm mt-2">No markets available.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center w-full">
                    <button @click="placeOrder" class="bg-orange-500 text-white rounded-lg w-full text-sm uppercase p-2 mt-2" :class="[minMaxData.length === 0 ? 'opacity-75' : 'hover:bg-orange-600']" :disabled="minMaxData.length === 0">Place Order</button>
                </div>
            </div>
        </dialog-drag>
        <bet-matrix v-for="odd in openedBetMatrix" :key="odd.market_id" :odd_details="odd" :analysis-data="analysisData"></bet-matrix>
        <odds-history v-for="odd in openedOddsHistory" :key="odd.market_id" :odd_details="odd" :market_details="market_details"></odds-history>
    </div>
</template>

<script>
import loader from '../../../../assets/images/loader.gif'
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import _ from 'lodash'
import BetMatrix from './BetMatrix'
import OddsHistory from './OddsHistory'
import 'vue-dialog-drag/dist/vue-dialog-drag.css'
import DialogDrag from 'vue-dialog-drag'
import { getSocketKey, getSocketValue } from '../../../helpers/socket'

export default {
    props: ['odd_details'],
    components: {
        DialogDrag,
        BetMatrix,
        OddsHistory
    },
    data() {
        return {
            loader: loader,
            market_details: {},
            formattedRefSchedule: [],
            initialPrice: this.odd_details.odds,
            points: null,
            market_id: this.odd_details.market_id,
            orderForm: {
                stake: '',
                orderExpiry: 'Now',
                betType: 'BEST_PRICE',
                markets: []
            },
            minMaxData: [],
            oddTypesWithSpreads: ['HDP', 'HT HDP', 'OU', 'HT OU'],
            orderMessage: '',
            orderError: '',
            options: {
                width: 825,
                buttonPin: false,
                centered: "viewport"
            },
            analysisData: {},
            isDoneBetting: false,
            isLoadingMarketDetails: true,
            isBetSuccessful: null
        }
    },
    computed: {
        ...mapState('trade', ['activeBetSlip', 'openedBetMatrix', 'openedOddsHistory', 'betSlipSettings']),
        spreads() {
            if(!_.isEmpty(this.market_details)) {
                return this.market_details.spreads
            }
        },
        activePointIndex() {
            if(!_.isEmpty(this.spreads)) {
                return this.spreads.findIndex(spread => spread.points == this.points)
            }
        },
        orderPrompt() {
            if(this.orderMessage == '') {
                return this.orderError
            } else {
                return this.orderMessage
            }
        },
        bet_score() {
            if(!_.isEmpty(this.market_details)) {
                let scores = this.market_details.score.split(' ')
                if(this.market_details.market_flag=='HOME') {
                    return Number(scores[0])
                } else if(this.market_details.market_flag=='AWAY') {
                    return Number(scores[2])
                }
            }
        },
        against_score() {
            if(!_.isEmpty(this.market_details)) {
                let scores = this.market_details.score.split(' ')
                if(this.market_details.market_flag=='HOME') {
                    return Number(scores[2])
                } else if(this.market_details.market_flag=='AWAY') {
                    return Number(scores[0])
                }
            }
        },
        lowestMin() {
            if(!_.isEmpty(this.minMaxData)) {
                let minValues = this.minMaxData.map(minmax => minmax.min)
                return Math.min(...minValues)
            } else {
                return 0
            }
        },
        highestMax() {
            if(!_.isEmpty(this.minMaxData)) {
                let maxValues = this.minMaxData.map(minmax => minmax.max)
                return Math.max(...maxValues)
            } else {
                return 0
            }
        },
        displayedAveragePrice() {
            if(!_.isEmpty(this.minMaxData)) {
                let prices = this.minMaxData.map(minmax => minmax.price)
                let sumOfPrices = prices.reduce((firstPrice, secondPrice) => firstPrice + secondPrice, 0)
                return Math.floor(sumOfPrices / prices.length * 100) / 100
            } else {
                return this.odd_details.odds
            }
        },
        towin() {
            return Math.floor(this.orderForm.stake * this.initialPrice * 100) / 100
        }
    },
    mounted() {
        this.getMarketDetails()
        this.minmax(this.market_id)
        this.$store.dispatch('trade/getBetSlipSettings')
    },
    methods: {
        getMarketDetails() {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/${this.odd_details.market_id}`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.market_details = response.data.data
                this.formattedRefSchedule = response.data.data.ref_schedule.split(' ')
                this.isLoadingMarketDetails = false
                this.points = this.odd_details.points
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        },
        setActiveBetSlip(market_id) {
            this.$store.commit('trade/SET_ACTIVE_BETSLIP', market_id)
        },
        changePoint(points, market_id) {
            this.emptyMinMax(this.market_id)
            this.points = points
            this.market_id = market_id
            this.minmax(market_id)
        },
        previousPoint() {
            if(this.activePointIndex != 0) {
                let previousSpread = this.spreads[this.activePointIndex - 1]
                this.changePoint(previousSpread.points, previousSpread.market_id)
            }
        },
        nextPoint() {
            if(this.activePointIndex != (this.spreads.length - 1)) {
                let nextSpread = this.spreads[this.activePointIndex + 1]
                this.changePoint(nextSpread.points, nextSpread.market_id)
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
        getMinMaxData() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getMinMax') {
                    let minmax = getSocketValue(response.data, 'getMinMax')
                    let minMaxObject = {}
                    Object.keys(minmax).map(key => {
                        let mustBeNumeric = ['min', 'max', 'price']
                        if(mustBeNumeric.includes(key)) {
                            this.$set(minMaxObject, key, Number(minmax[key]))
                        } else {
                            this.$set(minMaxObject, key, minmax[key])
                        }
                    })
                    if(minmax.market_id == this.market_id) {
                        if(!_.isEmpty(this.minMaxData)) {
                            let providerIds = this.minMaxData.map(minMaxData => minMaxData.provider_id)
                            if(providerIds.includes(minmax.provider_id)) {
                                this.minMaxData.map(minMaxData => {
                                    if(minMaxData.provider_id == minmax.provider_id) {
                                        minMaxData.min = Number(minmax.min)
                                        minMaxData.max = Number(minmax.max)
                                        minMaxData.price = Number(minmax.price)
                                    }
                                })
                            } else {
                                this.minMaxData.push(minMaxObject)
                            }
                        } else {
                            this.minMaxData.push(minMaxObject)
                        }
                    }
                }
            })
        },
        getUpdatedPrice() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'getUpdatedPrice') {
                    let updatedPrice = getSocketValue(response.data, 'getUpdatedPrice')
                    if(!_.isEmpty(this.minMaxData)) {
                        this.minMaxData.map(minMaxData => {
                            if(minMaxData.provider_id == updatedPrice.provider_id) {
                                minMaxData.price = Number(updatedPrice.odds)
                            }
                        })
                    }
                }
            })
        },
        getRemoveMinMax() {
            this.$options.sockets.onmessage = (response => {
                if(getSocketKey(response.data) === 'removeMinMax') {
                    let removeMinMax = getSocketValue(response.data, 'removeMinMax')
                    if(removeMinMax.status) {
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
                this.getUpdatedPrice()
            })
        },
        closeBetSlip(market_id) {
            this.$store.commit('trade/CLOSE_BETSLIP', this.odd_details.market_id)
            this.$store.commit('trade/CLOSE_BET_MATRIX', this.odd_details.market_id)
            this.$store.commit('trade/CLOSE_ODDS_HISTORY', this.odd_details.market_id)
            this.emptyMinMax(this.market_id)
        },
        openBetMatrix(odd_details) {
            this.$store.commit('trade/CLOSE_BET_MATRIX', odd_details.market_id)
            this.$store.commit('trade/OPEN_BET_MATRIX', odd_details)
        },
        openOddsHistory(odd_details) {
            this.$store.commit('trade/CLOSE_ODDS_HISTORY', odd_details.market_id)
            this.$store.commit('trade/OPEN_ODDS_HISTORY', odd_details)
        },
        clearOrderMessage() {
            this.orderMessage = ''
            this.orderError = ''
        },
        updatePrice(price) {
            this.initialPrice = price
        },
        convertPointAsNumeric(points) {
            if(this.market_details.odd_type == 'HDP' || this.market_details.odd_type == 'HT HDP') {
                this.points =  Number(points)
            } else if(this.market_details.odd_type == 'OU' || this.market_details.odd_type == 'HT OU') {
                this.points = Number(points.split(' ')[1])
            } else {
                return
            }
        },
        placeOrder() {
            if(this.orderForm.stake == '' || this.initialPrice == '') {
                this.orderMessage = 'Please input stake or price.'
            } else {
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
                            this.orderForm.stake = this.orderForm.stake - sortedByPriority.max
                            this.orderForm.markets.push(sortedByPriority)
                            this.orderError = ''
                            this.isBetSuccessful = true
                        } else if(this.orderForm.stake <= sortedByPriority.max && this.orderForm.stake >= sortedByPriority.min) {
                            this.orderForm.stake = 0
                            this.orderForm.markets.push(sortedByPriority)
                            this.orderError = ''
                            this.isBetSuccessful = true
                        } else if(this.orderForm.stake < sortedByPriority.min && this.orderForm.stake != 0) {
                            this.orderError = 'Stake lower than minimum stake or cannot proceed to next provider.'
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
                            this.orderForm.stake = this.orderForm.stake - mostPriority.max
                            this.orderForm.markets = mostPriorityArray
                            this.orderError = ''
                            this.isBetSuccessful = true
                        } else if(this.orderForm.stake <= mostPriority.max && this.orderForm.stake >= mostPriority.min) {
                            this.orderForm.stake = 0
                            this.orderForm.markets = mostPriorityArray
                            this.orderError = ''
                            this.isBetSuccessful = true
                        } else if(this.orderForm.stake < mostPriority.min && this.orderForm.stake != 0) {
                            this.orderError = 'Stake lower than minimum stake.'
                            this.isBetSuccessful = false
                        }
                    })
                }
                this.$set(data, 'markets', this.orderForm.markets)

                let token = Cookies.get('mltoken')

                axios.post('v1/orders/bet', data, { headers: { 'Authorization': `Bearer ${token}` }})
                .then(response => {
                    this.orderMessage = response.data.data
                    this.$store.dispatch('trade/getBetbarData')
                    this.$store.commit('trade/TOGGLE_BETBAR', true)
                    this.$store.dispatch('trade/getWalletData')
                    
                    if(this.oddTypesWithSpreads.includes(this.market_details.odd_type)) {
                        let prices = data.markets.map(market => market.price)
                        let sumOfPrices = prices.reduce((firstPrice, secondPrice) => firstPrice + secondPrice, 0)
                        let averagePrice = sumOfPrices / prices.length
                        this.analysisData = {
                            stake: data.stake,
                            hdp: this.convertPointAsNumeric(this.points),
                            price: Math.floor(averagePrice * 100) / 100,
                            bet_score: this.bet_score,
                            against_score: this.against_score
                        }
                    }

                    if(this.betSlipSettings.bets_to_fav == 1 && this.isBetSuccessful) {
                        this.$store.dispatch('trade/addToWatchlist', { type: 'event', data: this.odd_details.game.uid, payload: this.odd_details.game })
                    }

                    this.isDoneBetting = true
                })
                .catch(err => {
                    this.orderMessage = ''
                    this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
                })
            }
        }
    },
    directives: {
        overlapAllBetslip: {
            componentUpdated(el, binding, vnode)  {
                if(binding.value) {
                    el.style.zIndex = '150'
                } else {
                    el.style.zIndex = '101'
                }
            }
        }
    }
}
</script>

<style>
    .orderExpiryInput {
        width: 10.2rem;
    }

    .leagueAndTeamDetails {
        font-size: 15px;
    }

    .dialog-drag {
        border: solid 1px #ed8936;
        box-shadow: none;
        background-color: #edf2f7;
        animation-duration: .2s;
        animation-name: fadeIn;
        animation-timing-function: ease-in-out;
        position: fixed;
    }

    .dialog-drag .dialog-body {
        padding: 0;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .dialog-drag .dialog-header {
        background-color:#ed8936;
    }

    .loader {
        height: 510px;
    }
</style>
