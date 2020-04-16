<template>
    <div class="betMatrix">
        <dialog-drag title="Bet Matrix" :options="options" @close="$emit('close')" v-overlap-all-bet-matrix="activeBetSlip==market_id">
            <div class="p-6">
                <p>Bet Matrix for Market ID: {{market_id}}</p>
                <p>Current Score: {{this.analysisData.bet_score}} - {{this.analysisData.against_score}}</p>
                <div class="flex items-center bg-black text-white pl-4">
                    <i class="material-icons sportsIcon pr-3">sports_soccer</i>
                    <div class="result p-1 text-center" v-for="(matrix, index) in matrix_table" :key="index">
                        {{index}}
                    </div>
                </div>
                <div class="flex flex-wrap items-center" v-for="(matrix, index) in matrix_table" :key="index">
                    <span class="w-12 label block p-1 text-center bg-black text-white">{{index}}</span>
                    <div class="result p-1 text-center text-white border border-white" :class="{'grey': data.color=='grey', 'green': data.color=='green', 'lightgreen': data.color=='lightgreen', 'red': data.color=='red', 'lightred': data.color=='lightred'}" v-for="(data, index) in matrix" :key="index">
                        {{data.result}}
                    </div>
                </div>
            </div>
        </dialog-drag>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import 'vue-dialog-drag/dist/vue-dialog-drag.css'
import DialogDrag from 'vue-dialog-drag'

export default {
    props: ['market_id', 'analysisData'],
    components: {
        DialogDrag
    },
    data() {
        return {
            options: {
                width:868,
                buttonPin: false,
                centered: "viewport"
            },
            matrix_table: []
        }
    },
    computed: {
        ...mapState('trade', ['activeBetSlip']),
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
    watch: {
        analysisData() {
            this.matrix_table = []
            this.generateBetMatrix()
        }
    },
    mounted() {
        this.generateBetMatrix()
    },
    methods: {
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
                            var result = this.analysisData.stake * -1
                            var color = 'red'
                        }

                    } else {
                        var result = 'push'
                        var color = 'white'
                    }

                    if(against_team_counter <= this.analysisData.against_score || bet_team_counter <= this.analysisData.bet_score) {
                        var color = 'grey'
                    }
                    table.push({ 'color': color, 'result': Math.floor(result) })
                    against_team_counter++
                }
                this.matrix_table.push(table)
                bet_team_counter++
            }
        }
    },
    directives: {
        overlapAllBetMatrix: {
            componentUpdated(el, binding, vnode) {
                if(binding.value) {
                    el.style.zIndex = '151'
                } else {
                    el.style.zIndex = '102'
                }
            }
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

    .result {
        width: 70px;
    }
</style>
