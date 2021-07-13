<template>
    <div class="flex flex-col pb-4 pt-2 my-2 ml-4 bg-white shadow-xl border-t-8 border-orange-500">
        <div class="px-6">
            <div class="flex justify-between">
                <span class="text-sm">Credit</span>
                <span class="text-sm">{{wallet.currency_symbol}} {{wallet.credit | moneyFormat}}</span>
            </div>
            <div class="flex justify-between">
                <p class="text-sm">Profit/Loss</p>
                <p class="text-sm">{{wallet.currency_symbol}} {{wallet.profit_loss  | moneyFormat}}</p>
            </div>
            <div class="flex justify-between">
                <router-link to="/orders" class="text-sm text-orange-500 underline">Open Orders</router-link>
                <p class="text-sm">{{wallet.currency_symbol}} {{wallet.orders  | moneyFormat}}</p>
            </div>
        </div>
    </div>
</template>

<script>
import Cookies from 'js-cookie'
import { mapState } from 'vuex';
import { moneyFormat } from '../../../helpers/numberFormat'

export default {
    computed: {
        ...mapState('trade', ['wallet'])
    },
    mounted() {
        this.$store.dispatch('trade/getWalletData')
    },
    filters: {
        moneyFormat
    }
}
</script>

<style>

</style>
