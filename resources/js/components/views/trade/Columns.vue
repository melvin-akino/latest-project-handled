<template>
    <div class="oddTypes">
        <div class="betColumns flex bg-gray-800 text-white text-xs pl-6 pr-8 z-10" :class="[tradeLayout==1 ? 'items-center py-2' : 'py-1']" v-adjust-odd-types-column-width>
            <div class="w-2/12" v-if="tradeLayout==1"></div>
            <div class="w-1/12 text-center" v-if="tradeLayout==1">Sport</div>
            <div class="w-1/12 text-center" v-if="tradeLayout==1">Score & <br>Schedule</div>
            <div class="w-1/12 py-1 flex justify-center">
                <button class="w-8 text-white text-center bg-orange-500 px-1 py-2 hover:bg-orange-600" aria-label="Toggle Odd Types" @click="openColumnModal"><i class="fas fa-plus"></i></button>
            </div>
            <div class="flex w-1/12" :class="[tradeLayout==2 ? 'flex-col mr-10 px-2' : 'justify-center pl-2']" v-for="column in columnsToDisplay" :key="column.sport_odd_type_id">
                <span class="text-center">{{column.name}}</span>
                <div class="flex justify-between" v-if="tradeLayout==2">
                    <span>{{column.home_label}}</span>
                    <span v-if="column.odd_type_id===1 || column.odd_type_id===5">X</span>
                    <span>{{column.away_label}}</span>
                </div>
            </div>
        </div>
        <div class="flex justify-center items-center fixed w-full h-full top-0 left-0 modalWrapper z-40" @click="showToggleColumnsModal = false" v-if="showToggleColumnsModal">
            <div class="bg-white w-64 p-8 modal" @click.stop>
                <form @submit.prevent="saveColumns()">
                    <div class="mb-2" v-for="filteredColumn in filteredColumnsBySport" :key="filteredColumn.sport_odd_type_id">
                        <label class="block text-gray-700 text-sm mb-2 font-bold uppercase">
                            <input class="mr-2 leading-tight" type="checkbox" :value="filteredColumn.sport_odd_type_id" v-model="checkedColumns">
                            <span class="text-sm uppercase">{{filteredColumn.name}}</span>
                        </label>
                    </div>
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm uppercase px-4 py-2">Save & Close</button>
                </form>
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
        }
    },
    computed: {
        ...mapState('trade', ['selectedSport', 'tradeLayout', 'filteredColumnsBySport', 'columnsToDisplay', 'oddsTypeBySport']),
        ...mapState('settings', ['disabledBetColumns']),
        checkedColumns: {
            get() {
                return this.$store.state.trade.checkedColumns
            },
            set(value) {
                this.$store.commit('trade/SET_CHECKED_COLUMNS', value)
            }
        }
    },
    methods: {
        openColumnModal() {
            this.showToggleColumnsModal = true
        },
        saveColumns() {
            this.showToggleColumnsModal = false
            let token = Cookies.get('mltoken')
            let data = this.filteredColumnsBySport.map(column => {
                return {
                    sport_odd_type_id: column.sport_odd_type_id,
                    active: this.checkedColumns.includes(column.sport_odd_type_id) ? true : false
                }
            })

            axios.post(`/v1/user/settings/bet-columns/${this.selectedSport}`, data, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(() => {
                this.$store.dispatch('trade/getBetColumns', this.selectedSport)
                Swal.fire({
                    icon: 'success',
                    text: 'Saved Changes!'
                })
            })
            .catch(err => {
                this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
            })
        }
    },
    directives: {
        adjustOddTypesColumnWidth: {
            componentUpdated(el, binding, vnode) {
                let { selectedSport, columnsToDisplay } = vnode.context
                if(selectedSport == 3) {
                    if(columnsToDisplay.length > 8) {
                        el.style.width = '115rem'
                    } else {
                        el.style.width = '100%'
                    }
                } else {
                    el.style.width = '100%'
                }
            }
        }
    }
}
</script>

<style>
    .betColumns {
        height: 52px;
    }

    .modalWrapper {
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal {
        overflow: auto;
    }

    .modalBtn {
        right: 10px;
        bottom: 40px;
    }

    .column {
        width: 90px;
    }

    .text-white {
      color: #ffffff !important;
    }
</style>
