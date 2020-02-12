<template>
    <div class="trade">
        <div class="flex">
            <Sports></Sports>
            <div class="flex flex-col bg-gray-800 w-3/12 h-screen xl:w-2/12 fixed sidebar">
                <Wallet></Wallet>
                <div class="show-watchlist">
                    <div class="text-white text-center bg-orange-500 py-1">Watchlist <i class="fas fa-play text-xs"></i></div>
                </div>
                <Leagues></Leagues>
            </div>

            <div class="w-3/12"></div>
            <div class="w-full h-full">
                <Columns></Columns>
                <div class="gameScheds pt-4">
                    <Games gameSchedType="watchlist"></Games>
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
import Wallet from './Wallet'
import Sports from './Sports'
import Leagues from './Leagues'
import Columns from './Columns'
import Games from './Games'
import Betbar from './Betbar'

export default {
    components: {
        Wallet,
        Sports,
        Leagues,
        Columns,
        Games,
        Betbar
    },
    head: {
        title() {
            return {
                inner: 'Trade'
            }
        }
    },
    mounted() {
        // this.$socket.emit('teasd')
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
</style>
