er <template>
    <div class="container mx-auto my-10">
        <h3 class="text-xl">My Orders</h3>
        <div class="relative h-full">
            <div class="absolute text-sm totalPLdata" v-if="myorders.length != 0" v-adjust-pl-data-position="myorders.length">
                <span>Total P/L</span>
                <span class="totalPL">{{wallet.currency_symbol}} {{totalPL | moneyFormat}}</span>
            </div>
            <v-client-table name="My Orders" :data="myorders" :columns="columns" :options="options" ref="ordersTable">
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
import { mapState } from 'vuex'
import { moneyFormat } from '../../../helpers/numberFormat'

export default {
    components: {
        OrderData
    },
    data() {
        return {
            myorders: [],
            totalPL: '',
            columns: ['bet_id', 'created', 'bet_selection', 'provider', 'status', 'odds', 'stake', 'towin', 'pl', 'betData'],
            options: {
                headings: {
                    bet_id: 'Bet ID',
                    bet_selection: 'Bet Selection',
                    created: 'Transaction Date & Time',
                    pl: 'Profit/Loss',
                    towin: 'To Win',
                    status: 'Status',
                    betData: ''
                }
            },
            openedOddsHistory: [],
            openedBetMatrix: [],
            oddTypesWithSpreads: [3, 4, 11, 12]
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
        this.renderBetSelectionAsHTML()
    },
    computed: {
        ...mapState('trade', ['wallet'])
    },
    updated() {
        this.renderBetSelectionAsHTML()
    },
    methods: {
        getMyOrders() {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/all`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                let orders = []
                let formattedColumns = ['stake', 'towin']
                response.data.data.orders.map(order => {
                    let orderObj = {}
                    Object.keys(order).map(key => {
                        if(formattedColumns.includes(key)) {
                            this.$set(orderObj, key, moneyFormat(Number(order[key])))
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
        renderBetSelectionAsHTML() {
            if(!_.isEmpty(this.myorders)) {
                Object.keys(this.$refs.ordersTable.$el.children[1].children[0].tBodies[0].rows).map(row => {
                    let betSelection = this.$refs.ordersTable.$el.children[1].children[0].tBodies[0].rows[row].cells[2]
                    betSelection.innerHTML = this.myorders[row].bet_selection
                })
            }
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
        adjustPlDataPosition: {
            bind(el, binding, vnode) {
                if(binding.value > 10) {
                    el.style.top = '55px'
                } else {
                    el.style.top = '17px'
                }
            }
        }
    },
    filters: {
        moneyFormat
    }
}
</script>

<style>
    @import '../../../../assets/vuetables2.css';

    .VueTables__table {
        margin-top: 1rem;
    }

    .VueTables__table > thead {
        background-color: #ed8936;
        color: #ffffff;
    }

    .VueTables__sortable,  .VueTables__row td {
        text-align: center;
    }

    .VueTables__table > tbody {
        background-color: #ffffff;
        font-size: .875rem;
    }

    .VueTables__row  {
        border: none;
    }

    .VuePagination__pagination {
        margin: 0;
    }

    .VuePagination__count {
        font-size: .875rem;
    }

    .VueTables__search {
        margin-bottom:5px;
    }

    .VueTables__search-field > label {
        margin-right:25px;
    }

    .VueTables__limit-field > label {
        margin-right: 6px;
    }

    .VueTables__search__input, .VueTables__limit-field > select {
        font-size: .9rem;
        color: #4a5568;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        line-height: 1.25;
        border-width: 1px;
        border-radius: 0.25rem;
    }

    .VueTables__search__input {
        padding: 0.25rem 0.75rem;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }

    .VueTables__limit-field {
        padding-top: 0.25rem;
        padding-bottom: 0.75rem;
    }

    .totalPL {
        margin-left: 53px;
    }

    .totalPLdata {
        right: 55px;
    }

    .dialog-drag {
        border: solid 1px #ed8936;
        box-shadow: none;
        background-color: #edf2f7;
        animation-duration: .2s;
        animation-name: fadeIn;
        animation-timing-function: ease-in-out;
        position: fixed;
    }

    .dialog-drag .dialog-body {
        padding: 0;
    }

    .dialog-drag .dialog-header {
        background-color:#ed8936;
    }
</style>
