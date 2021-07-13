<template>
    <v-snackbars :objects.sync="messages" right>
        <template v-slot:default="{ message }">
            <span class="text-base">{{ message }}</span>
        </template>
        <template v-slot:action="{ close }">
            <v-btn @click="close()" dark icon>
                <v-icon small>mdi-close</v-icon>
            </v-btn>
        </template>
    </v-snackbars>
</template>

<script>
import bus from "../../eventBus";
import VSnackbars from 'v-snackbars'

export default {
    components: {
        VSnackbars
    },
    data() {
        return {
            colors: {
                success: '#5cb85c',
                error: '#d9534f',
                primary: '#0275d8'
            },
            messages: []
        }
    },
    mounted() {
        bus.$on("SHOW_SNACKBAR", data => {
            let message = data.text || ''
            let color =  data.color ? this.colors[data.color] : '#0f0f0f';
            let timeout = data.timeout || 5000

            this.messages.push({ message, color, timeout })
        });
    }
};
</script>

<style>
    .v-snack__content {
        padding: 0px 16px;
    }
</style>
