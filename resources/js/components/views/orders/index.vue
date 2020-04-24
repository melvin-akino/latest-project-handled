er <template>
    <div class="container mx-auto my-10">
        <h3 class="text-xl">My Orders</h3>
        <div class="h-full">
            <div class="relative" v-if="myorders.length != 0" v-adjust-pl-data-position="myorders.length">
                <span class="absolute totalPLlabel">Total P/L</span>
                <span class="absolute totalPL" v-adjust-total-pl-color="totalPL">{{wallet.currency_symbol}} {{totalPL | moneyFormat}}</span>
            </div>
            <v-client-table name="My Orders" :data="myorders" :columns="columns" :options="options" ref="ordersTable">
                <div slot="betSelection" slot-scope="props" v-html="props.row.bet_selection"></div>
                <div slot="pl" slot-scope="props">
                    <span :class="{'greenPL': props.row.status == 'WIN' || props.row.status == 'HALF WIN', 'redPL': props.row.status == 'LOSE' || props.row.status == 'HALF LOSE'}" >{{props.row.pl | formatPL}}</span>
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
import { mapState } from 'vuex'
import { twoDecimalPlacesFormat, moneyFormat } from '../../../helpers/numberFormat'

export default {
    components: {
        OrderData
    },
    data() {
        return {
            myorders: [],
            totalPL: '',
            columns: ['bet_id', 'created', 'betSelection', 'provider', 'status', 'odds', 'stake', 'towin', 'pl', 'betData'],
            options: {
                headings: {
                    bet_id: 'Bet ID',
                    betSelection: 'Bet Selection',
                    created: 'Transaction Date & Time',
                    pl: 'Profit/Loss',
                    towin: 'To Win',
                    status: 'Status',
                    betData: ''
                },
                columnsClasses: {
                    betSelection: 'betSelection',
                    odds: 'alignRight',
                    stake: 'alignRight',
                    towin: 'alignRight',
                    pl: 'alignRight'
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
        this.getPriceFormat()
        this.$store.dispatch('trade/getWalletData')
    },
    computed: {
        ...mapState('trade', ['wallet'])
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
        getPriceFormat() {
            if(!this.$store.state.settings.defaultPriceFormat) {
                this.$store.dispatch('settings/getDefaultPriceFormat')
                .then(response => {
                    this.$store.commit('settings/SET_DEFAULT_PRICE_FORMAT', response)
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
        },
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
    @import '../../../../assets/vuetables2.css';

    .VueTables__table {
        margin-top: 1rem;
    }

    .VueTables__table > thead {
        background-color: #ed8936;
        color: #ffffff;
    }

    .alignRight {
        text-align: right;
    }

    .VueTables__table > tbody {
        background-color: #ffffff;
        font-size: .875rem;
    }

    .VueTables__heading {
        font-size: 14px;
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

    .totalPLdata {
        font-size: 15px;
    }

    .totalPLlabel {
        right: 161px;
    }

    .totalPL {
        font-weight: 600;
        right: 62px;
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

    .greenPL {
        color: #4cbb17;
    }

    .redPL {
        color: #ff0000;
    }

    .betSelection {
        width: 216px;
    }
</style>
