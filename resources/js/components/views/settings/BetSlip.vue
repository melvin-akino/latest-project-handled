<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" value="1" v-model="betSlipCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-white"></span>
                </label>
                <span class="w-4/12 text-sm">Use equivalent bets</span>
                <p class="text-xs w-7/12 text-left">Try to place equivalent bets with different bet types to what you have requested. Even though these bet types may be different in meaning, the payout grids are identical. For instance, if you asked for under 0.5 goals, we could place a correct score 0-0 bet to try and get more volume</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" value="2" v-model="betSlipCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-white"></span>
                </label>
                <span class="w-4/12 text-sm">Allow putting offers on exchanges</span>
                <p class="text-xs w-7/12 text-left">If an exchange account is selected and no liquidity is available, we will attempt to put up an offer on that exchange</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" value="3" v-model="betSlipCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-white"></span>
                </label>
                <span class="w-4/12 text-sm">Show advanced placement options</span>
                <p class="text-xs w-7/12 text-left">Show advanced order expiry options, like keeping an order open in running or taking best closing price</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" value="3" v-model="betSlipCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-white"></span>
                </label>
                <span class="w-4/12 text-sm">Add bets to favorite events</span>
                <p class="text-xs w-7/12 text-left">Automatically add events that you bet on to your favorites so you can more easily keep track of them</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" value="3" v-model="betSlipCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-white"></span>
                </label>
                <span class="w-4/12 text-sm">Show advanced betslip information</span>
                <p class="text-xs w-7/12 text-left">Select to show expected returns, average price, and minimum & maximum order</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" value="3" v-model="betSlipCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-white"></span>
                </label>
                <span class="w-4/12 text-sm">Tint bookies</span>
                <p class="text-xs w-7/12 text-left">Lightly tint bookie account backgrounds</p>
            </div>
            <div class="mb-12">
                <label class="text-sm">Adaptive Selection</label>
                <div class="flex justify-between items-center">
                    <div class="relative w-4/12 mt-4">
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none">
                            <option value="1">Only bookies at placement</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <p class="text-xs w-7/12">Should orders reselect accounts and bookies during placement ?</p>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-12 py-4">Save Changes</button>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    data() {
        return {
            betSlipCheckedSettings:[]
        }
    },
    head:{
        title() {
            return {
                inner: 'Settings - Bet Slip'
            }
        }
    },
    methods:{
        saveChanges() {

        }
    }
}
</script>
