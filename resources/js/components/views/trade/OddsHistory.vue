<template>
    <div class="oddsHistory">
        <dialog-drag title="Order Logs" :options="options" @close="closeOddsHistory(odd_details.market_id)">
            <div class="flex flex-col">
                <div class="bg-gray-800 w-full p-2">
                    <div class="container mx-auto">
                        <p class="text-white">Order logs for Market: {{odd_details.market_id}}</p>
                    </div>
                </div>
                <div class="flex flex-col">
                    <div v-if="!loadingOddsHistory">
                        <div class="order w-full my-1" v-for="(log, index) in logs" :key="index">
                            <div class="orderHeading bg-gray-400 p-2 cursor-pointer" @click="toggleOrderLog(index)">
                                <div class="container mx-auto text-sm">{{index}}</div>
                            </div>
                            <div class="container text-sm mx-auto p-2" :class="[openedOrderLog == index ? 'block' : 'hidden']">
                                <div v-for="(logType, index) in log" :key="index">
                                    <div v-for="(update, index) in logType" :key="index">
                                        <span class="font-bold">{{index}}</span> - {{update.description}} to {{update.data}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else>
                        <span class="text-sm p-2 text-gray-800">Loading odds history...</span>
                    </div>
                </div>
            </div>
        </dialog-drag>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import _ from 'lodash'
import 'vue-dialog-drag/dist/vue-dialog-drag.css'
import DialogDrag from 'vue-dialog-drag'

export default {
    props: ['odd_details', 'market_details'],
    components: {
        DialogDrag
    },
    data() {
        return {
            options: {
                width:400,
                buttonPin: false,
                centered: "viewport"
            },
            openedOrderLog: '',
            logs: {},
            loadingOddsHistory: true
        }
    },
    computed: {
        ...mapState('trade', ['bookies'])
    },
    mounted() {
        this.$store.dispatch('trade/getBookies')
        this.getOrderLogs()
    },
    methods: {
        closeOddsHistory(market_id) {
            this.$store.commit('trade/CLOSE_ODDS_HISTORY', market_id)
        },
        toggleOrderLog(orderLog) {
            if(this.openedOrderLog == orderLog) {
                this.openedOrderLog = ''
            } else {
                this.openedOrderLog = orderLog
            }
        },
        getOrderLogs() {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/logs/${this.odd_details.market_id}`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.logs = response.data.data
                this.loadingOddsHistory = false
                let logTimeStamps = Object.keys(response.data.data)
                this.openedOrderLog = logTimeStamps[0]
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        },
    }

}
</script>

<style>
    .oddsHistory .dialog-drag .dialog-body {
        padding: 0;
        max-height: 440px;
        overflow-y: auto;
    }
</style>
