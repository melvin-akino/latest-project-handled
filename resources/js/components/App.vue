<template>
    <appwrapper>
        <router-view></router-view>
    </appwrapper>
</template>

<script>
import appwrapper from './layouts/appwrapper'

export default {
    name: 'App',
    props: ['settingsData'],
    components: {
        appwrapper
    },
    mounted() {
        this.$store.commit('settings/getSettingsData', this.settingsData)
    }
}
</script>
