<template>
    <div class="container mx-auto my-10">
        <h3 class="text-xl">My Orders</h3>
        <div class="relative">
            <v-client-table name="My Orders" :data="myorders" :columns="columns" :options="options"></v-client-table>
            <div class="absolute text-sm totalPLdata">
                <span class="totalPLlabel">Total P/L</span>
                <span>$ 0.00</span>
            </div>
        </div>
    </div>
</template>

<script>
import Cookies from 'js-cookie'

export default {
    data() {
        return {
            myorders: [],
            columns: ['bet_id', 'created', 'bet_selection', 'provider', 'settled', 'odds', 'stake', 'towin', 'pl'],
            options: {
                headings: {
                    bet_id: 'Bet ID',
                    bet_selection: 'Bet Selection',
                    created: 'Transaction Date & Time',
                    pl: 'Profit/Loss',
                    towin: 'To Win',
                    settled: 'Status'
                }
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
    },
    methods: {
        getMyOrders() {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/all`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.myorders = response.data.data.orders
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
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

    .totalPLlabel {
        margin-right: 53px;
    }

    .totalPLdata {
        right: 132px;
        bottom: 55px;
    }
</style>
