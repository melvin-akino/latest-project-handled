<template>
    <div class="additional-filters grid grid-cols-5 content-center mt-2">
        <!-- Grid: Additional Filters -->
        <div class="col-span-1 px-2">
            <label class="font-bold text-sm uppercase">Period</label><br />
            <select v-model="form.period" class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase" @change="setFilterDates">
                <template v-if="!ordersPage.includes('history')">
                    <option value="this_week">This Week</option>
                </template>
                <template v-else>
                    <option value="last_week">Last Week</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="all">All</option>
                </template>
            </select><br />

            <label class="font-bold text-sm uppercase">Date Covered</label><br />
            <div class="inline-block w-1/2">
                <span class="bg-white rounded-sm p-1 absolute text-xs uppercase text-center bg-gray-400" style="margin-top: 5px; margin-left: 5px; width: 40px;">From</span>
                <v-menu v-model="menu.date_from" :close-on-content-click="true" :nudge-right="40" transition="scale-transition" offset-y min-width="auto">
                    <template v-slot:activator="{ on, attrs }">
                        <input class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase tracking-wide select-none"
                            style="padding-left: 3.25rem;"
                            v-model="form.date_from" readonly v-bind="attrs" v-on="on">
                    </template>
                    <v-date-picker v-model="form.date_from" @input="menu.date_from = false" no-title></v-date-picker>
                </v-menu>
            </div>
            <div class="inline-block w-1/2" style="margin-left: -4px;">
                <span class="bg-white rounded-sm p-1 absolute text-xs uppercase text-center bg-gray-400" style="margin-top: 5px; margin-left: 5px; width: 40px;">To</span>
                <v-menu v-model="menu.date_to" :close-on-content-click="true" :nudge-right="40" transition="scale-transition" offset-y min-width="auto">
                    <template v-slot:activator="{ on, attrs }">
                        <input class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase tracking-wide select-none"
                            style="padding-left: 3.25rem;"
                            v-model="form.date_to" readonly v-bind="attrs" v-on="on">
                    </template>
                    <v-date-picker v-model="form.date_to" @input="menu.date_to = false" no-title></v-date-picker>
                </v-menu>
            </div>
            <br />

            <label class="font-bold text-sm uppercase" for="groupBy">Group By</label><br />
            <select class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase" v-model="form.group_by" id="groupBy">
                <option value="date" selected>Date</option>
                <option value="leaguename">League</option>
            </select>

            <button @click="getMyOrders(form)" class="w-auto mt-4 border-2 border-gray-400 hover:border-orange-600 hover:bg-orange-600 hover:text-white rounded-full px-4 py-2 text-xs transition ease-in-out duration-100 select-none focus:outline-none tracking-wide">
                <i class="fas fa-search mr-2"></i> <strong class="uppercase">Apply Filters</strong>
            </button>
        </div>

        <!-- Grid: Additional History Filters -->
        <div class="col-span-1 px-2" v-if="ordersPage.includes('history')">
            <label class="font-bold text-sm uppercase" for="period">Search By</label><br />
            <select class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase" v-model="form.search_by" @change="populateSearch()">
                <option value="league_names" selected>League Names</option>
                <option value="team_names">Team Names</option>
            </select><br />

            <label class="font-bold text-sm uppercase">Search Keyword</label><br />
            <v-autocomplete class="w-full text-xs uppercase" v-model="form.search_keyword" :items="populateSearch()" height="40" outlined dense style="margin-top: -2px; font-size: 0.8rem;"></v-autocomplete>
        </div>

        <div class="col-span-1 px-2" v-if="ordersPage.includes('history')">
            <!-- Button Group -->
            <label class="font-bold text-sm uppercase">Export</label><br />
            <json-excel :data="toExport" :fields="exportFields" :name="filename">
                <button class="w-auto mb-5 border-2 border-gray-400 hover:border-green-600 hover:bg-green-600 hover:text-white rounded-full px-4 py-2 text-xs transition ease-in-out duration-100 select-none focus:outline-none tracking-wide">
                    <i class="fas fa-download mr-2"></i> <strong>EXCEL</strong> (.xlsx)
                </button>
            </json-excel>
            <button class="w-auto border-2 border-gray-400 hover:border-blue-600 hover:bg-blue-600 hover:text-white rounded-full px-4 py-2 text-xs transition ease-in-out duration-100 select-none focus:outline-none tracking-wide">
                <i class="fas fa-download mr-2"></i> <strong>CSV FILE</strong> (.csv)
            </button>
        </div>

        <!-- Grid: Separator -->
        <div class="col-span-2 px-2" v-if="!ordersPage.includes('history')">&nbsp;</div>

        <!-- Grid: User Wallet Information -->
        <div class="col-span-2 px-2 pt-6" align="right">
            <table class="user-wallet-info" width="100%">
                <tr>
                    <td>Credits</td>
                    <td>¥ &nbsp; <span class="totalPL" v-adjust-total-pl-color="wallet.credit">{{ wallet.credit | moneyFormat }}</span></td>
                </tr>
                <tr>
                    <td>Profit & Loss</td>
                    <td>¥ &nbsp; <span class="totalPL" v-adjust-total-pl-color="totalPL">{{ totalPL | moneyFormat }}</span></td>
                </tr>
                <tr>
                    <td>Open Orders</td>
                    <td>¥ &nbsp; <span class="totalPL" v-adjust-total-pl-color="wallet.orders">{{ wallet.orders | moneyFormat }}</span></td>
                </tr>
                <tr>
                    <td>Today's PL</td>
                    <td>¥ &nbsp; <span class="totalPL" v-adjust-total-pl-color="wallet.today_pl">{{ wallet.today_pl | moneyFormat }}</span></td>
                </tr>
                <tr>
                    <td>Yesterday's PL</td>
                    <td>¥ &nbsp; <span class="totalPL" v-adjust-total-pl-color="wallet.yesterday_pl">{{ wallet.yesterday_pl | moneyFormat }}</span></td>
                </tr>
            </table>
        </div>
    </div>
