<template>
    <div class="toolTip ml-1" :class="color">
        <i :class="icon"></i>
        <span class="toolTipText p-2 text-white bg-gray-800">{{text}}</span>
    </div>
</template>

<script>
export default {
    props: ['icon', 'text', 'color']
}
</script>

<style>
.toolTip {
    position: relative;
    cursor: pointer;
    font-size: 13px;
    display:inline-block;
}

.toolTipText {
    visibility: hidden;
    width: 200px;
    border-radius: 6px;
    position: absolute;
    z-index: 1;
    right: 12px;
    top: 12px;
}

.toolTip:hover .toolTipText {
    visibility: visible;
}
</style>
