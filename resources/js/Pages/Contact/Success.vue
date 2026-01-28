<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    reference: {
        type: String,
        default: null,
    },
});

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
</script>

<template>
    <Head title="Message envoye" />

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
                    <div class="p-8 text-center">
                        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <h2 class="mb-4 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Message envoye !
                        </h2>

                        <p class="mb-6 text-gray-600 dark:text-gray-400">
                            Nous avons bien recu votre demande et nous vous repondrons dans les plus brefs delais.
                        </p>

                        <div v-if="reference" class="mb-8 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Votre numero de reference :
                            </p>
                            <p class="mt-1 text-lg font-mono font-semibold text-gray-900 dark:text-gray-100">
                                {{ reference }}
                            </p>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Conservez ce numero pour suivre votre demande.
                            </p>
                        </div>

                        <p class="mb-8 text-sm text-gray-600 dark:text-gray-400">
                            Un email de confirmation a ete envoye a votre adresse.
                        </p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                            <Link
                                href="/"
                                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                            >
                                Retour a l'accueil
                            </Link>
                            <Link
                                :href="route('contact.create')"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                            >
                                Envoyer un autre message
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
