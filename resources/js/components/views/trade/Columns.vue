<template>
    <div class="column">
        <div class="betColumns flex flex-wrap justify-around items-center bg-gray-800 py-2 pl-4 pr-6 text-white text-xs fixed z-10 w-5/6">
            <p class="w-2/12 px-4"></p>
            <p class="w-1/12 text-center px-2">Sport</p>
            <p class="w-1/12 text-center px-2">Score & <br>Schedule</p>
            <div class="w-1/12 flex justify-center">
                <button class="w-8 text-white text-center bg-orange-500 px-2 py-1 hover:bg-orange-600" @click="openColumnModal"><i class="fas fa-plus"></i></button>
            </div>
            <p class="w-1/12 text-center px-2" v-for="column in columnsToDisplay" :key="column.sport_odd_type_id">{{column.type}}</p>
        </div>
        <div class="flex justify-center items-center fixed w-full h-full top-0 left-0 modalWrapper z-40" v-if="showToggleColumnsModal">
            <div class="bg-white w-64 p-8 modal">
                <div class="mb-2" v-for="filteredColumn in filteredColumnsBySport" :key="filteredColumn.sport_odd_type_id">
                    <label class="block text-gray-700 text-sm mb-2 font-bold uppercase">
                        <input class="mr-2 leading-tight" type="checkbox" :value="filteredColumn.sport_odd_type_id" v-model="checkedColumns" @change="saveColumns">
                        <span class="text-sm uppercase">{{filteredColumn.type}}</span>
                    </label>
                </div>
                <button class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-4 py-2" @click="closeColumnModal">Save & Close</button>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'
import Swal from 'sweetalert2'

export default {
    data() {
        return {
            showToggleColumnsModal: false,
            checkedColumns: [],
            filteredColumnsBySport: [],
            columnsToDisplay: [],
            test: []
        }
    },
    computed: {
        ...mapState('trade', ['selectedSport']),
        ...mapState('settings', ['disabledBetColumns'])
    },
    mounted() {
        this.getBetColumns()
    },
    methods: {
        openColumnModal() {
            this.showToggleColumnsModal = true
        },
        closeColumnModal() {
            this.showToggleColumnsModal = false
            this.saveColumns()
            Swal.fire({
                icon: 'success',
                text: 'Saved Changes!'
            })
        },
        async getBetColumns() {
            let token = Cookies.get('mltoken')

            try {
                let response = await axios.get('v1/sports/odds', { headers: { 'Authorization': `Bearer ${token}` }})
                let settings = await this.$store.dispatch('settings/getUserSettingsConfig', 'bet-columns')
                let betColumns = response.data.data
                let { disabled_columns } = settings
                this.$store.commit('settings/FETCH_DISABLED_COLUMNS', disabled_columns)
                betColumns.filter(column => column.sport_id === this.selectedSport).map(column => this.filteredColumnsBySport = column.odds)
                this.columnsToDisplay = this.filteredColumnsBySport.filter(column => !this.disabledBetColumns.includes(column.sport_odd_type_id))
                this.checkedColumns = this.columnsToDisplay.map(column => column.sport_odd_type_id)
            } catch(err) {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            }
        },
        saveColumns(sportId) {
            let token = Cookies.get('mltoken')
            let data = this.filteredColumnsBySport.map(column => {
                return {
                    sport_odd_type_id: column.sport_odd_type_id,
                    active: this.checkedColumns.includes(column.sport_odd_type_id) ? true : false
                }
            })

            axios.post(`/v1/user/settings/bet-columns/${this.selectedSport}`, data, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(() => {
                this.getBetColumns()
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status_code)
            })
        }
    }
}
</script>

<style>
    .betColumns {
        right: 10px;
    }
    
    .modalWrapper {
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal {
        height: 300px;
    }

    .modalBtn {
        right: 10px;
        bottom: 40px;
    }
</style>
