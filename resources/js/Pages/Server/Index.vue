<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head} from '@inertiajs/vue3';
import AddServerForm from '@/Pages/Server/Partials/AddServerForm.vue';
import ServerList from '@/Pages/Server/Partials/ServerList.vue';

defineProps({
    servers: Array,
});
</script>

<template>
    <Head title="Servers"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Servers</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg"  v-if="servers.length > 0" >
                    <ServerList :servers="servers" />
                </div>
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <AddServerForm />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
