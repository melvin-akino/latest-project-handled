<template>
    <div class="flex flex-col px-6 py-4 my-4 ml-4 bg-white shadow-xl rounded-lg">
        <div class="flex justify-between">
            <span class="text-sm">Credit</span>
            <span class="text-sm">{{currency.symbol}} {{credit}}</span>
        </div>
        <div class="flex justify-between">
            <p class="text-sm">PL</p>
            <p class="text-sm">{{currency.symbol}} {{profit_loss}}</p>
        </div>
        <div class="flex justify-between">
            <p class="text-sm">Open Orders</p>
            <p class="text-sm">{{currency.symbol}} {{orders}}</p>
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
