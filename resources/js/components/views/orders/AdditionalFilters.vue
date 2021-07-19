<template>
    <v-app>
        <v-main>
            <div class="additional-filters grid grid-cols-4 content-center mt-2">
                <!-- Grid: Additional Filters -->
                <div class="col-span-1 px-2">
                    <label class="font-bold text-xs uppercase">Period</label><br />
                    <select v-model="form.period" class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase" ref="dd_period" v-is-daily="$refs.dd_period" @change="setFilterDates(); changeIsDaily();">
                        <template v-if="!ordersPage.includes('history')">
                            <option value="this_week">This Week</option>
                        </template>
                        <template v-else>
                            <option value="last_week">Last Week</option>
                            <option value="daily">Daily</option>
                            <option value="this_week">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="all">All</option>
                        </template>
                    </select><br />

                    <label class="font-bold text-xs uppercase">Date Covered</label><br />
                    <div class="inline-block w-1/2" v-date-expand="isDaily">
                        <span class="bg-white rounded-sm p-1 absolute text-xs uppercase text-center bg-gray-400" style="margin-top: 5px; margin-left: 5px; width: 40px;">{{ fromLabel }}</span>
                        <v-menu :close-on-content-click="true" transition="scale-transition" offset-y min-width="auto">
                            <template v-slot:activator="{ on, attrs }">
                                <input class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase tracking-wide select-none"
                                    style="padding-left: 3rem;"
                                    v-model="form.date_from"
                                    readonly
                                    v-bind="attrs"
                                    v-on="on">
                            </template>
                            <v-date-picker v-model="form.date_from" @input="menu.date_from = false" width="250" no-title></v-date-picker>
                        </v-menu>
                    </div>
                    <div class="inline-block w-1/2" style="margin-left: -4px;" v-hide="isDaily">
                        <span class="bg-white rounded-sm p-1 absolute text-xs uppercase text-center bg-gray-400" style="margin-top: 5px; margin-left: 5px; width: 40px;">To</span>
                        <v-menu :close-on-content-click="true" transition="scale-transition" offset-y min-width="auto">
                            <template v-slot:activator="{ on, attrs }">
                                <input class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase tracking-wide select-none"
                                    style="padding-left: 3rem;"
                                    v-model="form.date_to" readonly v-bind="attrs" v-on="on">
                            </template>
                            <v-date-picker v-model="form.date_to" @input="menu.date_to = false" width="250" no-title></v-date-picker>
                        </v-menu>
                    </div>
                    <br />

                    <label class="font-bold text-xs uppercase" for="groupBy">Group By</label><br />
                    <select class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase" v-model="form.group_by" id="groupBy">
                        <option value="date" selected>Date</option>
                        <option value="leaguename">League</option>
                    </select>

                    <button @click="getMyOrders(form)" class="w-auto mt-4 border-2 border-gray-400 hover:border-orange-600 hover:bg-orange-600 hover:text-white px-4 py-2 text-xs transition ease-in-out duration-100 select-none focus:outline-none tracking-wide">
                        <i class="fas fa-search mr-2"></i> <strong class="uppercase">Apply Filters</strong>
                    </button>
                </div>

                <!-- Grid: Additional History Filters -->
                <div class="col-span-1 px-2" v-if="ordersPage.includes('history')">
                    <label class="font-bold text-xs uppercase" for="period">Search By</label><br />
                    <select class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase" v-model="form.search_by" @change="populateSearch()">
                        <option value="league_names" selected>League Names</option>
                        <option value="team_names">Team Names</option>
                    </select><br />

                    <label class="font-bold text-xs uppercase">Search Keyword</label><br />
                    <v-container>
                        <v-combobox
                            class="w-full text-xs uppercase"
                            v-model="form.search_keyword"
                            :items="search_keywords"
                            height="38"
                            outlined
                            dense
                            hide-no-data
                            @keypress.native="form.search_keyword = $event.target.value"
                            style="margin-top: -2px; margin-bottom: -29px; font-size: 0.8rem;"></v-combobox>
                    </v-container>

                    <div class="" v-if="this.myorders.length > 0">
                        <!-- Button Group -->
                        <label class="font-bold text-xs uppercase">Export</label><br />
                        <json-excel
                            class="inline-block border-2 border-gray-400 hover:border-orange-500 hover:bg-orange-500 hover:text-white px-4 py-2 text-xs text-center transition ease-in-out duration-100 select-none focus:outline-none cursor-pointer tracking-wide"
                            :data="myorders.filter(order => order.status !== 'FAILED')"
                            :fields="exportFields"
                            :name="filename">
                                <i class="fas fa-download mr-2"></i> <strong>EXCEL</strong> (.xlsx)
                        </json-excel>

                        <json-csv
                            class="inline-block border-2 border-gray-400 hover:border-orange-500 hover:bg-orange-500 hover:text-white px-4 py-2 text-xs text-center transition ease-in-out duration-100 select-none focus:outline-none cursor-pointer tracking-wide"
                            :data="myorders.filter(order => order.status !== 'FAILED')"
                            :name="`${ filename }.csv`">
                                <i class="fas fa-download mr-2"></i> <strong>CSV FILE</strong> (.csv)
                        </json-csv>
                    </div>
                </div>

                <!-- Grid: Separator -->
                <div :class="{ 'col-span-1 px-2': ordersPage.includes('history'), 'col-span-2 px-2': ordersPage.includes('orders') }">&nbsp;</div>

                <!-- Grid: User Wallet Information -->
                <div class="col-span-1 px-2 pt-20" align="right">
                    <table class="user-wallet-info" width="100%">
                        <tr>
                            <td>Credits</td>
                            <td>¥ &nbsp; <span class="totalPL" v-adjust-wallet-color="wallet.credit">{{ wallet.credit | moneyFormat }}</span></td>
                        </tr>
                        <tr>
                            <td>Profit & Loss</td>
                            <td>¥ &nbsp; <span class="totalPL" v-adjust-wallet-color="wallet.profit_loss">{{ wallet.profit_loss | moneyFormat }}</span></td>
                        </tr>
                        <tr>
                            <td>Open Orders</td>
                            <td>¥ &nbsp; <span class="totalPL" v-adjust-wallet-color="wallet.orders">{{ wallet.orders | moneyFormat }}</span></td>
                        </tr>
                        <tr>
                            <td>Today's PL</td>
                            <td>¥ &nbsp; <span class="totalPL" v-adjust-wallet-color="wallet.today_pl">{{ wallet.today_pl | moneyFormat }}</span></td>
                        </tr>
                        <tr>
                            <td>Yesterday's PL</td>
                            <td>¥ &nbsp; <span class="totalPL" v-adjust-wallet-color="wallet.yesterday_pl">{{ wallet.yesterday_pl | moneyFormat }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </v-main>
    </v-app>
</template>

<script>
    import Cookies from 'js-cookie'
    import { mapState, mapActions } from 'vuex'
    import { twoDecimalPlacesFormat, moneyFormat } from '../../../helpers/numberFormat'
    import moment from 'moment-timezone'
    import JsonExcel from 'vue-json-excel'
    import JsonCsv from 'vue-json-csv'

    export default {
        components: {
            JsonExcel,
            JsonCsv,
        },
        computed: {
            ...mapState('trade', ['wallet']),
            ...mapState('settings', ['defaultTimezone']),
            ...mapState('orders', ['myorders']),
            filename() {
                let display_name = Cookies.get('display_name')

                return `Multiline Orders (${ display_name })`
            },
        },
        data() {
            return {
                isDaily: false,
                fromLabel: 'From',
                search_keywords: [],
                menu: {
                    date_from: false,
                    date_to: false
                },
                form: {
                    period: '',
                    group_by: 'date',
                    search_by: '',
                    search_keyword: '',
                    date_from: '',
                    date_to: ''
                },
                exportFields: {
                    'Bet ID': 'bet_id',
                    'Transaction Date & Time': 'created',
                    'Bet Selection': 'bet_selection',
                    'Provider': 'provider',
                    'Odds': 'odds',
                    'Stake': 'stake',
                    'To Win': 'towin',
                    'Status': 'status',
                    'Result': 'score',
                    'Valid Stake': 'valid_stake',
                    'Profit/Loss': 'pl',
                    'Reason': 'reason'
                }
            }
        },
        directives: {
            adjustWalletColor: {
                bind(el, binding, vnode) {
                    vnode.context.adjustWalletColor(el, binding, vnode)
                },
                componentUpdated(el, binding, vnode) {
                    vnode.context.adjustWalletColor(el, binding, vnode)
                }
            },
            isDaily: {
                componentUpdated(el, binding) {
                    let _selected = el.options.selectedIndex
                    let _value = el.children[_selected].value

                    el.isDaily = _value == 'daily' ? true : false
                }
            },
            hide: {
                componentUpdated(el, binding) {
                    if (binding.value) {
                        el.classList.add('hidden')
                    } else {
                        el.classList.remove('hidden')
                    }
                }
            },
            dateExpand: {
                componentUpdated(el, binding) {
                    if (binding.value) {
                        el.classList.remove('w-1/2')
                        el.classList.add('w-full')
                    } else {
                        el.classList.remove('w-full')
                        el.classList.add('w-1/2')
                    }
                }
            },
        },
        filters: {
            moneyFormat
        },
        methods: {
            ...mapActions('orders', ['getMyOrders', 'getLeaguesList']),
            adjustWalletColor(el, binding, vnode) {
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
            },
            changeIsDaily() {
                this.isDaily = this.form.period == 'daily' ? true : false
                this.fromLabel = this.form.period == 'daily' ? 'Date' : 'From'
            },
            setFilterDates() {
                let fromToDate = {
                    all: {
                        date_from: null,
                        date_to: null
                    },
                    daily: {
                        date_from: moment().format('YYYY-MM-DD'),
                        date_to: moment().format('YYYY-MM-DD')
                    },
                    yesterday: {
                        date_from: moment().subtract(1, 'days').format('YYYY-MM-DD'),
                        date_to: moment().subtract(1, 'days').format('YYYY-MM-DD')
                    },
                    this_week: {
                        date_from: moment().startOf('isoweek').format('YYYY-MM-DD'),
                        date_to: moment().endOf('isoweek').format('YYYY-MM-DD')
                    },
                    last_week: {
                        date_from: moment().startOf('isoweek').subtract(1, 'week').format('YYYY-MM-DD'),
                        date_to: moment().endOf('isoweek').subtract(1, 'week').format('YYYY-MM-DD')
                    },
                    monthly: {
                        date_from: moment().startOf('month').format('YYYY-MM-DD'),
                        date_to: moment().endOf('month').format('YYYY-MM-DD')
                    }
                }

                Object.keys(fromToDate).map(key => {
                    if(this.form.period == key) {
                        this.form.date_from = fromToDate[key].date_from
                        this.form.date_to = fromToDate[key].date_to
                    }

                    if (this.form.period != 'daily') {
                        this.form.search_by = ""
                        this.form.search_keyword = ""
                        this.search_keywords = []
                    }
                })
            },
            setInitialVars() {
                this.form.search_by = ""
                this.form.search_keyword = ""

                if (this.ordersPage.includes('history')) {
                    this.form.period = 'last_week'
                    this.form.date_from = moment().startOf('isoweek').subtract(1, 'week').format('YYYY-MM-DD')
                    this.form.date_to = moment().endOf('isoweek').subtract(1, 'week').format('YYYY-MM-DD')
                } else if (this.ordersPage.includes('orders')) {
                    this.form.period = 'this_week'
                    this.form.date_from = moment().startOf('isoweek').format('YYYY-MM-DD')
                    this.form.date_to = moment().endOf('isoweek').format('YYYY-MM-DD')
                    this.isDaily = false
                }

                this.getMyOrders(this.form)
            },
            getLeaguesList() {
                let token = Cookies.get('mltoken')

                axios.get('v1/leagues/list', { headers: { 'Authorization': `Bearer ${ token }` } })
                    .then(response => {
                        if (response.data.data != null) {
                            this.search_keywords = []

                            response.data.data.map(row => {
                                this.search_keywords.push(row.name)
                            })
                        }
                    })
            },
            getTeamsList() {
                let token = Cookies.get('mltoken')

                axios.get('v1/teams/list', { headers: { 'Authorization': `Bearer ${ token }` } })
                    .then(response => {
                        if (response.data.data != null) {
                            this.search_keywords = []

                            response.data.data.map(row => {
                                this.search_keywords.push(row.name)
                            })
                        }
                    })
            },
            populateSearch() {
                this.form.search_keyword = ""

                if (this.form.search_by == "league_names") {
                    return this.getLeaguesList()
                } else if (this.form.search_by == "team_names") {
                    return this.getTeamsList()
                }
            },
        },
        mounted() {
            this.setInitialVars()
        },
        watch: {
            ordersPage() {
                this.setInitialVars()
            }
        },
        props: ['totalPL', 'ordersPage'],
    }
</script>

<style lang="scss">
    .user-wallet-info {
        td {
            padding: 0rem 0.2rem;
            vertical-align: bottom;

            color: #ABABAB;
            font-size: 0.8rem;

            &:first-child {
                text-align: right;
                text-transform: uppercase;
            }


            &:last-child {
                text-align: right;
                width: 200px;

                color: #444444;
                font-size: 1.2rem;
                font-weight: bold !important;
                text-align: right;
                text-transform: uppercase;
            }
        }

        .greenPL {
            color: #009E28 !important;
        }

        .redPL {
            color: #FF2525 !important;
        }
    }
</style>
