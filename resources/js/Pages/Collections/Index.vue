<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteCollectionModal from '@/Components/DeleteCollectionModal.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    collections: {
        type: Array,
        default: () => []
    }
});

const showDeleteModal = ref(false);
const collectionToDelete = ref(null);

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const openDeleteModal = (collection) => {
    collectionToDelete.value = collection;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    collectionToDelete.value = null;
};
</script>

<template>
    <Head>
        <title>Mes Collections | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="description" content="Gérez vos collections de vinyles personnalisées." />
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Mes Collections
                </h2>
                <Link href="/collections/create"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    Nouvelle Collection
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="collections.length === 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucune collection</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Commencez par créer votre première collection de vinyles.</p>
                        <div class="mt-6">
                            <Link href="/collections/create"
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Créer une collection
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="collection in collections" :key="collection.id"
                         class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate pr-2">
                                    {{ collection.collection_nom }}
                                </h3>
                                <div class="flex space-x-2 flex-shrink-0">
                                    <Link :href="`/collections/${collection.id}/edit`"
                                       class="text-blue-600 hover:text-blue-800 text-sm">
                                        Modifier
                                    </Link>
                                    <Link :href="`/collections/${collection.id}`"
                                       class="text-green-600 hover:text-green-800 text-sm">
                                        Voir
                                    </Link>
                                    <button
                                        @click="openDeleteModal(collection)"
                                        class="text-red-600 hover:text-red-800 text-sm">
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                        <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                    </svg>
                                    {{ collection.vinyls_count }} vinyle{{ collection.vinyls_count > 1 ? 's' : '' }}
                                </div>
                                
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Modifiée le {{ formatDate(collection.collection_date_modif) }}
                                </div>
                            </div>

                            <div v-if="collection.collection_commentaires" class="mt-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                    {{ collection.collection_commentaires }}
                                </p>
                            </div>

                            <div class="mt-4">
                                <Link :href="`/collections/${collection.id}`"
                                   class="w-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 px-4 py-2 rounded-md text-sm text-center block transition-colors">
                                    Voir la collection
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Collection Modal -->
        <DeleteCollectionModal
            v-if="collectionToDelete"
            :show="showDeleteModal"
            :collection="collectionToDelete"
            @close="closeDeleteModal"
        />
    </AuthenticatedLayout>
</template>