<template>
    <div class="betbar flex flex-col w-full bg-gray-900 left-0 bottom-0 fixed z-10 overflow-y-scroll" :class="[isBetBarOpen ? 'h-48': 'h-8']">
        <div class="text-center text-white h-10 pt-2 cursor-pointer" :class="{'border-b border-white': isBetBarOpen}" @click="isBetBarOpen = !isBetBarOpen">
            Recent Orders
            <span v-show="isBetBarOpen"><i class="fas fa-chevron-down"></i></span>
            <span v-show="!isBetBarOpen"><i class="fas fa-chevron-up"></i></span>
        </div>
        <div class="flex border-b text-white" v-for="(betData, index) in betDatas" :key="index">
            <div class="w-2/12 text-center py-1 border-r">{{betData.league_name}}</div>
            <div class="w-2/12 text-center py-1 border-r">{{betData.home}} vs {{betData.away}}</div>
            <div class="w-2/12 text-center py-1 border-r">
                <span v-if="betData.bet_info[0]==='home'">{{betData.home}}</span>
                <span v-if="betData.bet_info[0]==='away'">{{betData.away}}</span>
            </div>
            <div class="w-2/12 text-center py-1 border-r">{{betData.bet_info[1]}} {{betData.bet_info[2]}}</div>
            <div class="w-3/12 text-center py-1 border-r">
                {{betData.bet_info[3]}}@{{betData.bet_info[2]}} - {{betData.status}}
            </div>
            <div class="w-1/12 text-center py-1 border-r"><i class="fas fa-chart-area"></i></div>
            <div class="w-1/12 text-center py-1"><i class="fas fa-bars"></i></div>
        </div>
    </div>
</template>

<script>
import Cookies from 'js-cookie'

export default {
    data() {
        return {
            isBetBarOpen: false,
            betDatas: []
        }
    },
    computed: {

    },
    mounted() {
        this.getBetbarData()
    },
    methods: {
        getBetbarData() {
            let token = Cookies.get('access_token')

            axios.get('v1/trade/betbar', { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.betDatas = response.data.data
                if(this.betDatas) {
                    this.isBetBarOpen = true
                }
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
