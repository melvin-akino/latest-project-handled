<template>
    <div class="h-full m-12">
        <p class="text-3xl mb-12">Trade Page</p>
        <form @submit.prevent="saveChanges">
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="1" v-model="tradePageCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    Suggested competitions/events
                </label>
            </div>
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="2" v-model="tradePageCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    Color trade market background
                </label>
            </div>
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="3" v-model="tradePageCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    Hide competition names in favorites
                </label>
            </div>
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="4" v-model="tradePageCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    Live position values
                </label>
            </div>
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="5" v-model="tradePageCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    Hide exchange only lines
                </label>
            </div>
            <div class="mb-12">
                <label>Trade Layout</label>
                <div class="relative w-1/2 mt-2">
                    <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none">
                        <option value="1">Asian</option>
                        <option value="2">European</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
            </div>
            <div class="mb-12">
                <label>Sort Events By</label>
                <div class="relative w-1/2 mt-2">
                    <select class="shadow appearance-none border w-full rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none">
                        <option value="1">Event Start Time</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-md uppercase px-12 py-4">Save Changes</button>
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
