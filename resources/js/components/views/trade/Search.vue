<template>
    <div class="search w-1/2 xl:w-1/3" v-if="showSearch">
        <form class="relative px-4">
            <div class="flex items-center">
                <input type="text" class="appearance-none bg-transparent border-b border-gray-800 w-full text-sm text-gray-700 mr-1 py-1 leading-tight focus:outline-none" placeholder="Search Leagues or Teams" aria-label="Search Leagues or Teams" v-model="searchKeyword" @keyup="searchLeaguesOrTeams">
                <span class="text-gray-700" type="submit"><i class="fas fa-search"></i></span>
            </div>
            <div class="flex flex-col absolute w-full shadow shadow-inner searchSuggestions py-3 bg-white" v-if="isSearching">
                <div class="suggestionWrapper" v-if="searchSuggestions.length != 0">
                    <div class="flex items-center suggestion p-2 cursor-pointer hover:bg-gray-300" v-for="(suggestion, index) in searchSuggestions" :key="index" @click="addToWatchlist(suggestion.type, suggestion.data)">
                        <span v-if="suggestion.type=='league'" class="text-white p-2 mr-2 bg-orange-600">L</span>
                        <span v-if="suggestion.type=='event'" class="text-white p-2 mr-2 bg-green-500">E</span>
                        <span class="text-gray-700">{{suggestion.label}}</span>
                    </div>
                    <div class="text-gray-700 text-center suggestion p-2 hover:underline cursor-pointer" v-if="isPaginated" @click="incrementPage">Load More...</div>
                </div>
                <div class="flex justify-center items-center p-2" v-else>
                    <span class="text-gray-700 suggestion">No leagues/events found.</span>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import Cookies from 'js-cookie'

export default {
    data() {
        return {
            searchKeyword: '',
            searchSuggestions: [],
            isSearching: false,
            isPaginated: false,
            page: 1
        }
    },
    computed: {
        ...mapState('trade', ['showSearch', 'eventsList'])
    },
    watch: {
        searchKeyword(newValue, oldValue) {
            if(newValue != oldValue) {
                this.page = 1
            }
        }
    },
    methods: {
        searchLeaguesOrTeams() {
            if(this.searchKeyword == '') {
                this.searchSuggestions = []
                this.isSearching = false
            } else {
                this.isSearching = true
                let token = Cookies.get('mltoken')
                axios.post('v1/trade/search', { keyword: this.searchKeyword, page: this.page }, { headers: { 'Authorization': `Bearer ${token}` } })
                .then(response => {
                    this.isPaginated = response.data.paginated
                    if(this.page == 1) {
                        this.searchSuggestions = response.data.data
                    } else if(this.page > 1) {
                        response.data.data.map(suggestion => {
                            this.searchSuggestions.push(suggestion)
                        })
                    }
                })
                .catch(err => {
                    this.$store.dispatch('auth/checkIfTokenIsValid', err.response.status)
                })
            }
        },
        incrementPage() {
            this.page++
            this.searchLeaguesOrTeams()
        },
        addToWatchlist(type, data) {
            this.isSearching = false
            this.searchKeyword = ''
            if(type=='league') {
                this.$store.dispatch('trade/addToWatchlist', { type: type, data: data })
            } else if(type=='event') {
                let payload
                let event = this.eventsList.filter(event => event.uid == data)
                if(event.length != 0) {
                    payload = event[0]
                } else {
                    payload = null
                }
                this.$store.dispatch('trade/addToWatchlist', { type: type, data: data, payload: payload })
            }
        }
    }
}
</script>

<style lang="scss">
    .search {
      input {
        border-style: solid !important;
      }
    }

    .searchSuggestions {
        max-height: 245px;
        overflow-y: auto;
    }

    .suggestion {
        font-size: 14px;
    }
</style>
