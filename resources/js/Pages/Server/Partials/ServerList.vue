<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Link, useForm, usePage} from '@inertiajs/vue3';
import {ref} from "vue";
import useClipboard from 'vue-clipboard3'
import {VAceEditor} from 'vue3-ace-editor';
import {notify} from "@kyvg/vue3-notification";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const downloadInitialBash = ref('');
const {toClipboard} = useClipboard()
const openUpdateModal = ref(false);

const copyInitialBash = async (url) => {
    try {
        await toClipboard("curl -L -o initial-piejiang.sh " + url + " || wget -O initial-piejiang.sh " + url + " && bash initial-piejiang.sh")
        console.log('Copied to clipboard')
        notify({
            title: "Copied to clipboard",
            text: "The initial bash script has been copied to your clipboard",
            type: "success",
            duration: 3000,
        });
    } catch (e) {
        console.error(e)
        notify({
            title: "Failed to copy to clipboard",
            text: "The initial bash script failed to copy to your clipboard",
            type: "error",
            duration: 3000,
        });
    }

}


const updateServerForm = useForm({
    id: '',
    name: '',
    country: '',
    ip: '',
    domain: '',
    config: '',
});
const closeModal = () => {
    openUpdateModal.value = false;
    updateServerForm.reset();
};

const openUpdateServerModal = (server) => {
    updateServerForm.reset();
    updateServerForm.name = server.name;
    updateServerForm.country = server.country;
    updateServerForm.ip = server.ip;
    updateServerForm.domain = server.domain;
    updateServerForm.id = server.id;
    updateServerForm.config = JSON.stringify(server.config, null, 4);
    // updateServerForm.config = JSON.stringify(server.config, null, 4);
    openUpdateModal.value = true;
}

const updateServer = () => {
    updateServerForm.patch(route('servers.update', updateServerForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            updateServerForm.reset();
            openUpdateModal.value = false;
            notify({
                title: "Success",
                text: "Server updated successfully",
                type: "success",
                duration: 3000,
            });
        },
        onError: () => {
        },
    });
}

const props = defineProps({
    servers: Array,
});
const user = usePage().props.auth.user;

const destroyCert = async (id, token) => {
    //delete request
    //
    const {data} = await axios.delete(route('api.server.cert.destroy', {server: id, token: token}));
    // const {data} = await axios.post(route('api.server.cert.destroy', {server: id}));
    if (data.status === 'success') {
        notify({
            title: "Success",
            text: "Certificate Deleted",
            type: "success",
            duration: 3000,
        });
        window.location.reload();
    }
}
const deleteServerForm = useForm({});
const deleteServer = async (id) => {
    deleteServerForm.delete(route('servers.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteServerForm.reset();
            notify({
                title: "Success",
                text: "Server Deleted",
                type: "success",
                duration: 3000,
            });
        },
        onError: () => {
        },
    });
}


