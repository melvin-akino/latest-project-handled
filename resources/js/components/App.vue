<template>
    <appwrapper>
        <router-view></router-view>
        <snack-bar></snack-bar>
    </appwrapper>
</template>

<script>
import appwrapper from './layouts/appwrapper'
import SnackBar from './component/Snackbar'

export default {
    name: 'App',
    props: ['onloadData'],
    components: {
        appwrapper,
        SnackBar
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
