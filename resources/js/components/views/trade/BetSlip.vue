<template>
    <div class="betslip flex justify-center items-center">
        <dialog-drag :title="'Bet Slip - '+odd_details.market_id" :options="options" @close="closeBetSlip(odd_details.market_id)">
            <div class="container mx-auto p-2">
                <div class="flex items-center w-1/2">
                    <span class="text-white uppercase font-bold mr-2 my-2 px-2 bg-orange-500">{{market_details.odd_type}}</span>
                    <span class="text-gray-800 font-bold my-2 pr-6">{{market_details.league_name}}</span>
                    <a href="#" @click.prevent="openBetMatrix(odd_details)" class="text-center py-1 pr-1" title="Bet Matrix" v-if="oddTypesWithSpreads.includes(market_details.odd_type)"><i class="fas fa-chart-area"></i></a>
                    <a href="#" @click.prevent="openOddsHistory(odd_details)" lass="text-center py-1" title="Odds History"><i class="fas fa-bars"></i></a>
                </div>
                <div class="flex justify-between items-center w-full">
                    <div class="flex justify-between w-2/4 items-center teams">
                        <div class="home" :class="[market_details.market_flag==='HOME' ? 'p-3 bg-white shadow-xl' : '']">
                            <span class="font-bold bg-green-500 text-white mr-1 p-2 rounded-lg">Home</span>
                            <span class="w-full text-gray-800 font-bold">{{market_details.home}}</span>
                        </div>
                        <span class="text-sm text-gray-800">VS.</span>
                        <div class="away" :class="[market_details.market_flag==='AWAY' ? 'p-3 bg-white shadow-xl' : '']">
                            <span class="font-bold bg-red-600 text-white mr-1 p-2 rounded-lg">Away</span>
                            <span class="w-full text-gray-800 font-bold">{{market_details.away}}</span>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <a href="#" class="text-center py-1 pr-1 mr-2"><i class="far fa-calendar-alt"></i> {{formattedRefSchedule[0]}}</a>
                        <a href="#" class="text-center py-1"><i class="far fa-clock"></i> {{formattedRefSchedule[1]}}</a>
                    </div>
                </div>
                <!-- other data are hardcoded first, only game details were dynamic -->
                <div class="flex w-full">
                    <div class="flex flex-col mt-4 mr-3 p-2 shadow shadow-xl bg-white w-2/5 h-full">
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm">Min Stake</span>
                            <span class="text-sm">100</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm">Max Stake</span>
                            <span class="text-sm">100000</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm">Average Price</span>
                            <span class="text-sm">{{odd_details.odds}}</span>
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
                            <input class="shadow appearance-none border rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="number" v-model="initialPrice">
                        </div>
                        <div class="flex justify-between items-center py-2">
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
                        <div class="flex justify-between items-center py-2">
                            <label class="text-sm flex items-center">
                                <span class="mr-4">Fast Bet</span>
                                <input class="outline-none rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="radio" value="FAST_BET" v-model="orderForm.betType">
                            </label>
                            <label class="text-sm flex items-center">
                                <span class="mr-4">Best Price</span>
                                <input class="outline-none rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="radio" value="BEST_PRICE" v-model="orderForm.betType">
                            </label>
                        </div>
                        <span class="text-sm text-green-600">{{orderMessage}}</span>
                        <span class="text-sm text-red-600">{{orderError}}</span>
                    </div>
                    <div class="flex flex-col mt-4 w-3/5 h-full">
                        <div class="flex flex-col items-center bg-white shadow shadow-xl mb-2" v-if="oddTypesWithSpreads.includes(market_details.odd_type)">
                            <span class="text-white uppercase font-bold mr-2 my-3 px-2 bg-orange-500">{{market_details.odd_type}}</span>
                            <div class="flex justify-around items-center">
                                <a href="#" class="m-1 w-12 text-center text-gray-800"><i class="fas fa-chevron-left"></i></a>
                                <a href="#" class="m-1 w-12 text-center text-sm text-white bg-orange-500 px-2 py-1">-0.75</a>
                                <a href="#" class="m-1 w-12 text-center text-sm text-gray-800 bg-gray-200 px-2 py-1 hover:text-white hover:bg-orange-500">-0.50</a>
                                <a href="#" class="m-1 w-12 text-center text-sm text-gray-800 bg-gray-200 px-2 py-1 hover:text-white hover:bg-orange-500">-0.25</a>
                                <a href="#" class="m-1 w-12 text-center text-sm text-gray-800 bg-gray-200 px-2 py-1 hover:text-white hover:bg-orange-500">0</a>
                                <a href="#" class="m-1 w-12 text-center text-sm text-gray-800 bg-gray-200 px-2 py-1 hover:text-white hover:bg-orange-500">0.25</a>
                                <a href="#" class="m-1 w-12 text-center text-sm text-gray-800 bg-gray-200 px-2 py-1 hover:text-white hover:bg-orange-500">0.50</a>
                                <a href="#" class="m-1 w-12 text-center text-sm text-gray-800 bg-gray-200 px-2 py-1 hover:text-white hover:bg-orange-500">0.75</a>
                                <a href="#" class="m-1 w-12 text-center text-gray-800"><i class="fas fa-chevron-right"></i></a>
                            </div>
                        </div>
                        <div class="flex flex-col bg-white shadow shadow-xl py-8 px-3">
                            <div class="flex justify-between items-center p-1">
                                <span class="w-1/4"></span>
                                <span class="w-1/4 text-sm font-bold">Min</span>
                                <span class="w-1/4 text-sm font-bold">Max</span>
                                <span class="w-1/4 text-sm font-bold">Price</span>
                            </div>
                            <!-- <div class="flex justify-between items-center p-1" v-for="bookie in bookies" :key="bookie.id">
                                <span class="w-1/4 text-sm font-bold">{{bookie.alias}}</span>
                            </div> -->
                            <div class="flex justify-between items-center p-1" v-for="minmax in minMaxData" :key="minmax.provider_id">
                                <span class="w-1/4 text-sm font-bold">{{minmax.provider}}</span>
                                <span class="w-1/4 text-sm">{{minmax.min}}</span>
                                <span class="w-1/4 text-sm">{{minmax.max}}</span>
                                <a href="#" @click.prevent="updatePrice(minmax.price)" class="w-1/4 text-sm font-bold underline">{{minmax.price}}</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center w-full">
                    <button @click="placeOrder" class="bg-orange-500 text-white rounded-lg hover:bg-orange-600 w-full text-sm uppercase p-2 mt-2">Place Order</button>
                </div>
            </div>
        </dialog-drag>
        <bet-matrix v-for="odd in openedBetMatrix" :key="odd.market_id" :odd_details="odd" :points="points"></bet-matrix>
        <odds-history v-for="odd in openedOddsHistory" :key="odd.market_id" :odd_details="odd" :market_details="market_details"></odds-history>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import _ from 'lodash'
