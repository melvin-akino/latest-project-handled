<template>
    <div class="oddsHistory text-sm">
        <dialog-drag title="Order Logs" :options="options" @close="$emit('close')" v-overlap-all-order-logs="activeBetSlip==market_id">
            <div class="flex flex-col">
                <div class="bg-gray-800 w-full p-2">
                    <div class="container mx-auto">
                        <p class="text-white">Order logs for Market: {{market_id}}</p>
                    </div>
                </div>
                <div class="flex flex-col orderLogs">
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
                width:515,
                buttonPin: false,
                centered: "viewport"
            },
            loadingOddsHistory: true
        }
    },
    computed: {
        ...mapState('trade', ['activeBetSlip'])
    },
    directives: {
        overlapAllOrderLogs: {
            bind(el, binding, vnode) {
                if(binding.value) {
                    el.style.zIndex = '152'
                } else {
                    el.style.zIndex = '103'
                }
            },
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
    .orderLogs {
        padding: 0;
        max-height: 440px;
        overflow-y: auto;
    }
</style>
