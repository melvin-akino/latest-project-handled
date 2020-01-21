<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="1" v-model="tradePageCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                </label>
                <span class="w-4/12 text-sm">Suggested competitions/events</span>
                <p class="text-xs w-7/12 text-left">Mark competitions and events that provide good betting opportunities with a <i class="text-orange-500 fas fa-fire-alt"></i></p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="2" v-model="tradePageCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                </label>
                <span class="w-4/12 text-sm">Color trade market background</span>
                <p class="text-xs w-7/12 text-left">Lightly tint the background of event markets</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="3" v-model="tradePageCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                </label>
                <span class="w-4/12 text-sm">Hide competition names in favorites</span>
                <p class="text-xs w-7/12 text-left">You can disable the competition names in the favorites market to save vertical space. If you do this they will automatically be sorted by time regardless of your normal trade sort.</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="4" v-model="tradePageCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                </label>
                <span class="w-4/12 text-sm">Live position values</span>
                <p class="text-xs w-7/12 text-left">Select to show Position button with current expected profit / loss based on live score. This will poll your current position and update live based on bets placed and score changes. Disabling this will restore the original position button.</p>
            </div>
            <div class="flex items-center mb-12">
                <label class="relative flex items-center w-1/12">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="5" v-model="tradePageCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                </label>
                <span class="w-4/12 text-sm">Hide exchange only lines</span>
                <p class="text-xs w-7/12 text-left">Hide markets and handicap lines that only use exchange offers. This will show only bookie backed prices.</p>
            </div>
            <div class="mb-12">
                <label class="text-sm">Trade Layout</label>
                <div class="flex justify-between items-center">
                    <div class="relative w-4/12 mt-2">
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none">
                            <option value="1">Asian</option>
                            <option value="2">European</option>
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
                        <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 text-sm leading-tight focus:outline-none">
                            <option value="1">Event Start Time</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <p class="w-7/12 text-xs">Select the way of sorting events on the trade page.</p>
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
            tradePageCheckedSettings:[]
        }
    },
    head:{
        title() {
            return {
                inner: 'Settings - Trade Page'
            }
        }
    },
    methods:{
        saveChanges() {

        }
    }
}
</script>
