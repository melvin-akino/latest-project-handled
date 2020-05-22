<template>
    <div class="betMatrix text-sm">
        <dialog-drag title="Bet Matrix" :options="options" @close="$emit('close')" v-bet-matrix="activeBetSlip==market_id">
            <div class="text-center text-sm m-4" v-if="isLoadingBetMatrixOrders">
                <span class="text-gray-700">Loading bet matrix orders <i class="fas fa-circle-notch fa-spin"></i></span>
            </div>
            <div class="p-6" v-else>
                <p class="text-gray-700 mb-4">Current Score: {{analysisData.home_score}} - {{analysisData.away_score}}</p>
                <div class="matrixTable" v-if="selectedOrders.length != 0">
                    <div class="flex items-center bg-black text-white pl-4">
                        <i class="material-icons sportsIcon pr-3">sports_soccer</i>
                        <div class="result p-1 text-center" v-for="(matrix, index) in matrix_table" :key="index">
                            {{index}}
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center" v-for="(matrix, index) in matrix_table" :key="index">
                        <span class="w-12 label block p-1 text-center bg-black text-white">{{index}}</span>
                        <div class="result p-1 text-center text-white border border-white" :class="{'grey': data.color=='grey', 'green': data.color=='green', 'lightgreen': data.color=='lightgreen', 'red': data.color=='red', 'lightred': data.color=='lightred', 'white': data.color=='white'}" v-for="(data, index) in matrix" :key="index">
                            {{data.result | twoDecimalPlacesFormat }}
                        </div>
                    </div>
                </div>
                <div v-else>
                    <p class="text-gray-700 mb-4">No order selected. Please select an order to generate bet matrix.</p>
                </div>
                <div class="flex items-center bg-black text-white p-1 pl-4">
                    <span class="w-1/6"></span>
                    <span class="w-2/6">Bet Type</span>
                    <span class="w-1/6">Price</span>
                    <span class="w-1/6">Stake</span>
                    <span class="w-1/6">Order Date</span>
                </div>
                <div class="bets">
                    <div class="flex items-center text-gray-700 text-white p-1 my-1 cursor-pointer" v-for="order in matrix_orders_list" :key="order.order_id">
                        <div class="w-3/6">
                            <label class="text-gray-500 font-bold">
                                <input class="mr-2 leading-tight" type="checkbox" @change="toggleEventOrder(order, order.order_id)" :checked="selectedOrders.includes(order.order_id)">
                            </label>
                            {{analysisData.bet_team}} {{order.type}} {{order.points}} {{`(${analysisData.price_format})`}}
                        </div>
                        <span class="w-1/6">{{order.odds}}</span>
                        <span class="w-1/6">{{analysisData.currency_symbol}} {{Number(order.stake) | moneyFormat}}</span>
                        <span class="w-1/6">{{order.created_at}}</span>
                    </div>
                </div>
            </div>
        </dialog-drag>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import DialogDrag from 'vue-dialog-drag'
import Cookies from 'js-cookie'
import { twoDecimalPlacesFormat, convertPointAsNumeric, moneyFormat } from '../../../helpers/numberFormat'

