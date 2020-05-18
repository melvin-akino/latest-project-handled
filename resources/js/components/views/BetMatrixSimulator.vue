<template>
    <div class="container mx-auto mt-4 mb-8 betMatrixSimulator">
        <h3 class="text-base text-gray-700">Bet Matrix Simulator</h3>
        <form @submit.prevent="simulate" class="mt-4">
            <div class="mb-4">
                <label class="block capitalize text-gray-700 text-sm">Stake</label>
                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="matrix_input.stake" @keyup="clearMatrixTable">
            </div>
            <div class="flex mb-4">
                <div class="w-1/2 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Odds</label>
                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="matrix_input.odds" @keyup="clearMatrixTable">
                </div>
                <div class="w-1/2">
                    <label class="block capitalize text-gray-700 text-sm">Points</label>
                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="matrix_input.points" @keyup="clearMatrixTable">
                </div>
            </div>
            <div class="flex mb-4">
                <div class="w-1/2 mr-6">
                    <label class="block capitalize text-gray-700 text-sm">Home Score</label>
                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="matrix_input.home_score" @keyup="clearMatrixTable">
                </div>
                <div class="w-1/2">
                    <label class="block capitalize text-gray-700 text-sm">Away Score</label>
                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="matrix_input.away_score" @keyup="clearMatrixTable">
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-4 py-2">Simulate</button>
            </div>
        </form>
        <div class="matrixTable mt-8" v-if="matrix_table.length != 0">
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
            matrix_table: [],
            matrix_input: {
                stake: null,
                odds: null,
                points: null,
                home_score: null,
                away_score: null
            }
        }
    },
    computed: {
        towin() {
            return Number(this.matrix_input.stake * this.matrix_input.odds)
        },
        halfwin() {
            return this.towin / 2
        },
        halflose() {
            return Number(this.matrix_input.stake / 2)
        },
        matrix_data() {
            let matrix_data = {}
            Object.keys(this.matrix_input).map(key => {
                this.$set(matrix_data, key, Number(this.matrix_input[key]))
            })
            return matrix_data
        }
    },
    methods: {
        simulate() {
            var bet_team_counter = 0;
            while(bet_team_counter <= 10) {
                var table = []
                var against_team_counter = 0;
                while(against_team_counter <= 10) {
                    let difference = (this.matrix_data.points + bet_team_counter) - against_team_counter
                    if(difference > 0) {
                        if(difference == 0.25 || difference == 0.75) {
                            var result = this.halfwin
                            var color = 'lightgreen'
                        } else {
                            var result = this.towin
                            var color = 'green'
                        }
                    } else if(difference < 0) {
                        if(difference == -0.25 || difference == -0.75) {
                            var result = this.halflose * -1
                            var color = 'lightred'
                        } else {
                            var result = this.matrix_data.stake * -1
                            var color = 'red'
                        }
                    } else {
                        var result = 'Push'
                        var color = 'white'
                    }

                    if(against_team_counter < this.matrix_data.away_score || bet_team_counter < this.matrix_data.home_score) {
                        var color = 'grey'
                    }
                    table.push({ 'color': color, 'result': twoDecimalPlacesFormat(result) })
                    against_team_counter++
                }
                this.matrix_table.push(table)
                bet_team_counter++
            }
        },
        clearMatrixTable() {
            this.matrix_table = []
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
