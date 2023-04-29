<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import {Link, useForm, usePage} from '@inertiajs/vue3';
import {ref} from "vue";
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    rules: Array,
});
const user = usePage().props.auth.user;

const deleteRuleForm = useForm({
});
const deleteRule = (id) => {
    deleteRuleForm.delete(route('proxy-groups.rule.destroy', id), {
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
            <h2 class="text-lg font-medium text-gray-900">Rules List</h2>
        </header>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <!-- head -->
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Content</th>
                    <th>Type</th>
                    <th>Resolve</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in props.rules">
                    <th>{{item.id}}</th>
                    <td>{{item.content}}</td>
                    <td>{{item.type}}</td>
                    <td>{{item.resolve}}</td>
                    <td class="gap-2">
                        <button @click="deleteRule(item.id)" >
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
