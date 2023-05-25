<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Link, useForm, usePage} from '@inertiajs/vue3';
import {ref} from "vue";
import DangerButton from "@/Components/DangerButton.vue";


const props = defineProps({
    accesses: Array,
});
const user = usePage().props.auth.user;

const delAccessForm = useForm({
});
const delAccess = (id) => {
    delAccessForm.delete(route('access.destroy', id));
}

</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Accesses List</h2>
        </header>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <!-- head -->
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Port</th>
                    <th>Server[IN]</th>
                    <th>Proxy[OUT]</th>
                    <th>Proxy[Type]</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in props.accesses">
                    <th>{{item.id}}</th>
                    <td>{{item.name}}</td>
                    <td>{{item.port}}</td>
                    <td>{{item.server.name}}</td>
                    <td>{{item.proxy.name}}</td>
                    <td>{{item.proxy.type}}</td>
                    <td>{{item.type}}</td>
                    <td>
                        <div class="flex justify-center space-x-1">
                            <PrimaryButton  class="mr-2 ml-2" >Edit</PrimaryButton>
                            <DangerButton class="mr-2 ml-2" @click="delAccess(item.id)" >Delete</DangerButton>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
