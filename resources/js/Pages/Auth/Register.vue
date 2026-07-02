<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useRecaptcha } from '@/composables/useRecaptcha';
import { Head, Link, useForm } from '@inertiajs/vue3';

const { executeRecaptcha } = useRecaptcha();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    recaptcha_token: '',
});

const submit = async () => {
    form.recaptcha_token = (await executeRecaptcha('register')) ?? '';

    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation', 'recaptcha_token'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Inscription" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Pseudo" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="E-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Mot de passe" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirmer le mot de passe"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <InputError class="mt-4" :message="form.errors.recaptcha_token" />

            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Ce formulaire est protégé par Google reCAPTCHA.
            </p>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                >
                    Déjà inscrit ?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    S'inscrire
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
