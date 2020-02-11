<template>
    <div class="flex justify-between">
        <p class="text-white">{{walletTitle}}</p>
        <p class="text-white">{{walletData}}</p>
    </div>
</template>

<script>
export default {
    props: ['walletTitle', 'walletData']
}
</script>

<style>

</style>
