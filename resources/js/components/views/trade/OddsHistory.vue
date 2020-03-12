<template>
    <div class="oddsHistory">
        <dialog-drag v-for="market_id in openedOddsHistory" :key="market_id" :title="'Odds History - '+market_id" :options="options" @close="closeOddsHistory(market_id)">
            <div class="flex flex-col">
                <!-- hard coded data, should be dynamic -->
                <div class="py-2 bg-gray-800 w-full p-2">
                    <div class="container mx-auto">
                        <p class="text-white">Order 69</p>
                    </div>
                </div>

                <div class="flex flex-col">
                    <div class="container mx-auto">
                        <span class="text-sm py-2 px-2 font-bold text-gray-800">Football: FT HDP</span>
                    </div>
                    <div class="order w-full my-1">
                        <div class="orderHeading bg-gray-400 p-2 cursor-pointer">
                            <div class="container mx-auto text-sm">Order placed at 03/02/2020, 10:10:10 AM</div>
                        </div>
                        <div class="container mx-auto p-2">
                            <div class="flex justify-between">
                                <span class="text-sm">10:10:11 AM</span>
                                <span class="text-sm text-left">PIN - updated price to 6.90</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm">10:10:12 AM</span>
                                <span class="text-sm text-left">ISN - updated price to 4.20</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm">10:10:13 AM</span>
                                <span class="text-sm text-left">HG - updated price to 6.90</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm">10:10:14 AM</span>
                                <span class="text-sm text-left">SBC - updated price to 4.20</span>
                            </div>
                        </div>
                    </div>
                    <div class="order w-full my-1">
                        <div class="orderHeading bg-gray-400 p-2 cursor-pointer">
                            <div class="container mx-auto text-sm">Order placed at 03/02/2020, 10:10:15 AM</div>
                        </div>
                        <div class="container mx-auto p-2">
                            <div class="flex justify-between">
                                <span class="text-sm">10:10:15 AM</span>
                                <span class="text-sm text-left">PIN - updated price to 6.90</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm">10:10:16 AM</span>
                                <span class="text-sm text-left">ISN - updated price to 4.20</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm">10:10:17 AM</span>
                                <span class="text-sm text-left">HG - updated price to 6.90</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm">10:10:18 AM</span>
                                <span class="text-sm text-left">SBC - updated price to 4.20</span>
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
import 'vue-dialog-drag/dist/vue-dialog-drag.css'
import DialogDrag from 'vue-dialog-drag'

export default {
    components: {
        DialogDrag
    },
    data() {
        return {
            options: {
                width:400,
                height:400,
                buttonPin: false,
                dropEnabled: false,
                centered: "viewport"
            }
        }
    },
    computed: {
        ...mapState('trade', ['openedOddsHistory'])
    },
    methods: {
        closeOddsHistory(market_id) {
            this.$store.commit('trade/CLOSE_ODDS_HISTORY', market_id)
        }
    }

}
</script>

<style scoped>
    .dialog-drag .dialog-body {
        padding: 0;
    }
</style>
