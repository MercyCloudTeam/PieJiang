<script setup>
import useClipboard from 'vue-clipboard3'
import {notify} from "@kyvg/vue3-notification";

const props = defineProps({
    classes: String,
    text: String,

});

const {toClipboard} = useClipboard()

const copyText = (text) => {
    toClipboard(text)
    notify({
        title: "Success!",
        text: "Copied to clipboard",
        type: "success",
    });
}

</script>

<template>
    <span :class="classes" @click="copyText(text)">
        {{text}}
    </span>
</template>
