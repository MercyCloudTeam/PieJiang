<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import ProxiesList from '@/Pages/Proxy/Partials/ProxiesList.vue';
import ProxyGroupList from '@/Pages/Proxy/Partials/ProxyGroupList.vue';

defineProps({
    proxies: Array,
    proxyGroups: Array,
});
</script>

<template>
    <Head title="Proxies" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Proxies</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <ProxiesList :proxies="proxies" />
                </div>
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <ProxyGroupList :proxyGroups="proxyGroups" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
