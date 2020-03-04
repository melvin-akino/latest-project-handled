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
    },
    methods: {
        fetchSettingsOnLoadData() {
            let settingsData = Object.assign({}, this.onloadData)
            this.$store.commit('settings/FETCH_ONLOAD_SETTINGS_DATA', settingsData)
        }
    }
}
</script>
