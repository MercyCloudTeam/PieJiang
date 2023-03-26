<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    user: Array,
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">You're logged in!</div>
                    <div class="p-6 text-gray-900">
                        User Token: {{user.token}} <br>
                        Clash URL: {{route('api.proxy.clash.config')}}{{"?token="+ user.token + "&download=1"}} <br>
                        CA Crt: <a :href="route('ca')">Download</a>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
