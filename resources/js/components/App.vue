<template>
    <appwrapper>
        <router-view></router-view>
    </appwrapper>
</template>

<script>
import appwrapper from './layouts/appwrapper'

export default {
    name: 'App',
    props: ['onloadData'],
    components: {
        appwrapper
    },
    mounted() {
        this.fetchSettingsOnLoadData()
        this.fetchBettingOnLoadData()
    },
    methods: {
        fetchSettingsOnLoadData() {
            let settingsData = Object.assign({}, this.onloadData)
            delete settingsData['leauge-data']
            this.$store.commit('settings/FETCH_ONLOAD_SETTINGS_DATA', settingsData)
        },
        fetchBettingOnLoadData() {
            this.$store.commit('trade/FETCH_LEAGUES_DATA', this.onloadData['leauge-data'])
        }
    }
}
</script>
