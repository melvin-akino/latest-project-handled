<template>
    <div class="trade">
        <div class="flex">
            <div class="w-1/6">
                <div class="h-screen fixed sidebar bg-gray-800 w-1/6 pr-4">
                    <Wallet></Wallet>
                    <Watchlist :watchlist="watchlist"></Watchlist>
                    <Sports></Sports>
                    <Leagues></Leagues>
                </div>
            </div>

            <div class="w-5/6 h-full">
                <Columns></Columns>
                <div class="gameScheds">
                    <Games gameSchedType="watchlist" :games="watchlist"></Games>
                    <Games gameSchedType="in-play" :games="watchlist"></Games>
                    <Games gameSchedType="today"></Games>
                    <Games gameSchedType="early"></Games>
                </div>
            </div>
        </div>
        <Betbar></Betbar>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import Sports from './Sports'
import Wallet from './Wallet'
import Watchlist from './Watchlist'
import Leagues from './Leagues'
import Columns from './Columns'
import Games from './Games'
import Betbar from './Betbar'

export default {
    components: {
        Sports,
        Wallet,
        Watchlist,
        Leagues,
        Columns,
        Games,
        Betbar
    },
    data () {
        return {
            watchlist: []
        }
    },
    head: {
        title() {
            return {
                inner: 'Trade'
            }
        }
    },
    mounted() {
        this.getWatchlistData()
    },
    methods: {
        getWatchlistData() {
            let token = Cookies.get('access_token')

            axios.get('v1/trade/watchlist', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => this.watchlist = response.data.data)
            .catch(err => this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status))
        }
    }
}
</script>

<style lang="scss">
    .betbar {
        transition: all 0.3s;
    }

    .gameScheds {
        margin-top: 52px;
    }
</style>