export default {
    props: ['market_id', 'analysisData', 'event_id'],
    components: {
        DialogDrag
    },
    data() {
        return {
            options: {
                width: 868,
                buttonPin: false,
            },
            matrix_table: [],
            matrix_data: {
                stake: Number(this.analysisData.stake),
                price: Number(this.analysisData.price),
                home_score: Number(this.analysisData.home_score),
                away_score: Number(this.analysisData.away_score),
                points: convertPointAsNumeric(this.analysisData.points, this.analysisData.odd_type),
                created_at: this.analysisData.created_at
            },
            matrix_orders_list: [],
            matrix_orders: [],
            selectedOrders: [],
            isLoadingBetMatrixOrders: true
        }
    },
    computed: {
        ...mapState('trade', ['activeBetSlip']),
    },
    watch: {
        analysisData() {
            this.matrix_table = []
            this.generateBetMatrix()
        }
    },
    mounted() {
        this.getBetMatrixOrders()
    },
    methods: {
        getBetMatrixOrders() {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/bet-matrix/${this.event_id}`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.isLoadingBetMatrixOrders = false
                this.matrix_orders_list = response.data.data
                this.matrix_orders = response.data.data
                this.selectedOrders = response.data.data.map(order => order.order_id)
                this.generateBetMatrix()
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        },
        generateBetMatrix() {
            let totalStake = 0
            let totalTowin = 0
            this.matrix_orders.forEach(order => {
                let stake = Number(order.stake)
                let price = Number(order.odds)
                let towin = Number(order.stake) * Number(order.odds)
                let points = Number(order.points)
                let type = order.type
                let bet_team = order.bet_team
                totalStake += stake
                totalTowin += towin
                var home_team_counter = 0;
                while(home_team_counter <= 10) {
                    var away_team_counter = 0;
                    while(away_team_counter <= 10) {
                        var result = 0
                        var color = ''
                        if(type == 'HDP') {
                            if(bet_team == 'HOME') {
                                var difference = (points + home_team_counter) - away_team_counter
                            } else {
                                var difference = (points + away_team_counter) - home_team_counter
                            }

                            if(difference > 0.25) {
                                var result = stake * price
                            } else if(difference == 0.25) {
                                var result = (stake * price) / 2
                            } else if(difference == 0) {
                                var result = 0
                            } else if(difference == -0.25) {
                                var result = (stake / 2) * -1
                            } else {
                                var result = stake * -1
                            }
                        }
                        if(type == 'O') {
                            var teamTotals = home_team_counter + away_team_counter
                            if(teamTotals > points) {
                                var result = stake * price
                            } else {
                                var result = stake * -1
                            }
                        }
                        if(type == 'U') {
                            var teamTotals = home_team_counter + away_team_counter
                            if(teamTotals < points) {
                                var result = stake * price
                            } else {
                                var result = stake * -1
                            }
                        }

                        if(away_team_counter < this.matrix_data.away_score || home_team_counter < this.matrix_data.home_score) {
                            var color = 'grey'
                        }

                        if(typeof(this.matrix_table[home_team_counter])=="undefined") {
                            this.matrix_table[home_team_counter] = []
                        }
                        if(typeof(this.matrix_table[home_team_counter][away_team_counter])=="undefined") {
                            this.matrix_table[home_team_counter][away_team_counter] = {}
                        }
                        if(typeof(this.matrix_table[home_team_counter][away_team_counter]['result'])=="undefined") {
                            this.matrix_table[home_team_counter][away_team_counter]['result'] = ''
                        }
                        if(typeof(this.matrix_table[home_team_counter][away_team_counter]['color'])=="undefined") {
                            this.matrix_table[home_team_counter][away_team_counter]['color'] = ''
                        }
                        if(this.matrix_table[home_team_counter][away_team_counter]['result'] != '') {
                            this.matrix_table[home_team_counter][away_team_counter]['result'] += result
                            this.matrix_table[home_team_counter][away_team_counter]['color'] = color
                        } else {
                            this.matrix_table[home_team_counter][away_team_counter]['result'] = result
                            this.matrix_table[home_team_counter][away_team_counter]['color'] = color
                        }
                        away_team_counter++
                    }
                    home_team_counter++
                }
            })
            this.matrix_table.map(row => {
                row.map(col => {
                    if(col.color == '') {
                        if(col.result == totalTowin) {
                            col.color = 'green'
                        } else if(col.result > 0 && col.result < totalTowin) {
                            col.color ='lightgreen'
                        } else if(col.result < 0 && col.result == (totalStake * -1)) {
                            col.color = 'red'
                        } else if(col.result < 0 == col.result > (totalStake * -1)) {
                            col.color = 'lightred'
                        } else {
                            col.color = 'white'
                        }
                    }
                })
            })
        },
        toggleEventOrder(order, order_id) {
            if(this.selectedOrders.includes(order_id)) {
                this.selectedOrders = this.selectedOrders.filter(id => id != order_id)
                this.matrix_orders = this.matrix_orders.filter(order => order.order_id != order_id)
            } else {
                this.selectedOrders.push(order_id)
                this.matrix_orders.push(order)
            }
            this.matrix_table = []
            this.generateBetMatrix()
        }
    },
    directives: {
        betMatrix: {
            bind(el, binding, vnode) {
                let { $set, options } = vnode.context
                $set(options, 'top', window.innerHeight / 2)
                $set(options, 'left', window.innerWidth / 2)
            },
            componentUpdated(el, binding, vnode) {
                if(binding.value) {
                    el.style.zIndex = '151'
                } else {
                    el.style.zIndex = '102'
                }
                el.style.marginTop = 'calc(567px / 2 * -1)'
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
    .green {
        background-color: #006400;
    }

    .lightgreen {
        background-color: #4cbb17;
    }

    .red {
        background-color: #8b0000;
    }

    .lightred {
        background-color: #ff0000;
    }

    .grey {
        background-color: #aaaaaa;
    }

    .white {
        background-color: #fefefe;
        color: #000000
    }

    .result {
        width: 70px;
    }
</style>
