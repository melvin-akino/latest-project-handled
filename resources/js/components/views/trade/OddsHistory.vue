<template>
    <div class="oddsHistory">
        <dialog-drag title="Order Logs" :options="options" @close="$emit('close')" v-overlap-all-order-logs="activeBetSlip==market_id">
            <div class="flex flex-col">
                <div class="bg-gray-800 w-full p-2">
                    <div class="container mx-auto">
                        <p class="text-white">Order logs for Market: {{market_id}}</p>
                    </div>
                </div>
                <div class="flex flex-col">
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
    props: ['market_id', 'logs'],
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
            loadingOddsHistory: true
        }
    },
    computed: {
        ...mapState('trade', ['activeBetSlip'])
    },
    watch: {
        logs() {
            this.setOpenedOrderLog()
        }
    },
    mounted() {
        this.setOpenedOrderLog()
    },
    methods: {
        toggleOrderLog(orderLog) {
            if(this.openedOrderLog == orderLog) {
                this.openedOrderLog = ''
            } else {
                this.openedOrderLog = orderLog
            }
        },
        setOpenedOrderLog() {
            let logTimeStamps = Object.keys(this.logs)
            this.openedOrderLog = logTimeStamps[0]
        }
    },
    directives: {
        overlapAllOrderLogs: {
            componentUpdated(el, binding, vnode) {
                if(binding.value) {
                    el.style.zIndex = '152'
                } else {
                    el.style.zIndex = '103'
                }
            }
        }
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
