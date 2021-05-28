<template>
    <div class="container mx-auto mt-4 mb-8 betMatrixSimulator">
        <h3 class="text-base text-gray-700">Bet Matrix Simulator</h3>
        <form @submit.prevent="simulate" class="mt-4">
            <div class="flex mb-4">
                <div class="w-1/3">
                    <div class="mb-4">
                        <label class="block capitalize text-gray-700 text-sm">Home Score</label>
                        <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="$v.matrix_data.home_score.$model" @keyup="clearMatrixTable">
                        <span class="text-red-600 text-xs" v-if="$v.matrix_data.home_score.$dirty && !$v.matrix_data.home_score.required">Home score is required.</span>
                        <span class="text-red-600 text-xs" v-if="$v.matrix_data.home_score.$dirty && !$v.matrix_data.home_score.integer">Home score should be an integer value.</span>
                    </div>
                    <div>
                        <label class="block capitalize text-gray-700 text-sm">Away Score</label>
                        <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="$v.matrix_data.away_score.$model" @keyup="clearMatrixTable">
                        <span class="text-red-600 text-xs" v-if="$v.matrix_data.away_score.$dirty && !$v.matrix_data.away_score.required">Away score is required.</span>
                        <span class="text-red-600 text-xs" v-if="$v.matrix_data.away_score.$dirty && !$v.matrix_data.away_score.integer">Away score should be an integer value.</span>
                    </div>
                </div>
                <div class="w-1/3"></div>
                <div class="flex flex-col justify-end items-end w-1/3">
                    <button type="button" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-4 py-2" @click="addMatrixOrder"><span class="text-xs"><i class="fas fa-plus"></i></span> Add Bet</button>
                </div>
            </div>
            <div class="mb-4">
                <div class="flex justify-center py-2 pl-2 ordersHeading">
                    <span class="w-64 text-sm text-white">Stake</span>
                    <span class="w-64 text-sm text-white">Price (Odds)</span>
                    <span class="w-64 text-sm text-white">Points (HDP/OU)</span>
                    <span class="w-64 text-sm text-white">Type</span>
                    <span class="w-64 text-sm text-white">Bet Team</span>
                    <span class="w-64 text-sm text-white">Home Score</span>
                    <span class="w-64 text-sm text-white">Away Score</span>
                    <span class="w-20"></span>
                </div>
                <div v-if="matrix_data.matrix_orders.length == 0">
                    <p class="text-gray-700 m-4 text-center">Add a bet to simulate bet matrix.</p>
                </div>
                <div class="betMatrixRows" v-else>
                    <div class="flex justify-center py-2" v-for="(order, index) in $v.matrix_data.matrix_orders.$each.$iter" :key="index">
                        <div class="w-64 mr-2">
                            <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="order.stake.$model" @keyup="clearMatrixTable">
                            <span class="text-red-600 text-xs" v-if="order.stake.$dirty && !order.stake.required">Stake is required.</span>
                            <span class="text-red-600 text-xs" v-if="order.stake.$dirty && !order.stake.decimal">Stake should be a numeric/decimal value.</span>
                        </div>
                        <div class="w-64 mr-2">
                            <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="order.odds.$model" @keyup="clearMatrixTable">
                            <span class="text-red-600 text-xs" v-if="order.odds.$dirty && !order.odds.required">Odds is required.</span>
                            <span class="text-red-600 text-xs" v-if="order.odds.$dirty && !order.odds.decimal">Odds should be a numeric/decimal value.</span>
                        </div>
                        <div class="w-64 mr-2">
                            <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700
                            text-sm leading-tight focus:outline-none" v-model="order.points.$model" @keyup="clearMatrixTable" :disabled="order.type.$model == '1x2' || order.type.$model == 'Odd' || order.type.$model == 'Even'">
                            <span class="text-red-600 text-xs" v-if="order.points.$dirty && !order.points.required">Points is required.</span>
                            <span class="text-red-600 text-xs" v-if="order.points.$dirty && !order.points.decimal">Points should be a numeric/decimal value.</span>
                        </div>
                        <div class="w-64 mr-2">
                            <div class="relative">
                                <select class="text-sm shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" v-model="order.type.$model" @change="clearMatrixTable">
                                    <option :value="null">Select Type</option>
                                    <option value="1x2">1x2</option>
                                    <option value="HDP">HDP</option>
                                    <option value="O">O</option>
                                    <option value="U">U</option>
                                    <option value="Odd">Odd</option>
                                    <option value="Even">Even</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                            <span class="text-red-600 text-xs" v-if="order.type.$dirty && !order.type.required">Type is required.</span>
                        </div>
                        <div class="w-64 mr-2">
                            <div class="relative">
                                <select class="text-sm shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" v-model="order.bet_team.$model" @change="clearMatrixTable">
                                    <option :value="null">Select Team</option>
                                    <option value="HOME">HOME</option>
                                    <option value="AWAY">AWAY</option>
                                    <option v-if="order.type.$model == '1x2'" value="DRAW">DRAW</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                            <span class="text-red-600 text-xs" v-if="order.bet_team.$dirty && !order.bet_team.required">Bet team is required.</span>
                        </div>
                        <div class="w-64 mr-2">
                            <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="order.home_score_on_bet.$model" @keyup="clearMatrixTable">
                            <span class="text-red-600 text-xs" v-if="order.home_score_on_bet.$dirty && !order.home_score_on_bet.required">Home score is required.</span>
                            <span class="text-red-600 text-xs" v-if="order.home_score_on_bet.$dirty && !order.home_score_on_bet.integer">Home score should be an integer.</span>
                        </div>
                        <div class="w-64 mr-2">
                            <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="order.away_score_on_bet.$model" @keyup="clearMatrixTable">
                            <span class="text-red-600 text-xs" v-if="order.away_score_on_bet.$dirty && !order.away_score_on_bet.required">Away score is required.</span>
                            <span class="text-red-600 text-xs" v-if="order.away_score_on_bet.$dirty && !order.away_score_on_bet.integer">Away score should be an integer.</span>
                        </div>
                        <div class="w-20">
                            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-xs uppercase ml-2 px-3 py-2 rounded-lg" @click="removeMatrixOrder(index)"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-4 py-2">Simulate</button>
            </div>
        </form>
        <div class="matrixTable mt-8" v-if="showMatrixTable">
            <div class="flex items-center bg-black text-white">
                <i class="w-full text-center material-icons sportsIcon">sports_soccer</i>
                <div class="w-full p-1 text-center" v-for="(matrix, index) in matrix_table" :key="index">
                    {{index}}
                </div>
            </div>
            <div class="flex items-center" v-for="(matrix, index) in matrix_table" :key="index">
                <span class="w-full label block p-1 text-center bg-black text-white">{{index}}</span>
                <div class="w-full p-1 text-center text-white border border-white" :class="{'grey': data.color=='grey', 'green': data.color=='green', 'lightgreen': data.color=='lightgreen', 'red': data.color=='red', 'lightred': data.color=='lightred', 'white': data.color=='white', 'font-bold': data.highlight}" v-for="(data, index) in matrix" :key="index">
                    {{data.result | twoDecimalPlacesFormat}}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { twoDecimalPlacesFormat } from '../../helpers/numberFormat'
