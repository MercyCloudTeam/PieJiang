<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Link, useForm, usePage} from '@inertiajs/vue3';
import {ref} from "vue";


const props = defineProps({
    proxies: Array,
});
const user = usePage().props.auth.user;


const deleteProxyForm = useForm({
});



const deleteProxy = (id) => {
    deleteProxyForm.delete(route('proxies.destroy', id), {
        preserveScroll: true,
        onSuccess: () => deleteProxyForm.reset(),
        onError: () => {
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
                    <td>
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
    </section>
</template>
