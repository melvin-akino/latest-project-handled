<template>
    <div class="watchlist mb-2 ml-4 shadow-xl">
        <div class="flex flex-col overflow-hidden show-watchlist" :class="[isWatchListOpen ? 'h-full' : 'h-8']">
            <div class="text-center text-white text-sm cursor-pointer py-2 bg-orange-500" @click="isWatchListOpen = !isWatchListOpen">Watchlist ({{Object.keys(watchlist).length}})
                <span v-show="!isWatchListOpen"><i class="fas fa-chevron-down"></i></span>
                <span v-show="isWatchListOpen"><i class="fas fa-chevron-up"></i></span>
            </div>
            <div class="watchlistLeagues bg-white" v-if="watchlist">
                <p class="text-left text-sm py-1 px-6" v-for="(league, index) in watchlist" :key="index">{{index}} &nbsp; ({{league.length}})</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['watchlist'],
    data() {
        return {
            isWatchListOpen: true
        }
    }
}
</script>