import { required, requiredIf, decimal, integer } from 'vuelidate/lib/validators'

export default {
    name: 'BetMatrixSimulator',
    head: {
        title() {
            return {
                inner: 'Bet Matrix Simulator'
            }
        }
    },
    data() {
        return {
            matrix_table: [],
            showMatrixTable: false,
            matrix_data: {
                home_score: 0,
                away_score: 0,
                matrix_orders: [{stake: 50.00, odds: 1, points: -0.5, type: "HDP", bet_team: "HOME", home_score_on_bet: 0, away_score_on_bet: 0}],
            }
        }
    },
    validations: {
        matrix_data: {
            required,
            home_score: { required, integer },
            away_score: { required, integer },
            matrix_orders: {
                required,
                $each: {
                    stake: { required, decimal },
                    odds: { required, decimal },
                    points: {
                        required: requiredIf(function(value) {
                            return value.type == 'HDP' || value.type == 'O' || value.type == 'U'
                        }),
                        decimal
                    },
                    type: { required },
                    bet_team: { required },
                    home_score_on_bet: {  required, integer },
                    away_score_on_bet: {  required, integer },
                }
            }
        }
    },
    mounted() {
        this.simulate()
    },
    methods: {
        computeHomeDifference(points, home_score_on_bet, away_score_on_bet, home_team_counter, away_team_counter) {
            return (points + (home_team_counter - home_score_on_bet)) - (away_team_counter - away_score_on_bet)
        },
        computeAwayDifference(points, home_score_on_bet, away_score_on_bet, home_team_counter, away_team_counter) {
            return (points + (away_team_counter - away_score_on_bet)) - (home_team_counter - home_score_on_bet)
        },
        generateBetMatrix() {
            let totalStake = 0
            let totalTowin = 0
            this.matrix_data.matrix_orders.forEach(order => {
                let stake = Number(order.stake)
                let price = Number(order.odds)
                let towin = order.type == '1x2' || order.type == 'Odd' || order.type == 'Even'  ? stake * (price - 1) : stake * price
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
                        var difference = 0
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
                this.showMatrixTable = true
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
        simulate() {
            if(this.$v.matrix_data.$invalid) {
                this.$v.matrix_data.home_score.$touch()
                this.$v.matrix_data.away_score.$touch()
                Object.keys(this.$v.matrix_data.matrix_orders.$each.$iter).map(order => {
                    this.$v.matrix_data.matrix_orders.$each.$iter[order].stake.$touch()
                    this.$v.matrix_data.matrix_orders.$each.$iter[order].odds.$touch()
                    this.$v.matrix_data.matrix_orders.$each.$iter[order].points.$touch()
                    this.$v.matrix_data.matrix_orders.$each.$iter[order].type.$touch()
                    this.$v.matrix_data.matrix_orders.$each.$iter[order].bet_team.$touch()
                })
            } else {
                this.generateBetMatrix()
            }
        },
        addMatrixOrder() {
            this.clearMatrixTable()
            this.matrix_data.matrix_orders.push({
                stake: '',
                odds: '',
                points: '',
                type: null,
                bet_team: null
            })
        },
        removeMatrixOrder(index) {
            this.matrix_data.matrix_orders.splice(index, 1)
            this.matrix_table = []
            this.simulate()

            if(this.matrix_data.matrix_orders.length == 0) {
                this.showMatrixTable = false
            }
        },
        clearMatrixTable() {
            this.matrix_table = []
            this.showMatrixTable = false
        }
    },
    filters: {
        twoDecimalPlacesFormat
    }
}
</script>

<style lang="scss">
    .betMatrixSimulator {
      input:not([disabled]), select {
        background-color: #ffffff !important;
        border-style: solid !important;
      }
      select {
        appearance: none !important;
      }
      button, button[type=submit] {
        color: #ffffff !important;
      }
    }

    .ordersHeading {
        background-color: #ed8936;
        color: #ffffff;
        font-size:14px;
    }
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
        height: 34px;
    }

    .white {
        background-color: #fefefe;
        color: #000000 !important;
    }
</style>
