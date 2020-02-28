<template>
    <div class="container mx-auto my-10">
        <h3 class="text-xl">My Orders</h3>
        <div class="flex justify-start mt-4">
            <form @submit.prevent="filterOrders()">
                <div class="flex items-center my-4">
                    <label class="block text-gray-700 text-sm mb-1 mr-2 w-full">Transaction Date: </label>    
                    <input class="shadow appearance-none border rounded w-full mr-3 py-1 px-3 text-gray-700 leading-tight focus:outline-none" id="trasanctiondatefrom" type="date" placeholder="From" v-model="filterOrderForm.transactionDateFrom">
                    <input class="shadow appearance-none border rounded w-full mr-3 py-1 px-3 text-gray-700 leading-tight focus:outline-none" id="trasanctiondateto" type="date" placeholder="To" v-model="filterOrderForm.transactionDateTo">
                </div>
                <div class="flex items-center my-4">
                    <label class="block text-gray-700 text-sm mb-1 mr-2 w-full">Order Date: </label>
                    <input class="shadow appearance-none border rounded w-full mr-3 py-1 px-3 text-gray-700 leading-tight focus:outline-none" id="orderdatefrom" type="date" placeholder="From" v-model="filterOrderForm.orderDateFrom">
                    <input class="shadow appearance-none border rounded w-full mr-3 py-1 px-3 text-gray-700 leading-tight focus:outline-none" id="orderdateto" type="date" placeholder="To" v-model="filterOrderForm.orderDateTo">
                </div>
                <div class="flex items-center my-4">
                    <label class="block text-gray-700 text-sm mb-1 mr-2 w-full" for="status">Status: </label>
                    <div class="relative statusInputHolder">
                        <select class="shadow appearance-none border rounded w-full py-1 px-3 text-gray-700 leading-tight focus:outline-none statusInput" id="status" v-model="filterOrderForm.status">
                            <option :value="null" disabled>Select Status</option>
                            <option value="1">All</option>
                            <option value="2">Settled</option>
                            <option value="3">Pending</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                    <div class="w-full"></div>
                </div>
                <div class="flex justify-end items-center my-4">
                    <button type="submit" class="bg-orange-500 text-white rounded-lg sm:text-sm text-xs px-3 py-2 mr-2 hover:bg-orange-600 focus:outline-none">Search</button>
                    <button type="button" class="bg-red-700 text-white rounded-lg sm:text-sm text-xs px-3 py-2 hover:bg-red-800 focus:outline-none" @click="resetForm">Reset</button>
                </div>
            </form>
        </div>
        <div class="flex justify-between w-full mt-4">
            <div class="flex items-center">
                <span class="text-xs mr-2">Show</span>
                 <div class="relative w-12 mr-2">
                    <select class="shadow appearance-none border rounded w-12 py-1 px-1 text-xs text-gray-700 leading-tight focus:outline-none" v-model="dataPerPage">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
                <span class="text-xs">Entries</span>
            </div>
            <div class="flex items-center">
                <button type="button" class="text-gray-700 mr-2" title="Print orders."><i class="fas fa-print"></i></button>
                <button type="button" class="text-gray-700 mr-2" title="Export orders in excel format."><i class="fas fa-file-export"></i></button>
            </div>
        </div>
        <div class="ordersTable mt-4">
            <table class="table-fixed w-full shadow-lg">
                <thead class="bg-orange-500 text-white">
                    <tr>
                        <th class="w-2/12 p-2 text-sm">Settlement Date & Time</th>
                        <th class="w-2/12 p-2 text-sm">Transaction Date & Time</th>
                        <th class="w-3/12 p-2 text-sm">Selection</th>
                        <th class="w-1/12 p-2 text-sm">Source</th>
                        <th class="w-1/12 p-2 text-sm">Status</th>
                        <th class="w-1/12 p-2 text-sm">Odds</th>
                        <th class="w-1/12 p-2 text-sm">Stake</th>
                        <th class="w-1/12 p-2 text-sm">Profit/Loss</th>
                        <th class="w-1/12 p-2 text-sm">To Win</th>
                        <th class="w-1/12 p-2 text-sm"></th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <!-- Hardcoded data at first, will use response from API when server code is done  -->
                    <tr>
                        <td class="w-2/12 p-1 text-sm text-center">2/28/2020, 7:46:42 AM</td>
                        <td class="w-2/12 p-1 text-sm text-center">2/28/2020, 7:46:42 AM</td>
                        <td class="w-3/12 p-1 text-sm text-center">
                            <p>Tottenham Hotspur FC vs Southhampton FC</p>
                            <p>FT-HDP -0.75 (1-0, Asian HDP)</p>
                            <p>Tottenham Hotspur FC @ 0.99</p>
                        </td>
                        <td class="w-1/12 p-1 text-sm text-center">PIN88</td>
                        <td class="w-1/12 p-1 text-sm text-center">Win</td>
                        <td class="w-1/12 p-1 text-sm text-center">0.699</td>
                        <td class="w-1/12 p-1 text-sm text-center">$ 100.00</td>
                        <td class="w-1/12 p-1 text-sm text-center">$ 69.00</td>
                        <td class="w-1/12 p-1 text-sm text-center"></td>
                        <td class="w-1/12 p-1 text-sm text-center">
                            <a href="#" class="py-1 pr-1"><i class="fas fa-chart-area"></i></a>
                            <a href="#" class="py-1"><i class="fas fa-bars"></i></a>
                        </td>
                    </tr>
                    <tr>
                        <td class="w-2/12 p-1 text-sm text-center">2/28/2020, 7:46:42 AM</td>
                        <td class="w-2/12 p-1 text-sm text-center">2/28/2020, 7:46:42 AM</td>
                        <td class="w-3/12 p-1 text-sm text-center">
                            <p>Tottenham Hotspur FC vs Southhampton FC</p>
                            <p>FT-HDP -0.75 (1-0, Asian HDP)</p>
                            <p>Tottenham Hotspur FC @ 0.99</p>
                        </td>
                        <td class="w-1/12 p-1 text-sm text-center">PIN88</td>
                        <td class="w-1/12 p-1 text-sm text-center">Win</td>
                        <td class="w-1/12 p-1 text-sm text-center">0.699</td>
                        <td class="w-1/12 p-1 text-sm text-center">$ 100.00</td>
                        <td class="w-1/12 p-1 text-sm text-center"></td>
                        <td class="w-1/12 p-1 text-sm text-center">$ 69.00</td>
                        <td class="w-1/12 p-1 text-sm text-center">
                            <a href="#" class="py-1 pr-1"><i class="fas fa-chart-area"></i></a>
                            <a href="#" class="py-1"><i class="fas fa-bars"></i></a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="totalPL mt-2 mb-10 relative">
            <div class="absolute text-sm totalPLdata">
                <span class="totalPLlabel">Total P/L</span>
                <span>$ 69.90</span>
            </div>
        </div>
        
        <div class="text-center">
            <ul class="pagination flex justify-center items-center">
                <div class="flex bg-white border border-gray-700 rounded-full">
                    <a href="#">
                        <li class="border-r border-gray-700 py-1 px-4 prev"><i class="fas fa-chevron-left"></i></li>
                    </a>
                    <a href="#">
                        <li class="border-r border-gray-700 py-1 px-4">1</li>
                    </a>
                    <a href="#">
                        <li class="border-r border-gray-700 py-1 px-4">2</li>
                    </a>
                    <a href="#">
                        <li class="border-r border-gray-700 py-1 px-4">3</li>
                    </a>
                    <a href="#">
                        <li class="border-r border-gray-700 py-1 px-4">4</li>
                    </a>
                    <a href="#"> 
                        <li class="border-r border-gray-700 py-1 px-4 next"><i class="fas fa-chevron-right"></i></li>
                    </a>
                </div>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            filterOrderForm: {
                transactionDateFrom: null,
                transactionDateTo: null,
                orderDateFrom: null,
                orderDateTo: null,
                status: null,
            },
            dataPerPage: 10
        }
    },
    head: {
        title() {
            return {
                inner: 'My Orders'
            }
        }
    },
    mounted() {
        
    },
    methods: {
        filterOrders() {

        },
        resetForm() {
            Object.keys(this.filterOrderForm).map(field => {
                this.filterOrderForm[field] = null
            })
        }
    }
}
</script>

<style>
    .statusInputHolder {
        right: 52px;
    }

    .statusInput {
        width: 182px;
    }

    .totalPLlabel {
        margin-right: 43px;
    }

    .totalPLdata {
        right: 125px;
    }

    .next {
        border-right:0;
    }
</style>
