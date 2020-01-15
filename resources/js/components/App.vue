<template>
    <div>
        <appwrapper>
            <router-view></router-view>
        </appwrapper>
    </div>
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
