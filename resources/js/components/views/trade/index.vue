<template>
    <div class="trade">
        <div class="flex">
            <div class="w-1/5">
                <Sports></Sports>
                <div class="h-screen fixed sidebar w-1/6 pr-3">
                    <Wallet></Wallet>
                    <Watchlist :watchlist="watchlist"></Watchlist>
                    <Leagues></Leagues>
                </div>
            </div>

            <div class="w-4/5 h-full">
                <Columns></Columns>
                <div class="gameScheds overflow-x-hidden overflow-y-scroll">
                    <Games gameSchedType="watchlist" :games="watchlist"></Games>
                    <Games gameSchedType="in-play"></Games>
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
    .sidebar {
        margin-left: 63px;
    }

    .betbar {
        transition: all 0.3s;
    }

    .gameScheds {
        margin-top: 52px;
    }
</style>
