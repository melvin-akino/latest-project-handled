<template>
    <div class="h-full m-12">
        <p class="text-3xl mb-12">Bookies</p>
        <form @submit.prevent="saveChanges">
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="HG" v-model="bookiesCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    HG
                </label>
            </div>
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="ISN" v-model="bookiesCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    ISN
                </label>
            </div>
            <div class="mb-12">
                <label class="relative flex items-center text-lg">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 rounded-full h-6 w-12 mr-4" value="PIN" v-model="bookiesCheckedSettings">
                    <span class="absolute left-0 shadow shadow-inner w-6 h-6 rounded-full bg-orange-500"></span>
                    PIN
                </label>
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
            bookiesCheckedSettings:[]
        }
    },
    head:{
        title() {
            return {
                inner: 'Settings - Bookies'
            }
        }
    },
    methods:{
        saveChanges() {

        }
    }
}
</script>
