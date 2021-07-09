<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="flex items-center mb-12 hidden">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="betSlipSettingsForm.use_equivalent_bets" @change="toggleBetSlipSettings(betSlipSettingsForm.use_equivalent_bets, 'use_equivalent_bets')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[betSlipSettingsForm.use_equivalent_bets === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Use equivalent bets</span>
                <p class="text-xs w-7/12 text-left">Try to place equivalent bets with different bet types to what you have requested. Even though these bet types may be different in meaning, the payout grids are identical. For instance, if you asked for under 0.5 goals, we could place a correct score 0-0 bet to try and get more volume</p>
            </div>
            <div class="flex items-center mb-12 hidden">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="betSlipSettingsForm.offers_on_exchanges"  @change="toggleBetSlipSettings(betSlipSettingsForm.offers_on_exchanges, 'offers_on_exchanges')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[betSlipSettingsForm.offers_on_exchanges === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Allow putting offers on exchanges</span>
                <p class="text-xs w-7/12 text-left">If an exchange account is selected and no liquidity is available, we will attempt to put up an offer on that exchange</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="betSlipSettingsForm.adv_placement_opt"  @change="toggleBetSlipSettings(betSlipSettingsForm.adv_placement_opt, 'adv_placement_opt')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[betSlipSettingsForm.adv_placement_opt === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Show advanced placement options</span>
                <p class="text-xs w-7/12 text-left">Show advanced order expiry options, like keeping an order open in running or taking best closing price</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="betSlipSettingsForm.bets_to_fav"  @change="toggleBetSlipSettings(betSlipSettingsForm.bets_to_fav, 'bets_to_fav')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[betSlipSettingsForm.bets_to_fav === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Add bets to favorite events</span>
                <p class="text-xs w-7/12 text-left">Automatically add events that you bet on to your favorites so you can more easily keep track of them</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="betSlipSettingsForm.adv_betslip_info"  @change="toggleBetSlipSettings(betSlipSettingsForm.adv_betslip_info, 'adv_betslip_info')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[betSlipSettingsForm.adv_betslip_info === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Show advanced betslip information</span>
                <p class="text-xs w-7/12 text-left">Select to show expected returns, average price, and minimum & maximum order</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="betSlipSettingsForm.awaiting_placement_msg"  @change="toggleBetSlipSettings(betSlipSettingsForm.awaiting_placement_msg, 'awaiting_placement_msg')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[betSlipSettingsForm.awaiting_placement_msg === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Show awaiting placement message</span>
                <p class="text-xs w-7/12 text-left">Show a message dialog that there is a bet awaiting placement during placing of bet on the same betslip</p>
            </div>
            <div class="flex items-center mb-12 hidden">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="betSlipSettingsForm.tint_bookies"  @change="toggleBetSlipSettings(betSlipSettingsForm.tint_bookies, 'tint_bookies')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[betSlipSettingsForm.tint_bookies === '1' ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Tint bookies</span>
                <p class="text-xs w-7/12 text-left">Lightly tint bookie account backgrounds</p>
            </div>
            <div class="mb-12 hidden">
                <label class="text-sm">Adaptive Selection</label>
                <div class="flex justify-between items-center">
                    <div class="relative w-4/12 mt-4">
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="betSlipSettingsForm.adaptive_selection">
                            <option v-for="adaptive_selection in adaptive_selections" :key="adaptive_selection.id" :value="adaptive_selection.id">{{adaptive_selection.value}}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <p class="text-xs w-7/12">Should orders reselect accounts and bookies during placement ?</p>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-4 py-2">Save Changes</button>
            </div>
        </form>
    </div>
</template>

<script>
import Cookies from 'js-cookie'
import Swal from 'sweetalert2'

export default {
    data() {
        return {
            betSlipSettingsForm: {
                use_equivalent_bets: null,
                offers_on_exchanges: null,
                adv_placement_opt: null,
                bets_to_fav: null,
                adv_betslip_info: null,
                tint_bookies: null,
                adaptive_selection: null,
                awaiting_placement_msg: null
            },
            adaptive_selections: []
        }
    },
    head: {
        title() {
            return {
                inner: 'Settings - Bet Slip'
            }
        }
    },
    mounted() {
        this.getUserConfig()
        this.adaptive_selections = this.$store.state.settings.settingsData['betslip-adaptive-selection']
    },
    methods: {
        getUserConfig() {
            let token = Cookies.get('mltoken')

            axios.get('v1/user/settings/bet-slip', { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                Object.keys(this.betSlipSettingsForm).forEach(field => {
                    this.betSlipSettingsForm[field] = response.data.data[field]
                })
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        },
        toggleBetSlipSettings(isActive, key) {
            if (isActive === '1') {
                this.betSlipSettingsForm[key] = '0'
            } else {
                this.betSlipSettingsForm[key] = '1'
            }
        },
        saveChanges() {
            let token = Cookies.get('mltoken')
            let data = {
                use_equivalent_bets: this.betSlipSettingsForm.use_equivalent_bets,
                offers_on_exchanges: this.betSlipSettingsForm.offers_on_exchanges,
                adv_placement_opt: this.betSlipSettingsForm.adv_placement_opt,
                bets_to_fav: this.betSlipSettingsForm.bets_to_fav,
                adv_betslip_info: this.betSlipSettingsForm.adv_betslip_info,
                tint_bookies: this.betSlipSettingsForm.tint_bookies,
                adaptive_selection: this.betSlipSettingsForm.adaptive_selection,
                awaiting_placement_msg: this.betSlipSettingsForm.awaiting_placement_msg
            }

            axios.post('/v1/user/settings/bet-slip', data, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                Swal.fire({
                    icon: 'success',
                    text: response.data.message
                })
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        }
    }
}
</script>

<style>
    .on-switch {
        left: 24px;
    }
</style>
