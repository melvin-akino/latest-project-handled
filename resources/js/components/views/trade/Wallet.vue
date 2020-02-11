<template>
    <div class="flex flex-col px-6 py-2">
        <div class="flex justify-between">
            <p class="text-white">Credit</p>
            <p class="text-white">{{currency.symbol.trim()}} {{credit.toFixed(2)}}</p>
        </div>
        <div class="flex justify-between">
            <p class="text-white">PL</p>
            <p class="text-white">{{currency.symbol.trim()}} {{profit_loss.toFixed(2)}}</p>
        </div>
        <div class="flex justify-between">
            <p class="text-white">Open Orders</p>
            <p class="text-white">{{currency.symbol.trim()}} {{orders.toFixed(2)}}</p>
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
                this.credit = credit
                this.orders = orders
                this.profit_loss = profit_loss

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
