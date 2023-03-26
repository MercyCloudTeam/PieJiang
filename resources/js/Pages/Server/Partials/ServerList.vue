<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Link, useForm, usePage} from '@inertiajs/vue3';
import {ref} from "vue";


const props = defineProps({
    servers: Array,
});
const user = usePage().props.auth.user;

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
                    <th>{{item.id}}</th>
                    <td>{{item.name}}</td>
                    <td>{{ item.ip }}</td>
                    <td class="text-xs">{{ item.domain }}</td>
                    <td>{{ item.country }}</td>
                    <td>{{ item.status }}</td>
                    <td >
<!--                        <p><a :href="route('api.server.xray.config',{server:item.id,token:item.token})">Xray Server Config URL</a></p>-->
                        <div class="dropdown dropdown-left">
                            <label tabindex="0" class="btn btn-circle btn-ghost btn-xs text-info">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-4 h-4 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </label>
                            <ul tabindex="0" class="dropdown-content text-sm menu p-2 shadow bg-base-100 rounded-box w-52">
                                <li><a :href="route('api.server.xray.config',{server:item.id,token:item.token})">Xray Server URL</a></li>
                                <li><a :href="route('api.server.xray.config.access',{server:item.id,token:item.token})">Xray Access URL</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
