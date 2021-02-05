<style lang="scss">
    .user-wallet-info {
        td {
            padding: 0rem 0.25rem;
            vertical-align: middle;

            color: #ABABAB;
            font-size: 0.8rem;

            &:first-child {
                width: 30%;

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
    }
</style>

<template>
    <div class="additional-filters grid grid-cols-5 content-center mt-2">
        <!-- Grid: Additional Filters -->
        <div class="col-span-1 px-2">
            <label class="font-bold text-sm uppercase" for="period">Period</label><br />
            <select class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase" disabled id="peiod">
                <option selected>This Week</option>
            </select><br />

            <label class="font-bold text-sm uppercase">Date Covered</label><br />
            <div class="inline-block w-1/2">
                <span class="bg-white rounded-sm p-1 absolute text-xs uppercase text-center bg-gray-400" style="margin-top: 5px; margin-left: 5px; width: 40px;">From</span>
                <input class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase tracking-wide select-none" disabled style="padding-left: 3.25rem;" @keyup="getMyOrders(form)" v-model="form.date_from">
            </div>
            <div class="inline-block w-1/2" style="margin-left: -4px;">
                <span class="bg-white rounded-sm p-1 absolute text-xs uppercase text-center bg-gray-400" style="margin-top: 5px; margin-left: 5px; width: 40px;">To</span>
                <input class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase tracking-wide select-none" disabled style="padding-left: 3.25rem;" @keyup="getMyOrders(form)" v-model="form.date_to">
            </div>
            <br />

            <label class="font-bold text-sm uppercase" for="groupBy">Group By</label><br />
            <select class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase" @change="getMyOrders(form)" v-model="form.group_by" id="groupBy">
                <option value="date" selected>Date</option>
                <option value="leaguename">League</option>
            </select>
        </div>

        <!-- Grid: Additional History Filters -->
        <div class="col-span-1 px-2" v-if="ordersPage.includes('history')">
            <label class="font-bold text-sm uppercase" for="period">Search By</label><br />
            <select class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase" @change="getMyOrders(form)" v-model="form.search_by" id="peiod">
                <option value="league_names" selected>League Names</option>
                <option value="team_names">Team Names</option>
            </select><br />

            <label class="font-bold text-sm uppercase">Search</label><br />
            <input class="w-full border border-gray-400 rounded-sm p-2 text-xs uppercase" @change="getMyOrders(form)" v-model="form.search_keyword">

            <!-- Button Group -->
            <label class="font-bold text-sm uppercase">Export</label><br />
            <button class="w-auto border-2 border-gray-400 hover:border-green-600 hover:bg-green-600 hover:text-white rounded-full px-4 py-2 text-xs transition ease-in-out duration-100 select-none focus:outline-none tracking-wide">
                <i class="fas fa-download mr-2"></i> <strong>EXCEL</strong> (.xlsx)
            </button>
            <button class="w-auto border-2 border-gray-400 hover:border-blue-600 hover:bg-blue-600 hover:text-white rounded-full px-4 py-2 text-xs transition ease-in-out duration-100 select-none focus:outline-none tracking-wide">
                <i class="fas fa-download mr-2"></i> <strong>CSV FILE</strong> (.csv)
            </button>
        </div>

        <!-- Grid: Separator -->
        <div class="col-span-2 px-2" v-if="ordersPage.includes('history')">&nbsp;</div>
        <div class="col-span-3 px-2" v-if="!ordersPage.includes('history')">&nbsp;</div>

        <!-- Grid: User Wallet Information -->
        <div class="col-span-1 px-2 pt-6">
            <table class="user-wallet-info w-full">
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
    import { mapState, mapActions } from 'vuex'
    import { twoDecimalPlacesFormat, moneyFormat } from '../../../helpers/numberFormat'

    export default {
        computed: {
            ...mapState('trade', ['wallet']),
            ...mapState('settings', ['defaultTimezone']),
        },
        data() {
            return {
                form: {
                    group_by: 'date',
                    search_by: '',
                    search_keyword: '',
                    date_from: this.getWeekDates.date_from,
                    date_to: this.getWeekDates.date_to,
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
            ...mapActions('orders', ['getMyOrders']),
        },
        mounted() {
            this.getMyOrders(this.form)
        },
        props: ['totalPL', 'ordersPage', 'getWeekDates'],
        updated() {

        },
    }
</script>
