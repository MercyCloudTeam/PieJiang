<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import useClipboard from 'vue-clipboard3'
const { toClipboard } = useClipboard()
import { notify } from "@kyvg/vue3-notification";

defineProps({
    user: Array,
});



const copyText = (text) => {
    toClipboard(text)
    notify({
        title: "Success!",
        text: "Copied to clipboard",
        type: "success",
    });
}

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
                        <p>User Token: <span class="kbd" @click="copyText(user.token)">{{user.token}}</span></p>
                        <p>Clash URL: <span class="kbd" @click="copyText(route('api.proxy.clash.config') + '?token=' + user.token + '&download=1')">{{route('api.proxy.clash.config')}}{{"?token="+ user.token}}</span> <br></p>
                        <p>CA Crt: <a :href="route('ca')">Download</a></p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
