<template>
    <div class="mt-12 mb-12">
        <p class="mb-12 text-sm">The types of bet that are shown in the table of the trading screen</p>
        <form @submit.prevent="saveChanges">
            <div class="flex justify-evenly items-center">
                <div class="soccer w-1/4">
                    <p class="text-xl mb-4">{{soccerSportsOddTypes[0].sport}}</p>
                    <div class="mb-12" v-for="soccerSportsOddType in soccerSportsOddTypes" :key="soccerSportsOddType.sport_odd_type_id">
                        <label class="text-sm relative flex items-center">
                            <input type="checkbox" class="appearance-none shadow border border-gray-400 bg-gray-400 rounded-full h-3 w-12 mr-4" @change="soccerSportsOddType.active = !soccerSportsOddType.active">
                            <span class="absolute shadow shadow-lg w-6 h-6 rounded-full" :class="[soccerSportsOddType.active ? 'on-switch bg-orange-500' : 'left-0 bg-white']"></span>
                            {{soccerSportsOddType.type}}
                        </label>
                    </div>
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
          // mock data these should come from an API request
            sportsOddsType:[
                {
                    "sport_odd_type_id": 1,
                    "odd_type_id": 1,
                    "sport_id": 1,
                    "sport": "Soccer",
                    "type": "FT 1X2",
                    "active": true
                },
                {
                    "sport_odd_type_id": 2,
                    "sport_id": 1,
                    "sport": "Soccer",
                    "type": "FT Handicap",
                    "odd_type_id": 2,
                    "active": true
                },
                {
                    "sport_odd_type_id": 3,
                    "sport_id": 1,
                    "sport": "Soccer",
                    "type": "FT O/U",
                    "odd_type_id": 3,
                    "active": true
                },
                {
                    "sport_odd_type_id": 4,
                    "sport_id": 1,
                    "sport": "Soccer",
                    "type": "FT O/E",
                    "odd_type_id": 4,
                    "active": true
                },
                {
                    "sport_odd_type_id": 5,
                    "sport_id": 1,
                    "sport": "Soccer",
                    "type": "1H 1X2",
                    "odd_type_id": 5,
                    "active": true
                },
                {
                    "sport_odd_type_id": 6,
                    "sport_id": 1,
                    "sport": "Soccer",
                    "type": "1H Handicap",
                    "odd_type_id": 6,
                    "active": true
                },
                {
                    "sport_odd_type_id": 7,
                    "sport_id": 1,
                    "sport": "Soccer",
                    "type": "1H O/U",
                    "odd_type_id": 7,
                    "active": true
                }
            ]
        }
    },
    head:{
        title() {
            return {
                inner: 'Settings - Bet Columns'
            }
        }
    },
    computed: {
        soccerSportsOddTypes() {
            return this.sportsOddsType.filter(sportsOddsType => sportsOddsType.sport_id === 1)
        }
    },
    mounted() {

    },
    methods:{
       saveChanges() {
           /* save settings here */
       }
    }
}
</script>

<style>
    .on-switch {
        left: 24px;
    }
</style>
