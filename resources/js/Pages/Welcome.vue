<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const isLoading = ref(true);

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

onMounted(() => {
    isLoading.value = false;
});

function handleImageError() {
    document.getElementById('screenshot-container')?.classList.add('!hidden');
    document.getElementById('docs-card')?.classList.add('!row-span-1');
    document.getElementById('docs-card-content')?.classList.add('!flex-row');
    document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>
    <Head>
        <title>Vinyls Collection - Gérez votre collection de vinyles</title>
        <meta name="description" content="Plateforme communautaire pour gérer, partager et discuter de votre collection de vinyles. Forum actif et outils de gestion complets." />
    </Head>

    <div v-if="isLoading" class="fixed inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900">
        <div class="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-blue-500"></div>
    </div>

    <div v-else class="relative min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Hero Section -->
        <div class="relative overflow-hidden">
            <div class="absolute inset-0">
                <img class="h-full w-full object-cover opacity-10" src="/images/vinyl-bg.jpg" alt="Vinyl background" />
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-blue-800 mix-blend-multiply"></div>
            </div>
            <div class="relative max-w-7xl mx-auto">
                <div class="relative z-10 pb-8 bg-transparent sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                    <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                        <div class="sm:text-center lg:text-left">
                            <h1 class="text-4xl tracking-tight font-extrabold text-white sm:text-5xl md:text-6xl">
                                <span class="block">Votre passion</span>
                                <span class="block text-blue-200">pour les vinyles</span>
                            </h1>
                            <p class="mt-3 text-base text-blue-100 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                                Gérez votre collection, échangez avec d'autres passionnés et découvrez de nouveaux trésors musicaux sur notre plateforme communautaire.
                            </p>
                            <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                                <div class="rounded-md shadow">
                                    <Link href="/forum" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 md:py-4 md:text-lg md:px-10">
                                        Accéder au forum
                                    </Link>
                                </div>
                                <div class="mt-3 sm:mt-0 sm:ml-3">
                                    <Link v-if="$page.props.auth.user" href="/dashboard" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10">
                                        Mon espace
                                    </Link>
                                    <Link v-else href="/register" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10">
                                        Créer un compte
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="py-12 bg-white dark:bg-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="lg:text-center">
                    <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Fonctionnalités</h2>
                    <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                        Une meilleure façon de gérer votre collection
                    </p>
                </div>

                <div class="mt-10">
                    <div class="space-y-10 md:space-y-0 md:grid md:grid-cols-3 md:gap-x-8 md:gap-y-10">
                        <div class="relative">
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div class="ml-16">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Gestion de collection</h3>
                                <p class="mt-2 text-base text-gray-500 dark:text-gray-400">
                                    Organisez et suivez facilement votre collection de vinyles avec notre interface intuitive.
                                </p>
                            </div>
                        </div>

                        <div class="relative">
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                </svg>
                            </div>
                            <div class="ml-16">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Forum communautaire</h3>
                                <p class="mt-2 text-base text-gray-500 dark:text-gray-400">
                                    Échangez avec d'autres passionnés, partagez vos découvertes et obtenez des conseils.
                                </p>
                            </div>
                        </div>

                        <div class="relative">
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <div class="ml-16">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Recherche avancée</h3>
                                <p class="mt-2 text-base text-gray-500 dark:text-gray-400">
                                    Trouvez facilement les vinyles que vous recherchez grâce à notre système de recherche performant.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800">
            <div class="max-w-2xl mx-auto text-center py-16 px-4 sm:py-20 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                    <span class="block">Prêt à commencer ?</span>
                    <span class="block">Rejoignez notre communauté dès aujourd'hui.</span>
                </h2>
                <p class="mt-4 text-lg leading-6 text-blue-100">
                    Créez votre compte gratuitement et commencez à gérer votre collection de vinyles.
                </p>
                <Link v-if="!$page.props.auth.user" href="/register" class="mt-8 w-full inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 sm:w-auto">
                    Créer un compte
                </Link>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 md:flex md:items-center md:justify-between lg:px-8">
                <div class="mt-8 md:mt-0">
                    <p class="text-center text-base text-gray-400">
                        &copy; {{ new Date().getFullYear() }} Vinyls Collection. Tous droits réservés.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
/* Ajout d'un style pour éviter le FOUC */
[v-cloak] {
    display: none;
}
</style>
