<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import RulesList from '@/Pages/ProxyGroups/Partials/RulesList.vue';
import AddRuleForm from '@/Pages/ProxyGroups/Partials/AddRuleForm.vue';

defineProps({
    rules: Array,
    proxyGroup: Array,
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
                    <RulesList :rules="rules" />
                </div>
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <AddRuleForm :proxyGroup="proxyGroup" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
