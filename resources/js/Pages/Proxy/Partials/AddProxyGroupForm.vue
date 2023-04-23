<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Link, useForm, usePage} from '@inertiajs/vue3';
import {ref} from "vue";
import { notify } from "@kyvg/vue3-notification";

const props = defineProps({
    // user: Array,
});

const user = usePage().props.auth.user;

const form = useForm({
    name: null,
    type: null,
    default_proxy_id: null,
});

const createProxyGroup = () => {
    form.post(route('proxy-groups.store'), {
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
            <h2 class="text-lg font-medium text-gray-900">Add Proxy Groups</h2>

        </header>

            <div>
                <InputLabel for="name" value="Name"/>
                <TextInput
                    id="ip"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    autofocus
                    autocomplete="name"
                />
            </div>



            <div class="flex items-center gap-4 mt-4">
                <PrimaryButton :disabled="form.processing" @click="createProxyGroup">Create</PrimaryButton>
                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                </Transition>
            </div>
    </section>
</template>
