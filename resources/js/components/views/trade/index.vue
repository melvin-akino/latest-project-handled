<template>
    <div class="trade">
        <div class="flex" v-adjust-trade-window-height="isBetBarOpen">
            <div class="w-1/6">
                <div class="fixed sidebar bg-gray-800 w-1/6 h-screen pr-4">
                    <Wallet></Wallet>
                    <Watchlist :watchlist="watchlist"></Watchlist>
                    <Sports></Sports>
                    <Leagues></Leagues>
                </div>
            </div>

            <div class="w-5/6">
                <Columns></Columns>
                <div class="gameScheds pb-4">
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
    computed: {
        ...mapState('trade', ['isBetBarOpen'])
    },
    mounted() {
        this.getWatchlistData()
    },
    methods: {
        getWatchlistData() {
            let token = Cookies.get('mltoken')

            axios.get('v1/trade/watchlist', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => this.watchlist = response.data.data)
            .catch(err => this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code))
        }
    },
    directives: {
        adjustTradeWindowHeight: {
            update(el, binding, vnode) {
                if(binding.value) {
                    el.style.height = 'calc(100vh - 256px)'
                    el.style.overflowY = 'auto'
                } else {
                    el.style.height = 'calc(100vh - 104px)'
                }
            }
        }
    }
}
</script>

<style lang="scss">
    .gameScheds {
        margin-top: 52px;
    }
</style>
