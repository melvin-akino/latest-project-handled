<template>
    <div class="flex flex-wrap justify-around items-center h-8 pr-10 bg-gray-800 text-white text-xs">
        <p>Description</p>
        <p>Sport</p>
        <p>Score & Schedule</p>
        <p v-for="column in columnsToDisplay" :key="column.sport_odd_type_id">{{column.type}}</p>
        <button class="bg-orange-500 px-4 py-1 hover:bg-orange-600 fixed right-0 mr-1" @click="openColumnModal">Add <i class="fas fa-plus"></i></button>
        <div class="flex justify-center items-center fixed z-20 w-full h-full top-0 left-0 modalWrapper" v-if="showToggleColumnsModal">
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
        }
    },
    computed: {
        ...mapState('trade', ['selectedSport'])
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
            let token = Cookies.get('access_token')

            try {
                let response = await axios.get('v1/sports/odds', { headers: { 'Authorization': `Bearer ${token}` }})
                let settings = await this.$store.dispatch('settings/getUserSettingsConfig', 'bet-columns')
                let betColumns = response.data.data
                let { disabled_columns } = settings
                betColumns.filter(column => column.sport_id === this.selectedSport).map(column => this.filteredColumnsBySport = column.odds)
                this.columnsToDisplay = this.filteredColumnsBySport.filter(column => !disabled_columns.includes(column.sport_odd_type_id))
                this.checkedColumns = this.columnsToDisplay.map(column => column.sport_odd_type_id)
            } catch(err) {
                console.log(err)
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status)
            }
        },
        saveColumns() {
            let token = Cookies.get('access_token')
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
                console.log(err)
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.data.status)
            })
        }
    }
}
</script>

<style>
    .modalWrapper {
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal {
        height: 300px;
    }
</style>
