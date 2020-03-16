<template>
    <div class="betslip flex justify-center items-center">
        <dialog-drag :title="'Bet Slip - '+market_id" :options="options" @close="closeBetSlip(market_id)">
            <div class="container mx-auto p-2">
                <div class="flex items-center w-1/2">
                    <span class="text-white uppercase font-bold mr-2 my-2 px-2 bg-orange-500">{{market_details.odd_type}}</span>
                    <span class="text-gray-800 font-bold my-2 pr-6">{{market_details.league_name}}</span>
                    <a href="#" class="text-center py-1 pr-1" title="Bet Matrix"><i class="fas fa-chart-area"></i></a>
                    <a href="#" @click.prevent="openOddsHistory(market_id)" lass="text-center py-1" title="Odds History"><i class="fas fa-bars"></i></a>
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
                            <span class="text-sm">6.99</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm">{{market_details.odd_type}}</span>
                            <span class="text-sm">-0.69</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <label class="text-sm">Stake</label>
                            <input class="shadow appearance-none border rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="number" v-model="orderForm.stake">
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <label class="text-sm">Price</label>
                            <input class="shadow appearance-none border rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="number" v-model="orderForm.price">
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
                                <input class="outline-none rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="radio" value="1" v-model="orderForm.betType">
                            </label>
                            <label class="text-sm flex items-center">
                                <span class="mr-4">Best Bet</span>
                                <input class="outline-none rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="radio" value="2" v-model="orderForm.betType">
                            </label>
                        </div>
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
                            <div class="flex justify-between items-center p-1" v-for="bookie in bookies" :key="bookie.id">
                                <span class="w-1/4 text-sm font-bold">{{bookie.alias}}</span>
                                <span class="w-1/4 text-sm">6.90</span>
                                <span class="w-1/4 text-sm">6.90</span>
                                <span class="w-1/4 text-sm">6.90</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center w-full">
                    <button @click="placeOrder" class="bg-orange-500 text-white rounded-lg hover:bg-orange-600 w-full text-sm uppercase p-2 mt-2">Place Order</button>
                </div>
            </div>
        </dialog-drag>
        <odds-history></odds-history>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import _ from 'lodash'
import OddsHistory from './OddsHistory'
import 'vue-dialog-drag/dist/vue-dialog-drag.css'
import DialogDrag from 'vue-dialog-drag'

export default {
    props: ['market_id'],
    components: {
        DialogDrag,
        OddsHistory
    },
    data() {
        return {
            market_details: {},
            formattedRefSchedule: [],
            orderForm: {
                stake: '',
                price: '',
                orderExpiry: 'Now',
                betType: '1'
            },
            oddTypesWithSpreads: ['HDP', 'HT HDP', 'OU', 'HT OU'],
            bookies: [],
            options: {
                width:825,
                height:520,
                buttonPin: false,
                centered: "viewport"
            }
        }
    },
    mounted() {
        this.getMarketDetails()
        this.getBookies()
        this.$socket.send(`getMinMax_${this.market_id}`)
    },
    methods: {
        getMarketDetails() {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/${this.market_id}`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.market_details = response.data.data
                this.formattedRefSchedule = response.data.data.ref_schedule.split(' ')
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        },
        getBookies() {
            let token = Cookies.get('mltoken')

            axios.get('v1/bookies', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => this.bookies = response.data.data)
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        },
        closeBetSlip(market_id) {
            this.$store.commit('trade/CLOSE_BETSLIP', market_id)
        },
        openOddsHistory(market_id) {
            this.$store.commit('trade/CLOSE_ODDS_HISTORY', market_id)
            this.$store.commit('trade/OPEN_ODDS_HISTORY', market_id)
        },
        placeOrder() {
            /* place bet (API or Socket) */
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
