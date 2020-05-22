<template>
    <div class="container mx-auto mt-4 mb-8 betMatrixSimulator">
        <h3 class="text-base text-gray-700">Bet Matrix Simulator</h3>
        <form @submit.prevent="processMatrixJson" class="mt-4">
            <div class="flex mb-4">
                <div class="w-1/2 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Home Score</label>
                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="home_score" @keyup="clearMatrixTable">
                </div>
                <div class="w-1/2">
                    <label class="block capitalize text-gray-700 text-sm">Away Score</label>
                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="away_score" @keyup="clearMatrixTable">
                </div>
            </div>
            <div class="mb-4">
                <label class="block capitalize text-gray-700 text-sm">Event Orders:</label>
                <textarea rows="10" cols="100" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="matrix_json" @keyup="clearMatrixTable"></textarea>
                <span class="text-red-600 text-sm">{{error}}</span>
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
                <div class="w-full p-1 text-center text-white border border-white" :class="{'grey': data.color=='grey', 'green': data.color=='green', 'lightgreen': data.color=='lightgreen', 'red': data.color=='red', 'lightred': data.color=='lightred', 'white': data.color=='white'}" v-for="(data, index) in matrix" :key="index">
                    {{data.result}}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { twoDecimalPlacesFormat } from '../../helpers/numberFormat'

export default {
    name: 'BetMatrixSimulator',
    data() {
        return {
            matrix_json: '[{"stake": "50.00", "odds": "1.99", "points": "-0.5", "type": "HDP", "bet_team": "HOME"}]',
            matrix_orders: [],
            matrix_table: [],
            error: '',
            showMatrixTable: false,
            home_score: 0,
            away_score: 0
        }
    },
    mounted() {
        this.processMatrixJson()
    },
    methods: {
        processMatrixJson() {
            try {
                this.matrix_orders = JSON.parse(this.matrix_json)
                this.simulate()
            } catch(err) {
                this.error = 'Invalid JSON input: Check for missing quotes in key/value pairs.'
            }
        },
        simulate() {
            let totalStake = 0
            let totalTowin = 0
            this.matrix_orders.forEach(order => {
                if(typeof(order.stake) != "undefined" && typeof(order.odds) != "undefined" && typeof(order.points) != "undefined" && typeof(order.type) != "undefined" && typeof(order.bet_team) != "undefined") {
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
                            var difference = 0
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

                            if(away_team_counter < this.away_score || home_team_counter < this.home_score) {
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
                    this.showMatrixTable = true
                } else {
                    this.error = 'Please check your JSON input if it has a stake, odds, points and type value. The simulated bet matrix may be inaccurate due to missing required parameters.'
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
        clearMatrixTable() {
            this.matrix_table = []
            this.matrix_orders = []
            this.showMatrixTable = false
            this.error = ''
        }
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
</style>
