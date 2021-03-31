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
                            } else if(key=='score') {
                                if(rootState.trade.failedBetStatus.includes(order.status) || order.status == 'SUCCESS') {
                                    Vue.set(orderObj, key, "")
                                } else{
                                    Vue.set(orderObj, key, `"${order[key]}"`)
                                }
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
}

export default {
    state, mutations, actions, namespaced: true
}
