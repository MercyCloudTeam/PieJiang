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

                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
