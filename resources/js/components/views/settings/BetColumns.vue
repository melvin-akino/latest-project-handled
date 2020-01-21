<template>
    <div class="mt-12 mb-12">
        <p class="mb-12 text-sm">The types of bet that are shown in the table of the trading screen</p>
        <form @submit.prevent="saveChanges">
            <div class="mb-12">
                <label class="text-sm relative flex items-center">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="FT_1x2" v-model="betColumnsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    FT_1x2
                </label>
            </div>
            <div class="mb-12">
                <label class="text-sm relative flex items-center">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="FT_HDP" v-model="betColumnsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    FT Handicap
                </label>
            </div>
            <div class="mb-12">
                <label class="text-sm relative flex items-center">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="FT_OU" v-model="betColumnsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    FT O/U
                </label>
            </div>
            <div class="mb-12">
                <label class="text-sm relative flex items-center">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="FT_OE" v-model="betColumnsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    FT O/E
                </label>
            </div>
            <div class="mb-12">
                <label class="text-sm relative flex items-center">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="1H_1x2" v-model="betColumnsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    1H 1x2
                </label>
            </div>
            <div class="mb-12">
                <label class="text-sm relative flex items-center">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="1H_HDP" v-model="betColumnsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    1H Handicap
                </label>
            </div>
            <div class="mb-12">
                <label class="text-sm relative flex items-center">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="1H_OU" v-model="betColumnsCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    1H O/U
                </label>
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
            betColumnsCheckedSettings:[]
        }
    },
    head:{
        title() {
            return {
                inner: 'Settings - Bet Columns'
            }
        }
    },
    methods:{
        saveChanges() {

        }
    }
}
</script>
