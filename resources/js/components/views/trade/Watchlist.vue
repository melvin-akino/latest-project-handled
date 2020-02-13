<template>
    <div class="watchlist mb-4 ml-4 shadow-xl">
        <div class="flex flex-col text-white bg-orange-500 py-2 px-6 rounded-lg overflow-hidden show-watchlist" :class="[isWatchListOpen ? 'h-full' : 'h-8']">
            <div class="text-center text-sm cursor-pointer" @click="isWatchListOpen = !isWatchListOpen">Watchlist ({{Object.keys(watchlist).length}})
                <span v-show="!isWatchListOpen"><i class="fas fa-chevron-down"></i></span>
                <span v-show="isWatchListOpen"><i class="fas fa-chevron-up"></i></span>
            </div>
            <div class="watchlistLeagues" v-if="watchlist">
                <p class="text-center text-sm my-1 py-1" v-for="(league, index) in watchlist" :key="index">{{index}}</p>
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

<style>

</style>
