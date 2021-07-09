<template>
    <div class="betDialog">
        <dialog-drag :options="options" @mousedown.native="$store.dispatch('trade/setActivePopup', $vnode.key)" v-bet-dialog="activePopup==$vnode.key">
            <div class="text-center p-4">
                <h4 class="text-md font-bold pb-2">{{message}}</h4>
                <p class="pb-2 mb-2 border-b border-gray-500">{{bet.home}} vs {{bet.away}}</p>
                <div class="font-bold pb-2">
                    <span v-if="bet.bet_team === '' && bet.market_flag === 'HOME'">{{bet.home}}</span>
                    <span v-if="bet.bet_team === '' && bet.market_flag === 'AWAY'">{{bet.away}}</span>
                    <span v-if="bet.bet_team === '' && bet.market_flag === 'DRAW'">Draw</span>
                    <span v-if="bet.bet_team != ''">{{ bet.bet_team }}</span>
                    <span v-if="bet.bet_team === ''">{{bet.odd_type_name}} {{defaultPriceFormat}} {{bet.odd_label}} {{ `(${bet.score_on_bet})` }}</span>
                    <span v-if="bet.bet_team != ''">{{ bet.odd_type_name.includes("FT") ? "FT " : (bet.odd_type_name.includes("HT") ? "HT " : "") }}{{ bet.bet_team }} {{ defaultPriceFormat }} {{ `(${bet.score_on_bet})` }}</span>
                </div>
                <div class="pb-2 font-bold">
                    <span class="mr-2" v-if="mode=='oddsHaveChanged'">{{Number(oldBet.stake) | moneyFormat}} @ {{oldBet.odds | twoDecimalPlacesFormat}} on {{oldBet.provider_alias}}</span>
                    <i class="fas fa-long-arrow-alt-right" v-if="mode=='oddsHaveChanged'"></i>
                    <span class="ml-2">{{Number(bet.stake) | moneyFormat}} @ {{bet.odds | twoDecimalPlacesFormat}} on {{bet.provider_alias}}</span>
                </div>
                <p class="pb-2 font-bold">Would you like to place another bet?</p>
                <div class="flex justify-center items-center">
                    <button @click="$emit('close')" class="rounded w-20 mr-6 bg-red-600 hover:bg-red-800 text-white text-sm uppercase px-4 py-2">Cancel</button>
                    <button @click="$emit('confirm')" class="rounded w-20 bg-blue-400 hover:bg-blue-600 text-white text-sm uppercase px-4 py-2">Yes</button>
                </div>
            </div>
        </dialog-drag>
    </div>
</template>

<script>
import DialogDrag from 'vue-dialog-drag'
import { mapState } from 'vuex'
import { moneyFormat, twoDecimalPlacesFormat } from '../../helpers/numberFormat'

export default {
    props: ['message', 'mode', 'oldBet', 'bet'],
    components: {
        DialogDrag
    },
    data() {
        return {
            options: {
                width:550,
                buttonPin: false,
            }
        }
    },
    computed: {
        ...mapState('trade', ['activePopup', 'popupZIndex']),
        ...mapState('settings', ['defaultPriceFormat'])
    },
    directives: {
        betDialog: {
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
    },
    filters: {
        moneyFormat,
        twoDecimalPlacesFormat
    }
}
</script>

<style>
.betDialog .dialog-header {
    display: none;
}

.betDialog .dialog-body {
    background-color: #ffffff;
    color: #4a5568;
}
</style>
