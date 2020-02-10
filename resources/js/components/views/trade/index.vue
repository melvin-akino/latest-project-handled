<template>
    <div class="trade">
        <div class="flex">
            <Sports></Sports>
            <div class="flex flex-col bg-gray-800 w-3/12 h-screen xl:w-2/12 fixed sidebar">
                <div class="flex flex-col px-6 py-2">
                    <Wallet wallet-title="Credit" wallet-data="$ 1,000.00"></Wallet>
                    <Wallet wallet-title="PL" wallet-data="$ 500.00"></Wallet>
                    <Wallet wallet-title="Open Orders" wallet-data="$ 100.00"></Wallet>
                </div>
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
        <div class="betbar flex flex-col w-full bg-gray-900 left-0 bottom-0 fixed z-10 overflow-y-scroll" :class="[isBetBarOpen ? 'h-48': 'h-10']">
            <div class="text-center text-white h-10 pt-2 cursor-pointer" :class="{'border-b border-white': isBetBarOpen}" @click="isBetBarOpen = !isBetBarOpen">
                Recent Orders
                <span v-show="isBetBarOpen"><i class="fas fa-chevron-down"></i></span>
                <span v-show="!isBetBarOpen"><i class="fas fa-chevron-up"></i></span>
            </div>
        </div>
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

export default {
    components: {
        Wallet,
        Sports,
        Leagues,
        Columns,
        Games
    },
    data() {
        return {
            isBetBarOpen: false
        }
    },
    head: {
        title() {
            return {
                inner: 'Trade'
            }
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
</style>