</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Server List</h2>
        </header>

        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <!-- head -->
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>IP</th>
                    <th>Domain</th>
                    <th>Country</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in props.servers">
                    <th>{{ item.id }}</th>
                    <td>{{ item.name }}</td>
                    <td>{{ item.ip }}</td>
                    <td class="text-xs">{{ item.domain }}</td>
                    <td>{{ item.country }}</td>
                    <td>{{ item.status }}</td>
                    <td>
                        <!--                        <p><a :href="route('api.server.xray.config',{server:item.id,token:item.token})">Xray Server Config URL</a></p>-->
                        <!-- Edit Button  -->
                        <button @click="openUpdateServerModal(item)">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" width="16px" height="16px">
                                <path
                                    d="M 43.125 2 C 41.878906 2 40.636719 2.488281 39.6875 3.4375 L 38.875 4.25 L 45.75 11.125 C 45.746094 11.128906 46.5625 10.3125 46.5625 10.3125 C 48.464844 8.410156 48.460938 5.335938 46.5625 3.4375 C 45.609375 2.488281 44.371094 2 43.125 2 Z M 37.34375 6.03125 C 37.117188 6.0625 36.90625 6.175781 36.75 6.34375 L 4.3125 38.8125 C 4.183594 38.929688 4.085938 39.082031 4.03125 39.25 L 2.03125 46.75 C 1.941406 47.09375 2.042969 47.457031 2.292969 47.707031 C 2.542969 47.957031 2.90625 48.058594 3.25 47.96875 L 10.75 45.96875 C 10.917969 45.914063 11.070313 45.816406 11.1875 45.6875 L 43.65625 13.25 C 44.054688 12.863281 44.058594 12.226563 43.671875 11.828125 C 43.285156 11.429688 42.648438 11.425781 42.25 11.8125 L 9.96875 44.09375 L 5.90625 40.03125 L 38.1875 7.75 C 38.488281 7.460938 38.578125 7.011719 38.410156 6.628906 C 38.242188 6.246094 37.855469 6.007813 37.4375 6.03125 C 37.40625 6.03125 37.375 6.03125 37.34375 6.03125 Z"/>
                            </svg>
                        </button>

                        <div class="dropdown dropdown-left">
                            <label tabindex="0" class="btn btn-circle btn-ghost btn-xs text-info">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     class="w-4 h-4 stroke-current">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </label>
                            <ul tabindex="0"
                                class="dropdown-content text-sm menu p-2 shadow bg-base-100 rounded-box w-52">
                                <li><a target="_blank"
                                       :href="route('api.server.xray.config',{server:item.id,token:item.token})">Xray
                                    Server URL</a></li>
                                <li><a target="_blank"
                                       :href="route('api.server.xray.config.access',{server:item.id,token:item.token})">Xray
                                    Access URL</a></li>
                                <li><a
                                    @click="copyInitialBash(route('api.server.bash',{server:item.id,token:item.token}))">Copy
                                    Initial Bash</a></li>
                                <li><a target="_blank"
                                       :href="route('api.server.cert',{server:item.id,token:item.token,download:true})">Download
                                    Cert</a></li>
                                <li><a target="_blank"
                                       :href="route('api.server.cert.key',{server:item.id,token:item.token,download:true})">Download
                                    Cert Key</a></li>
                                <li><a @click="destroyCert(item.id,item.token)">Destroy Cert</a></li>
                            </ul>
                        </div>
                        <!-- Deleate -->
                        <button @click="deleteServer(item.id)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 class="w-4 h-4 stroke-current">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12"></path>
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
                    Edit Server
                </h2>

                <div class="mt-4">
                    <InputLabel for="name" value="Name"/>
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="updateServerForm.name"
                        :class="{ 'border-red-300': updateServerForm.errors.name }"
                    />
                    <InputError :message="updateServerForm.errors.name"/>
                </div>


                <div class="mt-4">
                    <InputLabel for="ip" value="IP"/>
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="updateServerForm.ip"
                        :class="{ 'border-red-300': updateServerForm.errors.ip }"
                    />
                    <InputError :message="updateServerForm.errors.ip"/>
                </div>

                <div class="mt-4">
                    <InputLabel for="country" value="Counrty"/>
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="updateServerForm.country"
                        :class="{ 'border-red-300': updateServerForm.errors.country }"
                    />
                    <InputError :message="updateServerForm.errors.country"/>
                </div>


                <div class="mt-4">
                    <InputLabel for="domain" value="Domain"/>
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="updateServerForm.domain"
                        :class="{ 'border-red-300': updateServerForm.errors.domain }"
                    />
                    <InputError :message="updateServerForm.errors.domain"/>
                </div>

                <div class="mt-4">
                    <InputLabel for="config" value="Config"/>
                    <v-ace-editor
                        v-model:value="updateServerForm.config"
                        style="height: 20rem"
                    />
                    <InputError :message="updateServerForm.errors.config"/>
                </div>


                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancel</SecondaryButton>

                    <PrimaryButton
                        class="ml-3"
                        :class="{ 'opacity-25': updateServerForm.processing }"
                        :disabled="updateServerForm.processing"
                        @click="updateServer"
                    >
                        Update
                    </PrimaryButton>
                </div>

            </div>
        </Modal>
    </section>
</template>
