<template>
    <div class="orders container-fluid px-5 mx-auto my-10">
        <h1 class="text-2xl font-semibold">{{ pageTitle }}</h1>

        <div class="h-full mt-4 rounded-xl bg-white" style="box-shadow: inset 0px 0px 0px 2px rgba(0, 0, 0, 0.1);">
            <div class="block h-full px-4 py-2">
                <additional-filters :ordersPage="ordersPage" :totalPL="totalPL"></additional-filters>
            </div>

            <div class="block h-full pt-4" style="padding: 1rem 0.125rem;">
                <v-app>
                    <v-main>
                        <v-data-table
                            :headers="headers"
                            :items="myorders"
                            :items-per-page="10"
                            :group-by="groupedBy"
                            :group-desc.sync="groupDesc"
                            :sort-by.sync="sortBy"
                            :sort-desc.sync="sortDesc"
                            :footer-props="{
                                showFirstLastPage: true,
                                firstIcon: 'mdi-chevron-double-left',
                                lastIcon: 'mdi-chevron-double-right',
                                prevIcon: 'mdi-chevron-left',
                                nextIcon: 'mdi-chevron-right'
                            }"
                            class="bet-data">
                            <template v-slot:[`group.header`]="{ group, toggle }">
                                <td colspan="13" @click="toggle">{{ convertDate(group) }}</td>
                            </template>

                            <template v-slot:header.created="{ header }">
                                <span v-html="header.text"></span>
                            </template>

                            <template v-slot:header.bet_selection="{ header }">
                                <span v-html="header.text"></span>
                            </template>

                            <template v-slot:header.valid_stake="{ header }">
                                <span v-html="header.text"></span>
                            </template>

                            <template v-slot:header.pl="{ header }">
                                <span v-html="header.text"></span>
                            </template>

                            <template v-slot:item="props">
                                <tr :class="{'_green': greenStatus.includes(props.item.status), '_failed': redStatus.includes(props.item.status)}">
                                    <td class="text-start">{{ props.item.bet_id }}</td>
                                    <td class="text-center">{{ props.item.created }}</td>
                                    <td class="text-start"><span v-html="props.item.bet_selection"></span></td>
                                    <td class="text-center font-bold">{{ props.item.provider }}</td>
                                    <td class="text-center"><span class="block">{{ props.item.odds }}</span></td>
                                    <td class="text-right">{{ props.item.stake }}</td>
                                    <td class="text-right">{{ props.item.towin }}</td>
                                    <td class="text-center"><strong class="block text-center">{{ props.item.status }}</strong></td>
                                    <td class="text-center"><span>{{ props.item.score.replace(/\"/g, '') }}</span></td>
                                    <td class="text-right">{{ props.item.valid_stake }}</td>
                                    <td class="text-right" v-adjust-total-pl-color="props.item.pl">{{ props.item.pl }}</td>
                                    <td class="text-start">{{ props.item.error_message ? props.item.error_message : props.item.reason }}</td>
                                    <td class="text-right">
                                        <a href="#" @click.prevent="openBetMatrix(props.item.order_id, `${props.item.order_id}-betmatrix`)"
                                            class="betdata-btn text-center rounded-full py-2 px-3 hover:bg-gray-400 w-1/2"
                                            v-if="!failedBetStatus.includes(props.item.status)">
                                                <i class="fas fa-chart-area" title="Bet Matrix"></i>
                                        </a>
                                        <a href="#" @click.prevent="openOddsHistory(props.item.order_id, `${props.item.order_id}-orderlogs`)"
                                            class="betdata-btn text-center rounded-full py-2 px-3 hover:bg-gray-400 w-1/2"
                                            :class="{'ml-4': failedBetStatus.includes(props.item.status)}">
                                                <i class="fas fa-bars" title="Odds History"></i>
                                        </a>
                                    </td>
                                </tr>
                            </template>
                        </v-data-table>
                    </v-main>
                </v-app>

                <order-data v-for="order in myorders"
                    :key="order.order_id"
                    :openedOddsHistory="openedOddsHistory"
                    :openedBetMatrix="openedBetMatrix"
                    @closeOddsHistory="closeOddsHistory"
                    @closeBetMatrix="closeBetMatrix" :order="order"></order-data>
            </div>
        </div>
    </div>
</template>

<script>
    import Cookies from 'js-cookie'
    import _ from 'lodash'
    import { mapState, mapActions } from 'vuex'
    import OrderData from './OrderData'
    import { twoDecimalPlacesFormat, moneyFormat } from '../../../helpers/numberFormat'
    import AdditionalFilters from './AdditionalFilters'
    import moment from 'moment-timezone'

    export default {
        components: {
            AdditionalFilters,
            OrderData,
        },
        data() {
            return {
                sortBy: 'bet_id',
                sortDesc: true,
                form: {
                    group_by: 'date',
                    search_by: '',
                    search_keyword: '',
                    date_from: moment().startOf('isoweek').format('YYYY-MM-DD'),
                    date_to: moment().endOf('isoweek').format('YYYY-MM-DD')
                },
                headers: [
                    { text: 'bet id', value: 'bet_id', align: 'center', width: 130, },
                    { text: 'transaction<br />date & time', value: 'created', class: 'col-center', align: 'center', width: 150, },
                    { text: 'bet<br />selection', value: 'bet_selection', align: 'start', sortable: false, },
                    { text: 'provider', value: 'provider', align: 'center', sortable: false, width: 50, },
                    { text: 'odds', value: 'odds', align: 'center', class: 'col-center', width: 70, },
                    { text: 'stake', value: 'stake', align: 'end', class: 'h-amounts', width: 80, },
                    { text: 'to win', value: 'towin', align: 'end', class: 'h-amounts', width: 80, },
                    { text: 'status', value: 'status', align: 'center', class: 'col-center', width: 100, },
                    { text: 'result', value: 'score', align: 'center', sortable: false, width: 70, },
                    { text: 'valid<br />stake', value: 'valid_stake', align: 'end', class: 'h-amounts', sortable: false, width: 80, },
                    { text: 'profit<br />loss', value: 'pl', align: 'end', class: 'h-amounts', width: 80, },
                    { text: 'reason', value: 'reason', align: 'start', sortable: false, },
                    { text: '', value: 'betData', align: 'end', sortable: false, width: 100, },
                ],
                openedOddsHistory: [],
                openedBetMatrix: [],
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
            this.$store.dispatch('trade/getWalletData')
            this.$store.dispatch('settings/getDefaultGeneralSettings')
        },
        updated() {
            moment.tz.setDefault(this.defaultTimezone.name)
        },
        computed: {
            ...mapState('trade', ['wallet', 'failedBetStatus']),
            ...mapState('settings', ['defaultTimezone']),
            ...mapState('orders', ['myorders', 'groupedBy', 'groupDesc']),
            totalPL() {
                let pls = this.myorders.map(order => Number(order.pl.replace(',', '')))

                return pls.reduce((firstPL, secondPL) => firstPL + secondPL, 0)
            },
            pageTitle() {
                let page = this.$route.path

                switch (page) {
                    case "/orders":
                        page = "My Orders"
                    break;
                    case "/history":
                        page = "Bet History"
                    break;
                }

                return page
            },
            ordersPage() {
                return this.$route.path
            },
            greenStatus() {
                return ['WIN', 'HALF WIN', 'PUSH', 'REFUNDED']
            },
            redStatus() {
                return ['FAILED', 'REJECTED', 'CANCELLED', 'ABNORMAL BET', 'VOID']
            }
        },
        methods: {
            ...mapActions('orders', ['getMyOrders']),
            getFilteredData() {
                this.toExport = this.$refs.ordersTable.allFilteredData
            },
            openOddsHistory(id, data) {
                this.openedOddsHistory.push(id)
                this.$store.dispatch('trade/setActivePopup', data)
            },
            openBetMatrix(id, data) {
                this.openedBetMatrix.push(id)
                this.$store.dispatch('trade/setActivePopup', data)
            },
            closeOddsHistory(id) {
                this.openedOddsHistory = this.openedOddsHistory.filter(oddHistory => oddHistory != id)
            },
            closeBetMatrix(id) {
                this.openedBetMatrix = this.openedBetMatrix.filter(betmatrix => betmatrix != id)
            },
            convertDate(data) {
                return this.groupedBy == 'date' ? moment(data).format('MMMM DD, YYYY') : data
            },
            adjustTotalPlColor(el, binding, vnode) {
                let _value = parseFloat(binding.value)

                if(_value > 0) {
                    el.classList.remove('redPL')
                    el.classList.add('greenPL')
                } else if(_value == 0) {
                    el.classList.remove('redPL')
                    el.classList.remove('greenPL')
                } else {
                    el.classList.add('redPL')
                    el.classList.remove('greenPL')
                }
            }
        },
        directives: {
            adjustTotalPlColor: {
                bind(el, binding, vnode) {
                    vnode.context.adjustTotalPlColor(el, binding, vnode)
                },
                componentUpdated(el, binding, vnode) {
                    vnode.context.adjustTotalPlColor(el, binding, vnode)
                }
            },
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

<style lang="scss">
    .orders *:not(i.material-icons) {
        font-family: "Assistant", sans-serif !important;
    }

    input, select, textarea {
        background: #FFFFFF !important;
    }

    select {
        appearance: auto !important;
            -webkit-appearance: auto !important;
            -moz-appearance: auto !important;
    }

    .alignRight {
        text-align: right;
    }

    .alignCenter {
        text-align: center;
    }

    .towin {
        text-align: right;
        width: 58px;
    }

    .totalPLdata {
        font-size: 15px;
    }

    .totalPLlabel {
        right: 138px;
    }

    .greenPL {
        color: #009E28 !important;
    }

    .redPL {
        color: #FF2525 !important;
    }

    .betSelection {
        width: 400px;
    }

    .provider {
        width: 60px;
    }

    .score {
        width: 60px;
    }

    .status {
        width: 100px;

        font-weight: 700;
    }

    .transactionDate {
        width: 185px;
    }

    th.stakeValues {
        text-align: center;
    }

    .stakeValues {
        width: 100px;
    }

    .betData {
        width: 48px;
    }

    .betId {
        width: 120px;
    }

    button, input, select, textarea {
        border-style: solid !important;
    }

    .betdata-btn {
        color: #444444 !important;
    }

    .v-autocomplete i {
        display: none !important;
    }

    .v-data-table__wrapper {
        tr.v-row-group__header th,
        tr.v-row-group__header td {
            background: #2D3748 !important;
            color: #FFFFFF !important;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .v-row-group__header td,
        th, td {
            padding: 0.5rem !important;

            font-size: 14px !important;
        }

        th {
            background: #ED8936 !important;
            color: #FFFFFF !important;
            font-weight: bold;
            text-transform: uppercase;
            vertical-align: bottom;

            span {
                display: inline-block;
            }

            &.sortable span {
                margin-top: 1.5px;
            }

            i.v-icon {
                color: #FFFFFF !important;
            }
        }

        td {
            font-size: 14px !important;
        }
    }

    tr._failed {
        td {
            &:first-child {
                box-shadow: inset 5px 0px 0px 0px #FF2525 !important;
            }

            &:nth-child(8) {
                color: #FF2525 !important;
            }
        }
    }

    tr._green {
        td {
            &:first-child {
                box-shadow: inset 5px 0px 0px 0px #009E28 !important;
            }

            &:nth-child(8) {
                color: #009E28 !important;
            }
        }
    }

    .v-application--wrap {
        min-height: auto !important;
    }
</style>
