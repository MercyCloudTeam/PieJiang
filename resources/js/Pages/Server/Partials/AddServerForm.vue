<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Link, useForm, usePage} from '@inertiajs/vue3';
import {ref} from "vue";


const registerUrl = ref({
    plain: null,
    params: null,
})

const props = defineProps({
    // user: Array,
});

const user = usePage().props.auth.user;

const form = useForm({
    ip: null,
    name: null,
    location: null,
    country: null,
});

const generateUrl = () => {
    // form.get(route('api.user.server.register.url'), {
    //     onSuccess: (data ) => {
    //         console.log(data)
    //         console.log('success');
    //     },
    // });
    axios({
        method: 'get',
        url: route('api.user.server.register.url'),
        params: {
            token: user.token,
            ip: form.ip,
            name: form.name,
            location: form.location,
            country: form.country
        }
    }).then(function (response) {
        let data = response.data.data;
            console.log(response.data.data.params)
            registerUrl.value.params = data.params
            registerUrl.value.plain = data.plain
    }).catch(function (error) {
        console.log(error);
    });
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Add Server</h2>

            <p class="mt-1 text-sm bg-dots-darker text-gray-600 gap-3" v-if="registerUrl">
                Params: {{ registerUrl.params }} <br>
                Plain:  {{ registerUrl.plain }}
            </p>
        </header>

        <form @submit.prevent="generateUrl" class="mt-6 space-y-6">
            <div>
                <InputLabel for="ip" value="Ip Address"/>
                <TextInput
                    id="ip"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.ip"
                    autofocus
                    autocomplete="ip"
                />
            </div>

            <div>
                <InputLabel for="name" value="Name"/>
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    autocomplete="name"
                />
            </div>

            <div>
                <InputLabel for="location" value="Location"/>
                <TextInput
                    id="location"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.location"
                    autocomplete="location"
                />
            </div>

            <div>
                <InputLabel for="country" value="Country"/>
                <TextInput
                    id="country"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.country"
                    autocomplete="country"
                />
            </div>


            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Generate</PrimaryButton>

                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
