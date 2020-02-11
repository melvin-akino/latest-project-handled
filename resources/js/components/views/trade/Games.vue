<template>
    <div class="gameSchedType px-4" :class="[isGameSchedTypeOpen ? 'mb-4' : 'mb-1']">
        <div class="text-white text-center bg-orange-500 rounded-lg cursor-pointer capitalize" @click="isGameSchedTypeOpen = !isGameSchedTypeOpen">
            {{gameSchedType}}
            <span v-show="isGameSchedTypeOpen"><i class="fas fa-chevron-down"></i></span>
            <span v-show="!isGameSchedTypeOpen"><i class="fas fa-chevron-right"></i></span>
        </div>
        <div class="games" :class="{'hidden': !isGameSchedTypeOpen}">
            <p class="text-sm text-gray-500 text-center">No competitions watched in this market.</p>
        </div>
    </div>
</template>

<script>
export default {
    props: ['gameSchedType'],
    data() {
        return {
            isGameSchedTypeOpen: true
        }
    }

}
</script>

<style>

</style>
