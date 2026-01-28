<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const form = useForm({
    email: page.props.auth?.user?.email || '',
    subject: '',
    message: '',
});

const submit = () => {
    form.post(route('contact.store'));
};
</script>

<template>
    <Head title="Nous contacter" />

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Header -->
        <nav class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="flex items-center gap-2">
                        <span class="text-xl font-bold text-blue-600 dark:text-blue-400">Vinyls Collection</span>
                    </Link>
                    <div class="flex items-center gap-4">
                        <template v-if="isAuthenticated">
                            <Link :href="route('dashboard')" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                                Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                                Connexion
                            </Link>
                            <Link :href="route('register')" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Inscription
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-8">
                        <div class="mb-8 text-center">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                Nous contacter
                            </h2>
                            <p class="mt-2 text-gray-600 dark:text-gray-400">
                                Une question ? Un probleme ? N'hesitez pas a nous contacter.
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Adresse email
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Sujet
                                </label>
                                <input
                                    id="subject"
                                    v-model="form.subject"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-500': form.errors.subject }"
                                />
                                <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.subject }}
                                </p>
                            </div>

                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Message
                                </label>
                                <textarea
                                    id="message"
                                    v-model="form.message"
                                    rows="6"
                                    required
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-500': form.errors.message }"
                                ></textarea>
                                <p v-if="form.errors.message" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.message }}
                                </p>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex w-full items-center justify-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-gray-800"
                            >
                                <svg v-if="form.processing" class="mr-2 h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Envoyer
                            </button>
                        </form>

                        <p v-if="isAuthenticated" class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
                            Vous etes connecte ?
                            <Link :href="route('support.index')" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                Utilisez le chat support
                            </Link>
                            pour une reponse plus rapide.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
