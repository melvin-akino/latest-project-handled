<template>
    <div class="betMatrix text-sm">
        <dialog-drag title="Bet Matrix" :options="options" @close="$emit('close')" @mousedown.native="$store.dispatch('trade/setActivePopup', $vnode.key)" v-bet-matrix="activePopup==$vnode.key">
            <div class="text-center text-sm m-4" v-if="isLoadingBetMatrixOrders">
                <span class="text-gray-700">Loading bet matrix orders <i class="fas fa-circle-notch fa-spin"></i></span>
            </div>
            <div class="p-6" v-else>
                <p class="text-gray-700 mb-4">Current Score: {{matrix_data.home_score}} - {{matrix_data.away_score}}</p>
                <div class="matrixTable" v-if="selectedOrders.length != 0">
                    <div class="flex items-center bg-black text-white pl-4">
                        <i class="material-icons sportsIcon pr-3">sports_soccer</i>
                        <div class="result p-1 text-center" v-for="(matrix, index) in matrix_table" :key="index">
                            {{index}}
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center" v-for="(matrix, index) in matrix_table" :key="index">
                        <span class="w-12 label block p-1 text-center bg-black text-white">{{index}}</span>
                        <div class="result p-1 text-center text-white border border-white" :class="{'grey': data.color=='grey', 'green': data.color=='green', 'lightgreen': data.color=='lightgreen', 'red': data.color=='red', 'lightred': data.color=='lightred', 'white': data.color=='white', 'font-bold': data.highlight}" v-for="(data, index) in matrix" :key="index">
                            {{data.result | twoDecimalPlacesFormat }}
                        </div>
                    </div>
                </div>
                <div v-else>
                    <p class="text-gray-700 mb-4">No order selected. Please select an order to generate bet matrix.</p>
                </div>
                <div class="flex items-center bg-black text-white p-1">
                    <span class="w-64">Bet Type</span>
                    <span class="w-32">Selection</span>
                    <span class="w-32">Price</span>
                    <span class="w-32">Stake</span>
                    <span class="w-32">Score on Bet</span>
                    <span class="w-40">Order Date</span>
                </div>
                <div class="bets">
                    <div class="flex items-center text-gray-700 p-1 my-1 cursor-pointer" v-for="order in matrix_orders_list" :key="order.order_id">
                        <div class="w-64">
                            <label class="text-gray-500 font-bold">
                                <input class="mr-2 leading-tight" type="checkbox" @change="toggleEventOrder(order, order.order_id)" :checked="selectedOrders.includes(order.order_id)">
                            </label>
                            {{ (order.odd_type.includes("OU") || order.odd_type.includes("OE")) && order.odd_type_name.includes("FT") ? "FT " : ((order.odd_type.includes("OU") || order.odd_type.includes("OE")) && order.odd_type_name.includes("HT") ? "HT " : "") }} {{order.team_name}} {{ order.type == 'HDP' || order.type == '1x2' ? order.odd_type_name : ''}} {{order.points}} {{`(${defaultPriceFormat})`}}
                        </div>
                        <span class="w-32">{{order.bet_team}}</span>
                        <span class="w-32">{{order.odds}}</span>
                        <span class="w-32">{{wallet.currency_symbol}} {{Number(order.stake) | moneyFormat}}</span>
                        <span class="w-32">{{order.home_score_on_bet}} - {{order.away_score_on_bet}}</span>
                        <span class="w-40">{{order.created_at}}</span>
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
    props: ['market_id', 'event_id'],
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
            current_score: '',
            matrix_data: {
                home_score: 0,
                away_score: 0,
            },
            matrix_orders_list: [],
            matrix_orders: [],
            selectedOrders: [],
            isLoadingBetMatrixOrders: true
        }
    },
    computed: {
        ...mapState('trade', ['wallet', 'activePopup', 'popupZIndex']),
        ...mapState('settings', ['defaultPriceFormat']),
    },
    mounted() {
        this.getBetMatrixOrders()
    },
    methods: {
        getBetMatrixOrders() {
            let token = Cookies.get('mltoken')

            axios.get('v1/orders/bet-matrix', { params: { event_id: this.event_id, market_id: this.market_id }, headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                let { data, current_score } = response.data
                this.isLoadingBetMatrixOrders = false
                this.current_score = current_score
                this.matrix_data.home_score = Number(current_score.home)
                this.matrix_data.away_score = Number(current_score.away)
                this.matrix_orders_list = data
                this.matrix_orders = data
                this.selectedOrders = data.map(order => order.order_id)
                this.generateBetMatrix()
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        },
        computeHomeDifference(points, home_score_on_bet, away_score_on_bet, home_team_counter, away_team_counter) {
            return (points + (home_team_counter - home_score_on_bet)) - (away_team_counter - away_score_on_bet)
        },
        computeAwayDifference(points, home_score_on_bet, away_score_on_bet, home_team_counter, away_team_counter) {
            return (points + (away_team_counter - away_score_on_bet)) - (home_team_counter - home_score_on_bet)
        },
        generateBetMatrix() {
            let totalStake = 0
            let totalTowin = 0
            this.matrix_orders.forEach(order => {
                let stake = Number(order.stake)
                let price = Number(order.odds)
                let towin = order.type == '1x2' || order.type == 'Odd' || order.type == 'Even' ? stake * (price - 1) : stake * price
                let points = Number(order.points)
                let home_score_on_bet = Number(order.home_score_on_bet)
                let away_score_on_bet = Number(order.away_score_on_bet)
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
                                var difference = this.computeHomeDifference(points, home_score_on_bet, away_score_on_bet, home_team_counter, away_team_counter)
                            } else {
                                var difference = this.computeAwayDifference(points, home_score_on_bet, away_score_on_bet, home_team_counter, away_team_counter)
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
                            var teamTotalsDifference = (home_team_counter + away_team_counter) - points
                            if(teamTotalsDifference > 0.25) {
                                var result = stake * price
                            } else if(teamTotalsDifference == 0.25) {
                                var result = (stake * price) / 2
                            } else if(teamTotalsDifference == 0) {
                                var result = 0
                            } else if(teamTotalsDifference == -0.25) {
                                var result = (stake / 2) * -1
                            } else {
                                var result = stake * -1
                            }
                        }
                        if(type == 'U') {
                            var teamTotalsDifference = (home_team_counter + away_team_counter) - points
                            if(teamTotalsDifference > 0.25) {
                                var result = stake * -1
                            } else if(teamTotalsDifference == 0.25) {
                                var result = (stake / 2) * -1
                            } else if(teamTotalsDifference == 0) {
                                var result = 0
                            } else if(teamTotalsDifference == -0.25) {
                                var result = (stake * price) / 2
                            } else {
                                var result = stake * price
                            }
                        }
                        if(type == 'Odd') {
                            if((home_team_counter + away_team_counter) % 2 != 0) {
                                var result = stake * (price - 1)
                            } else {
                                var result = stake * -1
                            }
                        }
                        if(type == 'Even') {
                            if((home_team_counter + away_team_counter) % 2 == 0) {
                                var result = stake * (price - 1)
                            } else {
                                var result = stake * -1
                            }
                        }
                        if(type == '1x2') {
                            if(bet_team == 'HOME') {
                                if(home_team_counter > away_team_counter) {
                                    var result = stake * (price - 1)
                                } else {
                                    var result = stake * -1
                                }
                            } else if(bet_team == 'AWAY') {
                                if(home_team_counter < away_team_counter) {
                                    var result = stake * (price - 1)
                                } else {
                                    var result = stake * -1
                                }
                            } else {
                                if(home_team_counter == away_team_counter) {
                                    var result = stake * (price - 1)
                                } else {
                                    var result = stake * -1
                                }
                            }
                        }

                        if(away_team_counter < this.matrix_data.away_score || home_team_counter < this.matrix_data.home_score) {
                            var color = 'grey'
                            var result = ''
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
            this.matrix_table.map((row, rowIndex) => {
                row.map((col, colIndex) => {
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
                    if(rowIndex == this.matrix_data.home_score && colIndex == this.matrix_data.away_score) {
                        col.highlight = true
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

            if(this.matrix_orders.length == 1) {
                if(this.matrix_orders[0].final_score) {
                    let final_score = this.matrix_orders[0].final_score.split(' - ')
                    this.matrix_data.home_score = final_score[0]
                    this.matrix_data.away_score = final_score[1]
                } else {
                    this.matrix_data.home_score = this.current_score.home
                    this.matrix_data.away_score = this.current_score.away
                }
            } else {
                this.matrix_data.home_score = this.current_score.home
                this.matrix_data.away_score = this.current_score.away
            }

            this.matrix_table = []
            this.generateBetMatrix()
        }
    },
    directives: {
        betMatrix: {
            bind(el, binding, vnode) {
                let { $set, options, popupZIndex } = vnode.context
                $set(options, 'top', window.innerHeight / 2)
                $set(options, 'left', window.innerWidth / 2)
                el.style.zIndex = popupZIndex
            },
            componentUpdated(el, binding, vnode) {
                if(binding.value) {
                    el.style.zIndex = vnode.context.popupZIndex
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
        height: 31px;
    }

    .white {
        background-color: #fefefe;
        color: #000000 !important;
    }

    .result {
        width: 70px;
    }

    .bets {
        max-height:160px;
        overflow-y: auto;
    }
</style>
