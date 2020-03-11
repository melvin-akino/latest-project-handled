<template>
    <div class="betslip container mx-auto">
        <!-- hard coded data for UI purposes, data should come from either API or Socket -->
        <div class="flex items-center w-1/2">
            <span class="text-white uppercase font-bold mr-2 my-2 px-2 bg-orange-500">FT Handicap</span>
            <span class="text-gray-800 font-bold my-2 pr-6">English Premier League</span>
            <a href="#" class="text-center py-1 pr-1" title="Bet Matrix"><i class="fas fa-chart-area"></i></a>
            <a href="#" @click.prevent="openOddsHistory" lass="text-center py-1" title="Odds History"><i class="fas fa-bars"></i></a>
        </div>
        <div class="flex justify-between items-center w-full">
            <div class="flex justify-between w-2/4 items-center teams">
                <div class="home">
                    <span class="font-bold bg-green-500 text-white mr-1 p-2 rounded-lg">Home</span>
                    <span class="w-full text-gray-800 font-bold">Liverpool</span>
                </div>
                <span class="text-sm text-gray-800">VS.</span>
                <div class="away">
                    <span class="font-bold bg-red-600 text-white mr-1 p-2 rounded-lg">Away</span>
                    <span class="w-full text-gray-800 font-bold">Manchester</span>
                </div>
            </div>
            <div class="flex items-center">
                <a href="#" class="text-center py-1 pr-1 mr-2"><i class="far fa-calendar-alt"></i> 02-02</a>
                <a href="#" class="text-center py-1"><i class="far fa-clock"></i> 01:10</a>
            </div>
        </div>
        <div class="flex items-center w-full">
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
                    <span class="text-sm">HDP</span>
                    <span class="text-sm">-0.69</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <label class="text-sm">Stake</label>
                    <input class="shadow appearance-none border rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="number" v-model="stake">
                </div>
                <div class="flex justify-between items-center py-2">
                    <label class="text-sm">Price</label>
                    <input class="shadow appearance-none border rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="number" v-model="price">
                </div>
                <div class="flex justify-between items-center py-2">
                    <label class="text-sm">Order Expiry</label>
                    <div class="relative orderExpiryInput">
                        <select class="shadow appearance-none border rounded text-sm w-full py-1 px-3 text-gray-700 leading-tight focus:outline-none" v-model="orderExpiry">
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
                        <input class="outline-none rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="radio" value="1" v-model="betType">
                    </label>
                    <label class="text-sm flex items-center">
                        <span class="mr-4">Best Bet</span>
                        <input class="outline-none rounded text-sm py-1 px-3 text-gray-700 leading-tight focus:outline-none" type="radio" value="2" v-model="betType">
                    </label>
                </div>
            </div>
            <div class="flex flex-col mt-4 w-3/5 h-full">
                <div class="flex flex-col items-center bg-white shadow shadow-xl mb-2">
                    <span class="text-white uppercase font-bold mr-2 my-3 px-2 bg-orange-500">FT Handicap</span>
                    <div class="flex justify-around items-center">
                        <!-- <a href="#" class="m-1 w-12 text-center text-gray-800"><i class="fas fa-chevron-left"></i></a> -->
                        <a href="#" class="m-1 w-12 text-center text-sm text-white bg-orange-500 px-2 py-1">-0.75</a>
                        <a href="#" class="m-1 w-12 text-center text-sm text-gray-800 bg-gray-200 px-2 py-1 hover:text-white hover:bg-orange-500">-0.50</a>
                        <a href="#" class="m-1 w-12 text-center text-sm text-gray-800 bg-gray-200 px-2 py-1 hover:text-white hover:bg-orange-500">-0.25</a>
                        <a href="#" class="m-1 w-12 text-center text-sm text-gray-800 bg-gray-200 px-2 py-1 hover:text-white hover:bg-orange-500">0</a>
                        <a href="#" class="m-1 w-12 text-center text-sm text-gray-800 bg-gray-200 px-2 py-1 hover:text-white hover:bg-orange-500">0.25</a>
                        <a href="#" class="m-1 w-12 text-center text-sm text-gray-800 bg-gray-200 px-2 py-1 hover:text-white hover:bg-orange-500">0.50</a>
                        <a href="#" class="m-1 w-12 text-center text-sm text-gray-800 bg-gray-200 px-2 py-1 hover:text-white hover:bg-orange-500">0.75</a>
                        <!-- <a href="#" class="m-1 w-12 text-center text-gray-800"><i class="fas fa-chevron-right"></i></a> -->
                    </div>
                </div>
                <div class="flex flex-col bg-white shadow shadow-xl py-8 px-3">
                    <div class="flex justify-between items-center p-1">
                        <span class="w-1/4"></span>
                        <span class="w-1/4 text-sm font-bold">Min</span>
                        <span class="w-1/4 text-sm font-bold">Max</span>
                        <span class="w-1/4 text-sm font-bold">Price</span>
                    </div>
                    <div class="flex justify-between items-center p-1">
                        <span class="w-1/4 text-sm font-bold">Singbet</span>
                        <span class="w-1/4 text-sm">6.90</span>
                        <span class="w-1/4 text-sm">6.90</span>
                        <span class="w-1/4 text-sm">6.90</span>
                    </div>
                    <div class="flex justify-between items-center p-1">
                        <span class="w-1/4 text-sm font-bold">ISN</span>
                        <span class="w-1/4 text-sm">6.90</span>
                        <span class="w-1/4 text-sm">6.90</span>
                        <span class="w-1/4 text-sm">6.90</span>
                    </div>
                    <div class="flex justify-between items-center p-1">
                        <span class="w-1/4 text-sm font-bold">PIN</span>
                        <span class="w-1/4 text-sm">6.90</span>
                        <span class="w-1/4 text-sm">6.90</span>
                        <span class="w-1/4 text-sm">6.90</span>
                    </div>
                    <div class="flex justify-between items-center p-1">
                        <span class="w-1/4 text-sm font-bold">ISC</span>
                        <span class="w-1/4 text-sm">6.90</span>
                        <span class="w-1/4 text-sm">6.90</span>
                        <span class="w-1/4 text-sm">6.90</span>
                    </div>
                    <div class="flex justify-between items-center p-1">
                        <span class="w-1/4 text-sm font-bold">SBC</span>
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
</template>

<script>
export default {
    data() {
        return {
            /* hard coded data for UI purposes, data should come from either API or Socket */
            stake: '',
            price: 6.99,
            orderExpiry: 'Now',
            betType: '1'
        }
    },
    methods: {
        openOddsHistory() {
            let x = (screen.width / 2) - (400 / 2)
            let y = (screen.height / 2) - (300 / 2)
            window.open(`${process.env.MIX_APP_URL}/#/odds-history/${this.$route.params.market_id}`, `order`, `width=400, height=300, top=${y}, left=${x}`)
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
</style>
