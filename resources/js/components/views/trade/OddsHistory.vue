<template>
    <div class="oddsHistory">
        <dialog-drag title="Odds History" :options="options" @close="closeOddsHistory(market_id)">
            <div class="flex flex-col">
                <div class="bg-gray-800 w-full p-2">
                    <div class="container mx-auto">
                        <p class="text-white">Orders for Market: {{market_id}}</p>
                    </div>
                </div>

                <div class="flex flex-col">
                    <div class="container mx-auto">
                        <span class="text-sm p-2 font-bold text-gray-800">{{market_details.sport}}: {{market_details.odd_type}} ({{market_details.market_flag}})</span>
                    </div>
                    <div class="order w-full my-1 p-2">
                        <div class="text-sm" v-for="(log, index) in logs" :key="index">
                            <span class="font-bold">{{index}}</span>
                            <div v-if="logs[index].length == 0">No odd updates for this provider yet.</div>
                            <div v-else>
                                <div class="flex justify-between" v-for="(update, index) in log" :key="index">
                                    <p>{{update.created_at}}</p>
                                    <p>Updated odds to <span class="font-bold">{{update.odds}}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </dialog-drag>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import 'vue-dialog-drag/dist/vue-dialog-drag.css'
import DialogDrag from 'vue-dialog-drag'

export default {
    props: ['market_id', 'market_details'],
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
            logs: {}
        }
    },
    computed: {
        ...mapState('trade', ['bookies'])
    },
    mounted() {
        this.getOrderLogs()
    },
    methods: {
        closeOddsHistory(market_id) {
            this.$store.commit('trade/CLOSE_ODDS_HISTORY', market_id)
        },
        getOrderLogs() {
            let token = Cookies.get('mltoken')

            axios.get(`v1/orders/${this.market_id}/logs`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                this.logs = response.data.data
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        },
    }

}
</script>

<style scoped>
    .dialog-drag .dialog-body {
        padding: 0;
        max-height: 400px;
        overflow-y: auto;
    }
</style>
