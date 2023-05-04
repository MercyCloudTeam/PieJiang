<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import {Link, useForm, usePage} from '@inertiajs/vue3';
import {ref} from "vue";
import Modal from '@/Components/Modal.vue';
import { VAceEditor } from 'vue3-ace-editor';
import { notify } from "@kyvg/vue3-notification";

const props = defineProps({
    proxies: Array,
    types: Array,
});
const user = usePage().props.auth.user;
const selectedProxy = ref(null);
const openUpdateModal = ref(false);

const deleteProxyForm = useForm({
});

const updateProxyForm = useForm({
    id: '',
    name: '',
    type: '',
    domain: '',
    port: '',
    config: '',
});

const closeModal = () => {
    openUpdateModal.value = false;
    updateProxyForm.reset();
};

const openUpdateProxyModal = (proxy) => {
    updateProxyForm.reset();
    selectedProxy.value = proxy;
    updateProxyForm.name = proxy.name;
    updateProxyForm.type = proxy.type;
    updateProxyForm.domain = proxy.domain;
    updateProxyForm.port = proxy.port;
    updateProxyForm.id = proxy.id;
    updateProxyForm.config = JSON.stringify(proxy.config, null, 4);
    openUpdateModal.value = true;
}

const deleteProxy = (id) => {
    deleteProxyForm.delete(route('proxies.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteProxyForm.reset();
            notify({
                title: "Success",
                text: "Proxy deleted successfully",
                type: "success",
                duration: 3000,
            });
        },
        onError: () => {
            notify({
                title: "Error",
                text: "Proxy delete failed",
                type: "error",
                duration: 3000,
            });
        },
    });
}

const updateProxy = () => {
    updateProxyForm.patch(route('proxies.update', updateProxyForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            updateProxyForm.reset();
            openUpdateModal.value = false;
            notify({
                title: "Success",
                text: "Proxy updated successfully",
                type: "success",
                duration: 3000,
            });
        },
        onError: () => {
            notify({
                title: "Error",
                text: "Proxy update failed",
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
            <h2 class="text-lg font-medium text-gray-900">Proxies List</h2>
        </header>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <!-- head -->
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Server</th>
                    <th>Domain</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in props.proxies">
                    <th>{{item.id}}</th>
                    <td>{{item.name}}</td>
                    <td>{{item.type}}</td>
                    <td>[{{item.server.id}}]{{item.server.name}}</td>
                    <td>{{item.domain}}</td>
                    <td class="gap-2">
                        <!-- Edit Button  -->
                        <button @click="openUpdateProxyModal(item)">
                            <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 50 50" width="16px" height="16px"><path d="M 43.125 2 C 41.878906 2 40.636719 2.488281 39.6875 3.4375 L 38.875 4.25 L 45.75 11.125 C 45.746094 11.128906 46.5625 10.3125 46.5625 10.3125 C 48.464844 8.410156 48.460938 5.335938 46.5625 3.4375 C 45.609375 2.488281 44.371094 2 43.125 2 Z M 37.34375 6.03125 C 37.117188 6.0625 36.90625 6.175781 36.75 6.34375 L 4.3125 38.8125 C 4.183594 38.929688 4.085938 39.082031 4.03125 39.25 L 2.03125 46.75 C 1.941406 47.09375 2.042969 47.457031 2.292969 47.707031 C 2.542969 47.957031 2.90625 48.058594 3.25 47.96875 L 10.75 45.96875 C 10.917969 45.914063 11.070313 45.816406 11.1875 45.6875 L 43.65625 13.25 C 44.054688 12.863281 44.058594 12.226563 43.671875 11.828125 C 43.285156 11.429688 42.648438 11.425781 42.25 11.8125 L 9.96875 44.09375 L 5.90625 40.03125 L 38.1875 7.75 C 38.488281 7.460938 38.578125 7.011719 38.410156 6.628906 C 38.242188 6.246094 37.855469 6.007813 37.4375 6.03125 C 37.40625 6.03125 37.375 6.03125 37.34375 6.03125 Z"/></svg>
                        </button>
                        <!-- Deleate -->
                        <button @click="deleteProxy(item.id)" >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-4 h-4 stroke-current">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <Modal :show="openUpdateModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Edit Proxy
                </h2>

                <div class="mt-4">
                    <InputLabel for="name" value="Name" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="updateProxyForm.name"
                        :class="{ 'border-red-300': updateProxyForm.errors.name }"
                    />
                    <InputError :message="updateProxyForm.errors.name" />
                </div>

                <div>
                    <InputLabel for="type" value="Type" />
                    <select
                        id="type"
                        class="mt-1 block w-full"
                        v-model="updateProxyForm.type"
                        :class="{ 'border-red-300': updateProxyForm.errors.type }"
                    >
                        <option  v-for="item in props.types">{{ item }}</option>
                    </select>
                    <InputError :message="updateProxyForm.errors.type" />
                </div>

                <div class="mt-4">
                    <InputLabel for="port" value="Port" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="updateProxyForm.port"
                        :class="{ 'border-red-300': updateProxyForm.errors.port }"
                    />
                    <InputError :message="updateProxyForm.errors.port" />
                </div>
                <div class="mt-4">
                    <InputLabel for="domain" value="Domain" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="updateProxyForm.domain"
                        :class="{ 'border-red-300': updateProxyForm.errors.domain }"
                    />
                    <InputError :message="updateProxyForm.errors.domain" />
                </div>

                <div class="mt-4">
                    <InputLabel for="config" value="Config" />
                    <v-ace-editor
                        v-model:value="updateProxyForm.config"
                        style="height: 20rem"
                    />
                    <InputError :message="updateProxyForm.errors.config" />
                </div>


                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancel </SecondaryButton>

                    <PrimaryButton
                        class="ml-3"
                        :class="{ 'opacity-25': updateProxyForm.processing }"
                        :disabled="updateProxyForm.processing"
                        @click="updateProxy"
                    >
                        Update
                    </PrimaryButton>
                </div>

            </div>
        </Modal>
    </section>
</template>
