import Vue from 'vue'
import Cookies from 'js-cookie'
import { twoDecimalPlacesFormat, moneyFormat } from '../helpers/numberFormat'
const token = Cookies.get('mltoken')

const state = {
    myorders: [],
    groupedBy: 'date',
    groupDesc: true,
}

const mutations = {
    SET_MY_ORDERS: (state, orders) => {
        state.myorders = orders
    },
    SET_GROUPED_BY: (state, group_by) => {
        state.groupedBy = group_by
    },
    SET_GROUP_SORT: (state, group_sort) => {
        state.groupDesc = group_sort
    },
    SET_PROVIDER_BETS_FOR_ORDER: (state, data) => {
        state.myorders.map(order => {
            if(order.order_id == data.order_id) {
                Vue.set(order, 'provider_bets', data.provider_bets)
            }
        })
    }
}

const actions = {
    getMyOrders({commit, dispatch, rootState}, filters) {
        axios.get(`v1/orders/myOrdersV2`, { params: filters, headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                let orders = []
                let formattedColumns = ['stake', 'towin', 'pl', 'valid_stake']
                let _filters = response.data.filters
                let _sort = _filters.group_by == 'date' ? true : false

                commit('SET_GROUPED_BY', _filters.group_by)
                commit('SET_GROUP_SORT', _sort)

                if (response.data.data != null) {
                    response.data.data.map(order => {
                        let orderObj = {}

                        Object.keys(order).map(key => {
                            if(formattedColumns.includes(key)) {
                                Vue.set(orderObj, key, moneyFormat(Number(order[key])))
                            } else if(key=='odds') {
                                Vue.set(orderObj, key, twoDecimalPlacesFormat(Number(order[key])))
                            } else {
                                Vue.set(orderObj, key, order[key])
                            }
                        })

                        if(order.status == 'SUCCESS') {
                            Vue.set(orderObj, 'status', 'PLACED')
                        }

                        orders.push(orderObj)
                    })
                }

                commit('SET_MY_ORDERS', orders)
            })
            .catch(err => {
                dispatch('auth/checkIfTokenIsValid', err.response.status, { root: true })
                commit('SET_MY_ORDERS', [])
            })
    },
    getProviderBets({commit, dispatch}, order_id) {
        axios.get(`v1/orders/provider-bets/${order_id}`, { headers: { 'Authorization': `Bearer ${token}` }})
            .then(response => {
                let data = []
                let formattedColumns = ['stake', 'towin', 'pl', 'valid_stake']
                if (response.data.data != null) {
                    response.data.data.map(bet => {
                        let obj = {}
                        Object.keys(bet).map(key => {
                            if(formattedColumns.includes(key)) {
                                Vue.set(obj, key, moneyFormat(Number(bet[key])))
                            } else if(key=='odds') {
                                Vue.set(obj, key, twoDecimalPlacesFormat(Number(bet[key])))
                            }  else {
                                Vue.set(obj, key, bet[key])
                            }
                        })
                        if(bet.status == 'SUCCESS') {
                            Vue.set(obj, 'status', 'PLACED')
                        }
                        data.push(obj)
                    })
                }
                commit('SET_PROVIDER_BETS_FOR_ORDER', { order_id, provider_bets: data })
            })
            .catch(err => {
                dispatch('auth/checkIfTokenIsValid', err.response.status, { root: true })
            })
    }
}

export default {
    state, mutations, actions, namespaced: true
}
