<template>
    <div class="mt-12 mb-12">
        <form @submit.prevent="saveChanges">
            <div class="mb-12" v-for="bookies in bookies" :key="bookies.id">
                <label class="text-sm relative flex items-center">
                    <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" :value="bookies.id" @click="bookies.active = !bookies.active">
                    <span class="absolute left-0 shadow shadow-lg w-6 h-6 rounded-full" :class="[bookies.active ? 'on-switch bg-orange-500' : 'bg-white left-0']"></span>
                    <div><span class="font-bold">{{bookies.alias}}</span> ({{bookies.name}})</div>
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
            bookies:[
                {
                    id: 1,
                    name: "Singbet",
                    alias: "HG",
                    priority: 1,
                    active: true
                },
                {
                    id: 2,
                    name: "Pinnacle",
                    alias: "PIN",
                    priority: 2,
                    active: true
                },
                {
                    id: 3,
                    name: "ISN88",
                    alias: "ISN",
                    priority: 3,
                    active: true
                }
            ]
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

<style>
    .on-switch{
        left:24px;
    }
</style>
