<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useRecaptcha } from '@/composables/useRecaptcha';
import { Head, useForm } from '@inertiajs/vue3';

const { executeRecaptcha } = useRecaptcha();

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    recaptcha_token: '',
});

const submit = async () => {
    form.recaptcha_token = (await executeRecaptcha('forgot_password')) ?? '';

    form.post(route('password.email'), {
        onFinish: () => form.reset('recaptcha_token'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Mot de passe oublié" />

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Mot de passe oublié ? Aucun problème. Indiquez-nous simplement votre adresse 
            e-mail et nous vous enverrons un lien de réinitialisation qui vous permettra 
            d'en choisir un nouveau.
        </div>

        <div
            v-if="status"
            class="mb-4 text-sm font-medium text-green-600 dark:text-green-400"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="E-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <InputError class="mt-4" :message="form.errors.recaptcha_token" />

            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Ce formulaire est protégé par Google reCAPTCHA.
            </p>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Envoyer le lien de réinitialisation
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