import BetMatrix from './BetMatrix'
import OddsHistory from './OddsHistory'
import 'vue-dialog-drag/dist/vue-dialog-drag.css'
import DialogDrag from 'vue-dialog-drag'

export default {
    props: ['odd_details'],
    components: {
        DialogDrag,
        BetMatrix,
        OddsHistory
    },
    data() {
        return {
            market_details: {},
            formattedRefSchedule: [],
            initialPrice: this.odd_details.odds,
            orderForm: {
                stake: '',
                orderExpiry: 'Now',
                betType: 'FAST_BET',
                markets: []
            },
            // NOTE: remove this once minmax socket is done, hardcoded for betting purposes
            minMaxData: [
                {
                    provider_id: 1,
                    provider: 'HG',
                    min: 100,
                    max: 500,
                    price: 0.69,
                    priority: 1
                },
                {
                    provider_id: 2,
                    provider: 'PIN',
                    min: 350,
                    max: 1000,
                    price: this.odd_details.odds,
                    priority: 2
                },
                {
                    provider_id: 3,
                    provider: 'ISN',
                    min: 600,
                    max: 2000,
                    price: this.odd_details.odds,
                    priority: 3
                }
            ],
            oddTypesWithSpreads: ['HDP', 'HT HDP', 'OU', 'HT OU'],
            orderMessage: '',
            orderError: '',
            options: {
                width:825,
                buttonPin: false,
                centered: "viewport"
            }
        }
    },
    computed: {
        ...mapState('trade', ['openedBetMatrix', 'openedOddsHistory', 'bookies']),
        points() {
            if(!_.isEmpty(this.market_details)) {
                if(this.market_details.odd_type == 'HDP' || this.market_details.odd_type == 'HT HDP') {
                    return Number(this.odd_details.points)
                } else if(this.market_details.odd_type == 'OU' || this.market_details.odd_type == 'HT OU') {
                    return Number(this.odd_details.points.split(' ')[1])
                } else {
                    return
                }
            }
        }
    },
    mounted() {
        this.getMarketDetails()
        this.$store.dispatch('trade/getBookies')
        this.$socket.send(`getMinMax_${this.odd_details.market_id}`)
    },
    methods: {
        getMarketDetails() {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/${this.odd_details.market_id}`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.market_details = response.data.data
                this.formattedRefSchedule = response.data.data.ref_schedule.split(' ')
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        },
        closeBetSlip(market_id) {
            this.$store.commit('trade/CLOSE_BETSLIP', this.odd_details.market_id)
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
        placeOrder() {
            let data = {
                betType: this.orderForm.betType,
                stake: this.orderForm.stake,
                orderExpiry: this.orderForm.orderExpiry,
                market_id: this.odd_details.market_id
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
                    } else if(this.orderForm.stake <= sortedByPriority.max && this.orderForm.stake >= sortedByPriority.min) {
                        this.orderForm.stake = 0
                        this.orderForm.markets.push(sortedByPriority)
                        this.orderError = ''
                    } else if(this.orderForm.stake < sortedByPriority.min && this.orderForm.stake != 0) {
                        this.orderError = 'Stake lower than minimum stake or cannot proceed to next provider.'
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
                    } else if(this.orderForm.stake <= mostPriority.max && this.orderForm.stake >= mostPriority.min) {
                        this.orderForm.stake = 0
                        this.orderForm.markets = mostPriorityArray
                        this.orderError = ''
                    } else if(this.orderForm.stake < mostPriority.min && this.orderForm.stake != 0) {
                        this.orderMessage = 'Stake lower than minimum stake.'
                    }
                })
            }
            this.$set(data, 'markets', this.orderForm.markets)

            let token = Cookies.get('mltoken')

            axios.post('v1/orders/bet', data, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.orderMessage = response.data.data
                this.$store.dispatch('trade/getBetbarData')
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        }
    }
}
</script>

<style>
    .orderExpiryInput {
        width: 10.2rem;
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
</style>
