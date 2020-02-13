<template>
    <div class="flex flex-col pb-4 pt-2 my-2 ml-4 bg-white shadow-xl border-t-8 border-orange-500">
        <div class="px-6">
            <div class="flex justify-between">
                <span class="text-sm">Credit</span>
                <span class="text-sm">{{currency.symbol}} {{credit}}</span>
            </div>
            <div class="flex justify-between">
                <p class="text-sm">Profit/Loss</p>
                <p class="text-sm">{{currency.symbol}} {{profit_loss}}</p>
            </div>
            <div class="flex justify-between">
                <p class="text-sm">Open Orders</p>
                <p class="text-sm">{{currency.symbol}} {{orders}}</p>
            </div>
        </div>
    </div>
</template>

<script>
import Cookies from 'js-cookie'

export default {
    data() {
        return {
            currency: {},
            credit: '',
            orders: '',
            profit_loss: '',
        }
    },
    mounted() {
        this.getWalletData()
    },
    methods: {
        getWalletData() {
            let token = Cookies.get('access_token')

            axios.get('v1/user/wallet', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                let { currency, credit, orders, profit_loss } = response.data.data
                this.currency = currency
                this.credit = credit.toFixed(2)
                this.orders = orders.toFixed(2)
                this.profit_loss = profit_loss.toFixed(2)

            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status)
            })
        }
    }
}
</script>

<style>

</style>
