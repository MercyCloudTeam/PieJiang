<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import AccessList from '@/Pages/Access/Partials/AccessList.vue';
import CreateAccessForm from '@/Pages/Access/Partials/CreateAccessForm.vue';

defineProps({
    proxies: Array,
    servers: Array,
    accesses: Array,
    types: Array,
});
</script>

<template>
    <Head title="Access" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Access</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg" v-if="accesses.length > 0">
                    <AccessList :accesses="accesses" />
                </div>
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <CreateAccessForm :proxies="proxies" :types="types" :servers="servers" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
