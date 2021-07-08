<template>
    <v-snackbar v-model="snackbar.show" right dark :color="snackbar.color">
        <span class="subtitle-1">{{ snackbar.text }}</span>
        <template v-slot:action="{ attrs }">
        <v-btn @click="snackbar.show = false" dark icon :bind="attrs">
            <v-icon>mdi-close</v-icon>
        </v-btn>
        </template>
    </v-snackbar>
</template>

<script>
import bus from "../../eventBus";

export default {
    data() {
        return {
            snackbar: {
                show: false,
                color: "",
                text: ""
            }
        }
    },
    mounted() {
        bus.$on("SHOW_SNACKBAR", data => {
            this.snackbar.show = true;
            this.snackbar.color = data.color == 'success' ? '#5cb85c' : '#d9534f';
            this.snackbar.text = data.text;
        });
    }
};
</script>

<style>
    .v-snack__content {
        padding: 0px 16px;
    }
</style>
