<template>
    <div class="betMatrix text-sm">
        <dialog-drag title="Bet Matrix" :options="options" @close="$emit('close')" v-bet-matrix="activeBetSlip==market_id">
            <div class="p-6">
                <p class="text-gray-700 mb-2">Current Score: {{analysisData.bet_score}} - {{analysisData.against_score}}</p>
                <div class="bg-gray-400 p-2">
                    <div class="container mx-auto text-sm text-gray-700">Order placed at {{analysisData.created_at}}</div>
                </div>
                <div class="flex items-center bg-black text-white pl-4">
                    <i class="material-icons sportsIcon pr-3">sports_soccer</i>
                    <div class="result p-1 text-center" v-for="(matrix, index) in matrix_table" :key="index">
                        {{index}}
                    </div>
                </div>
                <div class="flex flex-wrap items-center" v-for="(matrix, index) in matrix_table" :key="index">
                    <span class="w-12 label block p-1 text-center bg-black text-white">{{index}}</span>
                    <div class="result p-1 text-center text-white border border-white" :class="{'grey': data.color=='grey', 'green': data.color=='green', 'lightgreen': data.color=='lightgreen', 'red': data.color=='red', 'lightred': data.color=='lightred', 'white': data.color=='white'}" v-for="(data, index) in matrix" :key="index">
                        {{data.result}}
                    </div>
                </div>
                <div class="flex items-center bg-black text-white p-1 pl-4">
                    <span class="w-2/3">Bet Type</span>
                    <span class="w-1/3">Price</span>
                    <span class="w-1/3">Stake</span>
                </div>
                <div class="flex items-center text-gray-700 text-white p-1 pl-4">
                    <span class="w-2/3">{{analysisData.bet_team}} {{analysisData.points}} {{`(${analysisData.price_format})`}}</span>
                    <span class="w-1/3">{{analysisData.price}}</span>
                    <span class="w-1/3">{{analysisData.currency_symbol}} {{matrix_data.stake | moneyFormat}}</span>
                </div>
            </div>
        </dialog-drag>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import 'vue-dialog-drag/dist/vue-dialog-drag.css'
import DialogDrag from 'vue-dialog-drag'
import { twoDecimalPlacesFormat, convertPointAsNumeric, moneyFormat } from '../../../helpers/numberFormat'

export default {
    props: ['market_id', 'analysisData'],
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
                bet_score: Number(this.analysisData.bet_score),
                against_score: Number(this.analysisData.against_score),
                points: convertPointAsNumeric(this.analysisData.points, this.analysisData.odd_type)
            }
        }
    },
    computed: {
        ...mapState('trade', ['activeBetSlip']),
        towin() {
            return this.matrix_data.stake * this.matrix_data.price
        },
        halfwin() {
            return this.towin / 2
        },
        halflose() {
            return this.matrix_data.stake / 2
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

                    if(against_team_counter < this.matrix_data.against_score || bet_team_counter < this.matrix_data.bet_score) {
                        var color = 'grey'
                    }
                    table.push({ 'color': color, 'result': twoDecimalPlacesFormat(result) })
                    against_team_counter++
                }
                this.matrix_table.push(table)
                bet_team_counter++
            }
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
