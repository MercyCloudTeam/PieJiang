<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    proxies: Array,
    servers: Array,
    types: Array,
});

const user = usePage().props.auth.user;

const form = useForm({
    name: null,
    proxy_id: null,
    server_id: null,
    port: null,
    type: 'vmess',
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Create Access</h2>

            <p class="mt-1 text-sm text-gray-600">
                Add Access
            </p>
        </header>

        <form @submit.prevent="form.post(route('access.store'))" class="mt-6 space-y-6">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>
            <div>
                <InputLabel for="port" value="Port" />

                <TextInput
                    id="port"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.port"
                    required
                    autofocus
                />

                <InputError class="mt-2" :message="form.errors.port" />
            </div>
            <div>
                <InputLabel for="server_id" value="Server" />

                <select v-model="form.server_id" class="mt-1 block w-full">
                    <option v-for="server in props.servers" :value="server.id">[{{server.country}}]{{server.name}} - {{server.ip}}</option>
                </select>

                <InputError class="mt-2" :message="form.errors.server_id" />
            </div>
            <div>
                <InputLabel for="proxy_id" value="Proxy" />

                <select v-model="form.proxy_id" class="mt-1 block w-full">
                    <option v-for="proxy in props.proxies" :value="proxy.id">{{proxy.display_name}}</option>
                </select>

                <InputError class="mt-2" :message="form.errors.proxy_id" />
            </div>
            <div>
                <InputLabel for="type" value="Type" />

                <select v-model="form.type" class="mt-1 block w-full">
                    <option v-for="type in props.types" :value="type">{{type}}</option>
                </select>

                <InputError class="mt-2" :message="form.errors.type" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Create</PrimaryButton>

                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
