<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="flex items-center mb-12 hidden">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none" :value="tradePageSettingsForm.suggested" @change="toggleTradeSettings(tradePageSettingsForm.suggested, 'suggested')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[tradePageSettingsForm.suggested === '1' ? 'on-switch bg-orange-500' :  'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Suggested competitions/events</span>
                <p class="text-xs w-7/12 text-left">Mark competitions and events that provide good betting opportunities with a <i class="text-orange-500 fas fa-fire-alt"></i></p>
            </div>
            <div class="flex items-center mb-12 hidden">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none"  :value="tradePageSettingsForm.trade_background" @change="toggleTradeSettings(tradePageSettingsForm.trade_background, 'trade_background')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[tradePageSettingsForm.trade_background === '1' ? 'on-switch bg-orange-500' :  'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Color trade market background</span>
                <p class="text-xs w-7/12 text-left">Lightly tint the background of event markets</p>
            </div>
            <div class="flex items-center mb-12 hidden">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none"  :value="tradePageSettingsForm.hide_comp_names_in_fav" @change="toggleTradeSettings(tradePageSettingsForm.hide_comp_names_in_fav, 'hide_comp_names_in_fav')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[tradePageSettingsForm.hide_comp_names_in_fav === '1' ? 'on-switch bg-orange-500' :  'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Hide competition names in favorites</span>
                <p class="text-xs w-7/12 text-left">You can disable the competition names in the favorites market to save vertical space. If you do this they will automatically be sorted by time regardless of your normal trade sort.</p>
            </div>
            <div class="flex items-center mb-12 hidden">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none"  :value="tradePageSettingsForm.live_position_values" @change="toggleTradeSettings(tradePageSettingsForm.live_position_values, 'live_position_values')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[tradePageSettingsForm.live_position_values === '1' ? 'on-switch bg-orange-500' :  'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Live position values</span>
                <p class="text-xs w-7/12 text-left">Select to show Position button with current expected profit / loss based on live score. This will poll your current position and update live based on bets placed and score changes. Disabling this will restore the original position button.</p>
            </div>
            <div class="flex items-center mb-12 hidden">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4 focus:outline-none"  :value="tradePageSettingsForm.hide_exchange_only" @change="toggleTradeSettings(tradePageSettingsForm.hide_exchange_only, 'hide_exchange_only')">
                    <span class="absolute shadow shadow-inner w-6 h-6 rounded-full" :class="[tradePageSettingsForm.hide_exchange_only === '1' ? 'on-switch bg-orange-500' :  'left-0 bg-white']"></span>
                </label>
                <span class="w-4/12 text-sm">Hide exchange only lines</span>
                <p class="text-xs w-7/12 text-left">Hide markets and handicap lines that only use exchange offers. This will show only bookie backed prices.</p>
            </div>
            <div class="mb-12">
                <label class="text-sm">Trade Layout</label>
                <div class="flex justify-between items-center">
                    <div class="relative w-4/12 mt-2">
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="tradePageSettingsForm.trade_layout">
                            <option v-for="tradeLayout in tradeLayouts" :key="tradeLayout.id" :value="tradeLayout.id" :selected="tradeLayout.id === tradePageSettingsForm.trade_layout">{{tradeLayout.value}}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <p class="w-7/12 text-xs">Default Asian layout allows to dock the betslips in one column. European view shows the information in a more condensed manner. All the features are supported in both views.</p>
                </div>
            </div>
            <div class="mb-12">
                <label class="text-sm">Sort Events By</label>
                <div class="flex justify-between items-center">
                    <div class="relative w-4/12 mt-2">
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none" v-model="tradePageSettingsForm.sort_event">
                            <option v-for="sortEvent in sortEvents" :key="sortEvent.id" :value="sortEvent.id" :selected="sortEvent.id === tradePageSettingsForm.sort_event">{{sortEvent.value}}</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <p class="w-7/12 text-xs">Select the way of sorting events on the trade page.</p>
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
            tradePageSettingsForm: {
                suggested: null,
                trade_background: null,
                hide_comp_names_in_fav: null,
                live_position_values: null,
                hide_exchange_only: null,
                trade_layout: null,
                sort_event: null
            },
            tradeLayouts: [],
            sortEvents: []
        }
    },
    head: {
        title() {
            return {
                inner: 'Settings - Trade Page'
            }
        }
    },
    mounted() {
        this.tradeLayouts = this.$store.state.settings.settingsData['trade-layout']
        this.sortEvents = this.$store.state.settings.settingsData['sort-event']
        this.getUserConfig()
    },
    methods: {
        getUserConfig() {
            let token = Cookies.get('mltoken')

            axios.get('v1/user/settings/trade-page', { headers: { 'Authorization': `Bearer ${token}` } })
            .then(response => {
                Object.keys(this.tradePageSettingsForm).forEach(field => {
                    this.tradePageSettingsForm[field] = response.data.data[field]
                })
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        },
        toggleTradeSettings(isActive, key) {
            if (isActive === '1') {
                this.tradePageSettingsForm[key] = '0'
            } else {
                this.tradePageSettingsForm[key] = '1'
            }
        },
        saveChanges() {
            let token = Cookies.get('mltoken')
            let data = {
                suggested: this.tradePageSettingsForm.suggested,
                trade_background: this.tradePageSettingsForm.trade_background,
                hide_comp_names_in_fav: this.tradePageSettingsForm.hide_comp_names_in_fav,
                live_position_values: this.tradePageSettingsForm.live_position_values,
                hide_exchange_only: this.tradePageSettingsForm.hide_exchange_only,
                trade_layout: this.tradePageSettingsForm.trade_layout,
                sort_event: this.tradePageSettingsForm.sort_event
            }

            axios.post('/v1/user/settings/trade-page', data, { headers: { 'Authorization': `Bearer ${token}` } })
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
