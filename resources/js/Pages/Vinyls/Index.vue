<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    vinyls: {
        type: Array,
        default: () => []
    }
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="Mes Vinyles" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Mes Vinyles
                </h2>
                <Link href="/vinyls/create"
                   class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors">
                    Ajouter un vinyle
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="vinyls.length === 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun vinyle</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Commencez par ajouter votre premier vinyle à votre collection.</p>
                        <div class="mt-6">
                            <Link href="/vinyls/create"
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                                Ajouter un vinyle
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="vinyl in vinyls" :key="vinyl.id"
                                 class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <div class="flex items-start space-x-4">
                                    <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0">
                                        <img v-if="vinyl.pochette" 
                                             :src="vinyl.pochette" 
                                             :alt="vinyl.vinyl_nom"
                                             class="w-full h-full object-cover">
                                        <div v-else class="w-full h-full bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                            </svg>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-gray-900 dark:text-white text-sm">
                                            {{ vinyl.vinyl_nom }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ vinyl.artiste }}
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            Collection: {{ vinyl.collection_nom }}
                                        </p>
                                        <div class="flex items-center mt-2 space-x-4 text-xs text-gray-400">
                                            <span v-if="vinyl.prix_achat">
                                                {{ vinyl.prix_achat }}€
                                            </span>
                                            <span v-if="vinyl.note">
                                                ⭐ {{ vinyl.note }}/10
                                            </span>
                                            <span>
                                                {{ formatDate(vinyl.date_ajout) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-if="vinyl.commentaires" class="mt-3 text-xs text-gray-600 dark:text-gray-300">
                                    {{ vinyl.commentaires }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>