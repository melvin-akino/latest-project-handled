<template>
    <div class="betMatrix">
        <dialog-drag title="Bet Matrix" :options="options" @close="closeBetMatrix(odd_details.market_id)">
            <div class="p-6">
                <p>Bet Matrix for Market ID: {{odd_details.market_id}}</p>
                <p>Current Score: {{this.analysisData.bet_score}} - {{this.analysisData.against_score}}</p>
                <div class="flex items-center bg-black text-white pl-4">
                    <i class="material-icons sportsIcon pr-2">sports_soccer</i>
                    <div class="w-12 p-1 text-center" v-for="(matrix, index) in matrix_table" :key="index">
                        {{index}}
                    </div>
                </div>
                <div class="flex items-center" v-for="(matrix, index) in matrix_table" :key="index">
                    <span class="label block p-1 w-12 text-center bg-black text-white">{{index}}</span>
                    <div class="w-12 p-1 text-center text-white border border-white" :class="{'bg-gray-600': data.color=='grey', 'green': data.color=='green', 'red': data.color=='red',}" v-for="(data, index) in matrix" :key="index">
                        {{data.result}}
                    </div>
                </div>
            </div>
        </dialog-drag>
    </div>
</template>

<script>
import 'vue-dialog-drag/dist/vue-dialog-drag.css'
import DialogDrag from 'vue-dialog-drag'

export default {
    props: ['odd_details', 'points'],
    components: {
        DialogDrag
    },
    data() {
        return {
            analysisData: {
                stake: 100, //hardcoded
                price: this.odd_details.odds,
                hdp: this.points,
                bet_score: 0, //hardcoded
                against_score: 0, //hardcoded
            },
            matrixAnalysisObject: {},
            options: {
                width:600,
                buttonPin: false,
                centered: "viewport"
            },
            matrix_table: []
        }
    },
    computed: {
        towin() {
            return this.analysisData.stake * this.analysisData.price
        },
        halfwin() {
            return this.towin / 2
        },
        halflose() {
            return this.analysisData.stake / 2
        }
    },
    mounted() {
        this.generateBetMatrix()
    },
    methods: {
        closeBetMatrix(market_id) {
            this.$store.commit('trade/CLOSE_BET_MATRIX', market_id)
        },
        generateBetMatrix() {
            var bet_team_counter = 0;
            while(bet_team_counter <= 10) {
                var table = []
                var against_team_counter = 0;
                while(against_team_counter <= 10) {
                    let difference = (this.analysisData.hdp + bet_team_counter) - against_team_counter
                    if(difference > 0) {
                        if(difference == 0.25 || difference == 0.75) {
                            var result = this.halfwin
                        } else {
                            var result = this.towin
                        }
                        var color = 'green'
                    } else if(difference < 0) {
                        if(difference == -0.25 || difference == -0.75) {
                            var result = this.halflose * -1
                        } else {
                            var result = this.analysisData.stake * -1
                        }
                        var color = 'red'
                    } else {
                        var result = 'push'
                        var color = 'white'
                    }

                    if(against_team_counter <= this.analysisData.against_score || bet_team_counter <= this.analysisData.bet_score) {
                        var color = 'grey'
                    }
                    table.push({ 'color': color, 'result': Math.round(result) })
                    against_team_counter++
                }
                this.matrix_table.push(table)
                bet_team_counter++
            }
        }
    }
}
</script>

<style>
    .green {
        background-color: #006400;
    }

    .red {
        background-color: #8b0000;
    }

    .grey {
        background-color: #aaaaaa;
    }
</style>
