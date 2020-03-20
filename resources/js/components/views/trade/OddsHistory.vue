<template>
    <div class="oddsHistory">
        <dialog-drag title="Odds History" :options="options" @close="closeOddsHistory(odd_details.market_id)">
            <div class="flex flex-col">
                <div class="bg-gray-800 w-full p-2">
                    <div class="container mx-auto">
                        <p class="text-white">Orders for Market: {{odd_details.market_id}}</p>
                    </div>
                </div>

                <div class="flex flex-col">
                    <div class="container mx-auto">
                        <span class="text-sm p-2 font-bold text-gray-800">{{market_details.sport}}: {{market_details.odd_type}} ({{market_details.market_flag}})</span>
                    </div>
                    <div v-if="!loadingOddsHistory">
                        <div class="order w-full my-1" v-for="(oddHistory, index) in groupedByDateLogs" :key="index">
                            <div class="orderHeading bg-gray-400 p-2 cursor-pointer">
                                <div class="container mx-auto text-sm">Order placed at {{index}}</div>
                            </div>
                            <div class="container mx-auto p-2">
                                <div class="flex justify-between" v-for="oddUpdate in oddHistory" :key="oddUpdate.id">
                                    <span class="text-sm">{{oddUpdate.created_at}}</span>
                                    <span class="text-sm text-left">{{oddUpdate.provider}} - updated price to {{oddUpdate.odds}}</span>
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
            logs: [],
            groupedByDateLogs: [],
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
        getOrderLogs() {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/${this.odd_details.market_id}/logs`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                Object.keys(response.data.data).map(provider => {
                    response.data.data[provider].map(log => {
                        this.$set(log, 'provider', provider)
                        this.logs.push(log)
                    })
                })
                this.groupedByDateLogs = _.groupBy(this.logs, 'created_at')
                this.loadingOddsHistory = false
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
        max-height: 400px;
        overflow-y: auto;
    }
</style>