</template>

<script>
    import Cookies from 'js-cookie'
    import { mapState, mapActions } from 'vuex'
    import { twoDecimalPlacesFormat, moneyFormat } from '../../../helpers/numberFormat'
    import moment from 'moment-timezone'

    export default {
        computed: {
            ...mapState('trade', ['wallet']),
            ...mapState('settings', ['defaultTimezone']),
        },
        data() {
            return {
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
                }
            }
        },
        directives: {
            adjustTotalPlColor: {
                componentUpdated(el, binding, vnode) {
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
            moneyFormat
        },
        methods: {
            ...mapActions('orders', ['getMyOrders', 'getLeaguesList']),
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
                        date_to: moment().endOf('isoweek').add(1, 'day').format('YYYY-MM-DD')
                    },
                    last_week: {
                        date_from: moment().startOf('isoweek').subtract(1, 'week').format('YYYY-MM-DD'),
                        date_to: moment().endOf('isoweek').subtract(1, 'week').add(1, 'day').format('YYYY-MM-DD')
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
                })
            },
            setInitialVars() {
                if (this.ordersPage.includes('history')) {
                    this.form.period = 'last_week'
                    this.form.date_from = moment().startOf('isoweek').subtract(1, 'week').format('YYYY-MM-DD')
                    this.form.date_to = moment().endOf('isoweek').subtract(1, 'week').add(1, 'day').format('YYYY-MM-DD')
                } else if (this.ordersPage.includes('orders')) {
                    this.form.period = 'this_week'
                    this.form.date_from = moment().startOf('isoweek').format('YYYY-MM-DD')
                    this.form.date_to = moment().endOf('isoweek').add(1, 'day').format('YYYY-MM-DD')
                }

                this.getMyOrders(this.form)
            },
            getLeaguesList() {
                let list = []
                let token = Cookies.get('mltoken')

                axios.get('v1/leagues/list', { headers: { 'Authorization': `Bearer ${ token }` } })
                    .then(response => {
                        if (response.data.data != null) {
                            response.data.data.map(row => {
                                list.push(row.name)
                            })
                        }

                        console.log(list)
                    })

                console.log(list)
                return ['qwe', 'asd', 'zxc']
            },
            getTeamsList() {
                let _list = []
                let _token = Cookies.get('mltoken')

                axios.get('v1/teams/list', { headers: { 'Authorization': `Bearer ${ _token }` } })
                    .then(response => {
                        if (response.data.data != null) {
                            response.data.data.map(row => {
                                _list.push(row.name)
                            })

                            return _list
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
            }
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
            padding: 0rem 0.25rem;
            vertical-align: middle;

            color: #ABABAB;
            font-size: 0.8rem;

            &:first-child {
                width: 60%;

                text-align: right;
                text-transform: uppercase;
            }


            &:last-child {
                text-align: left;

                color: #444444;
                font-size: 1.2rem;
                font-weight: bold !important;
            }
        }

        .greenPL {
            color: #46C7BB !important;
        }

        .redPL {
            color: #B31005 !important;
        }
    }
</style>
