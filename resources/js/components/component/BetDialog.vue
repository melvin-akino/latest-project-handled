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
                <div class="mt-4 font-bold text-red-600" v-if="mode=='awaitingPlacement'">
                    <p><i class="fas fa-exclamation-triangle mr-2"></i>YES <span class="font-normal">may result in placing duplicate bet(s).</span></p>
                    <span>
                        <label>
                            <input class="mr-1 leading-tight" type="checkbox" v-model="disable" @change="disableAwaitingPlacement">
                            Don't ask me again.
                        </label>
                    </span>
                </div>
            </div>
        </dialog-drag>
    </div>
</template>

<script>
import DialogDrag from 'vue-dialog-drag'
import { mapState } from 'vuex'
import { moneyFormat, twoDecimalPlacesFormat } from '../../helpers/numberFormat'
import Cookies from 'js-cookie'

export default {
    props: ['message', 'mode', 'oldBet', 'bet'],
    components: {
        DialogDrag
    },
    data() {
        return {
            disable: false,
            options: {
                width:550,
                buttonPin: false,
            }
        }
    },
    computed: {
        ...mapState('trade', ['activePopup', 'popupZIndex', 'betSlipSettings']),
        ...mapState('settings', ['defaultPriceFormat'])
    },
    methods: {
        disableAwaitingPlacement() {
            let token = Cookies.get('mltoken')
            let awaitingPlacement = this.disable ? '0' : '1'
            let data = {
                use_equivalent_bets: this.betSlipSettings.use_equivalent_bets,
                offers_on_exchanges: this.betSlipSettings.offers_on_exchanges,
                adv_placement_opt: this.betSlipSettings.adv_placement_opt,
                bets_to_fav: this.betSlipSettings.bets_to_fav,
                adv_betslip_info: this.betSlipSettings.adv_betslip_info,
                tint_bookies: this.betSlipSettings.tint_bookies,
                adaptive_selection: this.betSlipSettings.adaptive_selection,
                awaiting_placement_msg: awaitingPlacement
            }

            this.$store.commit('trade/UPDATE_BET_SLIP_SETTINGS', { key: 'awaiting_placement_msg', value: awaitingPlacement })

            axios.post('/v1/user/settings/bet-slip', data, { headers: { 'Authorization': `Bearer ${token}` } })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        }
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
