<template>
    <div class="oddsHistory text-sm">
        <dialog-drag title="Order Logs" :options="options" @close="$emit('close')" @mousedown.native="$store.dispatch('trade/setActivePopup', $vnode.key)" v-order-logs="activePopup==$vnode.key">
            <div class="flex flex-col">
                <div class="bg-gray-800 w-full px-2">
                    <div class="container mx-auto">
                        <p class="text-white">Order logs for Market ID: {{market_id}}</p>
                    </div>
                </div>
                <div class="flex flex-col orderLogs">
                    <div class="pl-2 py-4 text-gray-700" v-if="!retrievedOrderLogs">
                        {{orderLogsPrompt}}
                        <span class="text-sm pl-1" v-show="isLoadingOrderLogs"><i class="fas fa-circle-notch fa-spin"></i></span>
                        <span class="text-sm pl-1" v-show="!isLoadingOrderLogs"><a href="#" class="text-sm underline" @click="getOrderLogs">Click here to try again</a></span>
                    </div>
                    <div v-else>
                        <div v-if="logs.length != 0">
                            <div class="flex px-3 my-2 text-gray-700" v-for="(log, index) in logs" :key="index">
                                <div class="w-2/6">{{log.created_at}}</div>
                                <div class="w-2/6">
                                  <p>{{log.bet_team}} {{log.odds_label}} ({{defaultPriceFormat}}, {{log.score}})</p>
                                  <p>{{log.stake}} @ {{log.odds}}</p>
                                </div>
                                <div class="w-1/6 text-right">{{log.provider}}</div>
                                <div class="w-1/6 text-right"><span class="font-bold">{{statusRename(log.status)}}</span></div>
                            </div>
                        </div>
                        <div class="flex justify-center items-center p-2" v-else>
                            <span class="text-sm">No market updates/order logs for this odds yet.</span>
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
import DialogDrag from 'vue-dialog-drag'
export default {
    props: ['market_id', 'event_id'],
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
        ...mapState('trade', ['activePopup', 'popupZIndex']),
        ...mapState('settings', ['defaultPriceFormat']),
    },
    mounted() {
        this.getOrderLogs()
    },
    methods: {
        async getOrderLogs() {
            try {
                this.isLoadingOrderLogs = true
                this.orderLogsPrompt = 'Loading order logs'
                this.logs = await this.$store.dispatch('trade/getOrderLogs', this.market_id)
                this.isLoadingOrderLogs = false
                this.retrievedOrderLogs = true
                this.orderLogsPrompt = ''
            } catch(err) {
                this.isLoadingOrderLogs = false
                if(err.response.status == 504) {
                    this.orderLogsPrompt = 'Cannot display order logs.'
                } else {
                    this.orderLogsPrompt = 'There was an error.'
                }
            }
        },
        statusRename(status) {
            let betStatus = status.toLowerCase()
            let statusOverride = {
                success: "PLACED",
                pending: "ON QUEUE",
                failed:  "FAILED"
            }

            let statusOverrideKeys = Object.keys(statusOverride)

            if(statusOverrideKeys.includes(betStatus)) {
                return statusOverride[betStatus]
            } else {
                return status
            }
        }
    },
    directives: {
        orderLogs: {
            bind(el, binding, vnode) {
                let { $set, options, popupZIndex } = vnode.context
                $set(options, 'top', window.innerHeight / 2)
                $set(options, 'left', window.innerWidth / 2)
                el.style.zIndex = popupZIndex
            },
            componentUpdated(el, binding, vnode) {
                if(binding.value) {
                    el.style.zIndex = vnode.context.popupZIndex
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
