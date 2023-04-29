<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Link, useForm, usePage} from '@inertiajs/vue3';
import {ref} from "vue";
import {notify} from "@kyvg/vue3-notification";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    user: Array,
    proxyGroup: Array,
});

const user = usePage().props.auth.user;

const types = [
    "DOMAIN-SUFFIX",
    "DOMAIN-KEYWORD",
    "DOMAIN",
    "IP-CIDR",
    "SRC-IP-CIDR",
    "GEOIP",
    "DST-PORT",
    "SRC-PORT",
    "PROCESS-NAME",
    "RULE-SET",
    "MATCH"

]

const form = useForm({
    content: null,
    type: null,
    resolve: false
});

const createRule = () => {
    form.put(route('proxy-groups.rule.store', props.proxyGroup.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            notify({
                title: "Success",
                text: "Proxy Group created successfully",
                type: "success",
                duration: 3000,
            });
        },
        onError: () => {
            notify({
                title: "Error",
                text: "Proxy Group creation failed",
                type: "error",
                duration: 3000,
            });
        },
    });
}

</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 mb-3">Add Rule</h2>

        </header>

        <div class="mt-3 mb-3">
            <InputLabel for="content" value="Content"/>
            <TextInput
                id="ip"
                type="text"
                class="mt-1 block w-full"
                v-model="form.content"
                autofocus
                autocomplete="content"
            />
        </div>


        <div class="mt-3 mb-3">
            <InputLabel for="type" value="Type"/>
            <select
                id="type"
                class="mt-1 block w-full"
                v-model="form.type"
                :class="{ 'border-red-300': form.errors.type }"
            >
                <option v-for="item in types">{{ item }}</option>
            </select>
            <InputError :message="form.errors.type"/>
        </div>


        <div class="mt-3 mb-3">
            <InputLabel for="resolve" value="Resolve"/>
            <input type="checkbox" class="toggle" v-model="form.resolve"/>
        </div>


        <div class="flex items-center gap-4 mt-4">
            <PrimaryButton :disabled="form.processing" @click="createRule">Create</PrimaryButton>
            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
            </Transition>
        </div>
    </section>
</template>
