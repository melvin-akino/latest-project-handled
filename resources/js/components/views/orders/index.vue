er <template>
    <div class="container mx-auto my-10">
        <h3 class="text-xl">My Orders</h3>
        <div class="h-full">
            <v-client-table name="My Orders" :data="myorders" :columns="columns" :options="options" ref="ordersTable" @filter="getFilteredData">
                <div slot="beforeTable" class="relative flex justify-end" v-if="myorders.length != 0">
                    <span class="absolute totalPLlabel">Total P/L</span>
                    <span class="absolute totalPL" v-adjust-total-pl-color="totalPL">{{wallet.currency_symbol}} {{totalPL | moneyFormat}}</span>
                    <json-excel :data="toExport" :fields="exportFields" :name="filename">
                        <span class="text-center py-1 cursor-pointer"><i class="fas fa-file-export" title="Export orders data."></i></span>
                    </json-excel>
                </div>
                <div slot="betSelection" slot-scope="props" v-html="props.row.bet_selection"></div>
                <div slot="pl" slot-scope="props">
                    <span :class="{'greenPL': props.row.status == 'WIN' || props.row.status == 'HALF WIN', 'redPL': props.row.status == 'LOSE' || props.row.status == 'HALF LOSE'}" >{{props.row.pl | formatPL}}</span>
                </div>
                <div slot="score" slot-scope="props">
                    <span class="text-sm">{{ props.row.settled != "" ? props.row.score : "-" }}</span>
                </div>
                <div class="flex justify-start" slot="betData" slot-scope="props">
                    <a href="#" @click.prevent="openBetMatrix(props.row.order_id)" class="text-center py-1 w-1/2"><i class="fas fa-chart-area" title="Bet Matrix" v-if="oddTypesWithSpreads.includes(props.row.odd_type_id)"></i></a>
                    <a href="#" @click.prevent="openOddsHistory(props.row.order_id)" class="text-center py-1 w-1/2"><i class="fas fa-bars" title="Odds History"></i></a>
                </div>
            </v-client-table>
            <order-data v-for="order in myorders" :key="order.order_id" :openedOddsHistory="openedOddsHistory" :openedBetMatrix="openedBetMatrix" @closeOddsHistory="closeOddsHistory" @closeBetMatrix="closeBetMatrix" :order="order"></order-data>
        </div>
    </div>
</template>

<script>
import Cookies from 'js-cookie'
import OrderData from './OrderData'
import _ from 'lodash'
import JsonExcel from 'vue-json-excel'
import { mapState } from 'vuex'
import { twoDecimalPlacesFormat, moneyFormat } from '../../../helpers/numberFormat'

export default {
    components: {
        OrderData,
        JsonExcel
    },
    data() {
        return {
            myorders: [],
            totalPL: '',
            columns: ['bet_id', 'created', 'betSelection', 'provider', 'odds', 'stake', 'towin', 'status', 'score', 'pl', 'betData'],
            options: {
                headings: {
                    bet_id: 'Bet ID',
                    betSelection: 'Bet Selection',
                    created: 'Transaction Date & Time',
                    pl: 'Profit/Loss',
                    towin: 'To Win',
                    status: 'Status',
                    score: 'Result',
                    betData: ''
                },
                columnsClasses: {
                    betSelection: 'betSelection',
                    odds: 'alignRight',
                    stake: 'alignRight',
                    towin: 'alignRight',
                    pl: 'alignRight',
                    score: 'alignRight'
                },
                sortable: ['bet_id', 'created', 'provider', 'odds', 'stake', 'towin', 'status','pl']
            },
            openedOddsHistory: [],
            openedBetMatrix: [],
            oddTypesWithSpreads: [3, 4, 11, 12],
            toExport: [],
            exportFields: {
                'Bet ID'                 : 'bet_id',
                'Transaction Date & Time': 'created',
                'Bet Selection'          : 'bet_selection',
                'Provider'               : 'provider',
                'Odds'                   : 'odds',
                'Stake'                  : 'stake',
                'To Win'                 : 'towin',
                'Status'                 : 'status',
                'Result'                 : 'score',
                'Profit/Loss'            : 'pl'
            }
        }
    },
    head: {
        title() {
            return {
                inner: 'My Orders'
            }
        }
    },
    mounted() {
        this.getMyOrders()
        this.$store.dispatch('trade/getWalletData')
        this.$store.dispatch('settings/getDefaultGeneralSettings')
    },
    computed: {
        ...mapState('trade', ['wallet']),
        filename() {
            let display_name = Cookies.get('display_name')
            return `Multiline Orders (${display_name})`
        }
    },
    watch: {
        myorders() {
            this.toExport = this.myorders
        }
    },
    methods: {
        getMyOrders() {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/all`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                let orders = []
                let formattedColumns = ['stake', 'towin', 'pl']
                response.data.data.orders.map(order => {
                    let orderObj = {}
                    Object.keys(order).map(key => {
                        if(formattedColumns.includes(key)) {
                            this.$set(orderObj, key, moneyFormat(Number(order[key])))
                        } else if(key=='odds') {
                            this.$set(orderObj, key, twoDecimalPlacesFormat(Number(order[key])))
                        } else {
                            this.$set(orderObj, key, order[key])
                        }
                    })
                    orders.push(orderObj)
                })
                this.myorders = orders
                let pls = this.myorders.map(order => Number(order.pl))
                this.totalPL = pls.reduce((firstPL, secondPL) => firstPL + secondPL, 0)
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        },
        getFilteredData() {
            this.toExport = this.$refs.ordersTable.allFilteredData
        },
        openOddsHistory(id) {
            this.openedOddsHistory.push(id)
        },
        openBetMatrix(id) {
            this.openedBetMatrix.push(id)
        },
        closeOddsHistory(id) {
            this.openedOddsHistory = this.openedOddsHistory.filter(oddHistory => oddHistory != id)
        },
        closeBetMatrix(id) {
            this.openedBetMatrix = this.openedBetMatrix.filter(betmatrix => betmatrix != id)
        }
    },
    directives: {
        adjustTotalPlColor: {
            bind(el, binding, vnode) {
                if(binding.value > 0) {
                    el.classList.remove('redPL')
                    el.classList.add('greenPL')
                } else if(binding.value < 0) {
                    el.classList.add('redPL')
                    el.classList.remove('greenPL')
                } else {
                    el.classList.remove('redPL')
                    el.classList.remove('greenPL')
                }
            }
        }
    },
    filters: {
        moneyFormat,
        formatPL(value) {
            if(value == "0.00" || value=="0") {
                return "-"
            } else {
                return value
            }
        }
    }
}
</script>

<style>
    .totalPLdata {
        font-size: 15px;
    }

    .totalPLlabel {
        right: 138px;
    }

    .totalPL {
        font-weight: 600;
        right: 62px;
    }

    .greenPL {
        color: #4cbb17;
    }

    .redPL {
        color: #ff0000;
    }

    .betSelection {
        width: 208px;
    }
</style>
