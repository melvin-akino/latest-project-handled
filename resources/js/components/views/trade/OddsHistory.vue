<template>
    <div class="oddsHistory text-sm">
        <dialog-drag title="Order Logs" :options="options" @close="$emit('close')" v-order-logs="activeBetSlip==market_id">
            <div class="flex flex-col">
                <div class="bg-gray-800 w-full p-2">
                    <div class="container mx-auto">
                        <p class="text-white">Order logs for Market: {{market_id}}</p>
                    </div>
                </div>
                <div class="flex flex-col orderLogs">
                    <div class="pl-2 py-4 text-gray-700" v-if="!retrievedOrderLogs">
                        {{orderLogsPrompt}}
                        <span class="text-sm pl-1" v-show="isLoadingOrderLogs"><i class="fas fa-circle-notch fa-spin"></i></span>
                        <span class="text-sm pl-1" v-show="!isLoadingOrderLogs"><a href="#" class="text-sm underline" @click="getOrderLogs">Click here to try again</a></span>
                    </div>
                    <div v-else>
                        <div class="flex px-3 my-1 text-gray-700" v-for="(log, index) in logs" :key="index">
                            <div class="w-1/2">
                                <div class="text-sm">{{index}}</div>
                            </div>
                            <div class="text-sm w-1/2">
                                <div v-for="(logType, index) in log" :key="index">
                                    <div v-for="(update, index) in logType" :key="index">
                                        <span class="font-bold">{{index}}</span> - {{update.description}} to {{update.data}}
                                    </div>
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
    props: ['market_id'],
    components: {
        DialogDrag
    },
    data() {
        return {
            options: {
                width:550,
                buttonPin: false,
            },
            logs: [],
            isLoadingOrderLogs: true,
            retrievedOrderLogs: false,
            orderLogsPrompt: 'Loading order logs'
        }
    },
    computed: {
        ...mapState('trade', ['activeBetSlip'])
    },
    watch: {
        market_id() {
            this.setOrderLogs()
        }
    },
    mounted() {
        this.setOrderLogs()
    },
    methods: {
        async setOrderLogs() {
            try {
                let orderLogs = await this.$store.dispatch('trade/getOrderLogs', this.market_id)
                this.logs = orderLogs
                this.isLoadingOrderLogs = false
                this.retrievedOrderLogs = true
                this.orderLogsPrompt = ''
            } catch(err) {
                this.isLoadingOrderLogs = false
                if(err.response.data.status_code == 504) {
                    this.orderLogsPrompt = 'Cannot display order logs.'
                } else {
                    this.orderLogsPrompt = 'There was an error.'
                }
            }
        },
        getOrderLogs() {
            this.isLoadingOrderLogs = true
            this.orderLogsPrompt = 'Loading order logs'
            this.setOrderLogs()
        }
    },
    directives: {
        orderLogs: {
            bind(el, binding, vnode) {
                if(binding.value) {
                    el.style.zIndex = '152'
                } else {
                    el.style.zIndex = '103'
                }

                let { $set, options } = vnode.context
                $set(options, 'top', window.innerHeight / 2)
                $set(options, 'left', window.innerWidth / 2)
            },
            componentUpdated(el, binding, vnode) {
                if(binding.value) {
                    el.style.zIndex = '152'
                } else {
                    el.style.zIndex = '103'
                }

                el.style.marginTop = 'calc(316px / 2 * -1)'
                el.style.marginLeft = `calc(${el.offsetWidth}px / 2 * -1)`
            }
        }
    }
}
</script>

<style>
    .orderLogs {
        padding: 0;
        max-height: 440px;
        overflow-y: auto;
    }
</style>
