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
            let id = data.id || null
            let message = data.text || ''
            let color =  data.color ? this.colors[data.color] : '#0f0f0f';
            let timeout = data.timeout || 5000

            let existingMessage = this.messages.filter(item => item.id == id)
            let existingMessageIndex = this.messages.map(item => item.id).indexOf(id)

            if(existingMessage.length != 0) {
                this.messages.splice(existingMessageIndex, 1)
                setTimeout(() => {
                    this.messages.push({ id, message, color, timeout })
                }, 1000);
            } else {
                this.messages.push({ id, message, color, timeout })
            }
        });

        bus.$on("REMOVE_PREVIOUS_SNACKBAR", data => {
            setTimeout(() => {
                this.messages.splice(this.messages.length - 2, 1)
            }, 200)
        })

        bus.$on("CLEAR_SNACKBARS", data => {
            this.messages.splice(0, this.messages.length)
        })
    }
};
</script>

<style>
    .v-snack__content {
        padding: 0px 16px;
    }
</style>
