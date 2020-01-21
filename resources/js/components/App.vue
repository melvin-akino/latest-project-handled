<template>
    <appwrapper>
      <router-view></router-view>
    </appwrapper>
</template>

<script>
import appwrapper from './layouts/appwrapper'
export default {
    name:'App',
    components:{
        appwrapper
    }
}
</script>