<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    collectionId: {
        type: Number,
        default: null
    },
    collections: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'openManualModal']);

// Variables pour Discogs
const discogsQuery = ref('');
const discogsResults = ref([]);
const isSearchingDiscogs = ref(false);
const selectedCollectionId = ref(props.collectionId);
const showCollectionError = ref(false);

// Réinitialiser selectedCollectionId quand collectionId change
watch(() => props.collectionId, (newValue) => {
    selectedCollectionId.value = newValue;
});

const closeModal = () => {
    discogsQuery.value = '';
    discogsResults.value = [];
    hasSearched.value = false;
    selectedCollectionId.value = props.collectionId;
    emit('close');
};

const hasSearched = ref(false);

const searchDiscogs = async () => {
    if (!discogsQuery.value.trim()) return;

    isSearchingDiscogs.value = true;
    hasSearched.value = true;

    try {
        const response = await fetch(`/api/discogs/search?q=${encodeURIComponent(discogsQuery.value)}`);
        const data = await response.json();
        discogsResults.value = data.results || [];
    } catch (error) {
        console.error('Erreur recherche Discogs:', error);
        discogsResults.value = [];
    } finally {
        isSearchingDiscogs.value = false;
    }
};

const addVinylFromDiscogs = (discogsItem) => {
    // Validation côté client si plusieurs collections
    if (props.collections.length > 1 && !selectedCollectionId.value) {
        showCollectionError.value = true;
        setTimeout(() => {
            showCollectionError.value = false;
        }, 3000);
        return;
    }

    router.post('/vinyls/from-discogs', {
        discogs_id: discogsItem.id,
        discogs_data: discogsItem,
        collection_id: selectedCollectionId.value
    }, {
        onSuccess: () => {
            closeModal();
            router.reload();
        },
        onError: (errors) => {
            console.error('Erreur lors de l\'ajout:', errors);
        }
    });
};

const openManualModal = () => {
    closeModal();
    emit('openManualModal');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-gray-600/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 p-4 sm:p-6 md:p-8">
        <div class="relative mx-auto w-full max-w-4xl shadow-xl rounded-lg bg-white dark:bg-gray-800 max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3rem)] md:max-h-[calc(100vh-4rem)] overflow-hidden flex flex-col">

            <!-- Header fixe -->
            <div class="flex-shrink-0 p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between">
                    <div class="flex-1 pr-4">
                        <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white">
                            Ajouter un vinyle depuis Discogs
                        </h3>
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1 hidden sm:block">
                            Recherchez par artiste, album ou code Discogs :
                            <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">[r123456]</code> ou
                            <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">[m123456]</code>
                        </p>
                    </div>
                    <button @click="closeModal"
                            class="flex-shrink-0 p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Contenu scrollable -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                <!-- Collection selector si plusieurs collections -->
                <div v-if="collections.length > 1" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Collection de destination <span class="text-red-500">*</span>
                    </label>
                    <select v-model="selectedCollectionId"
                            :class="[
                                'w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:text-white text-base',
                                selectedCollectionId ? 'border-gray-300 dark:border-gray-600' : 'border-red-300 dark:border-red-600'
                            ]">
                        <option value="">Sélectionnez une collection</option>
                        <option v-for="collection in collections"
                                :key="collection.id"
                                :value="collection.id">
                            {{ collection.collection_nom }}
                        </option>
                    </select>
                    <p v-if="!selectedCollectionId" class="text-xs text-red-500 dark:text-red-400 mt-1">
                        Veuillez sélectionner une collection avant d'ajouter un vinyle.
                    </p>
                    <p v-else class="text-xs text-green-600 dark:text-green-400 mt-1">
                        Le vinyle sera ajouté à "{{ collections.find(c => c.id == selectedCollectionId)?.collection_nom }}"
                    </p>
                </div>

                <div v-if="showCollectionError" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm">
                    <strong>Erreur :</strong> Veuillez sélectionner une collection avant d'ajouter un vinyle.
                </div>

                <!-- Barre de recherche -->
                <div class="mb-4">
                    <div class="flex flex-col sm:flex-row gap-2 mb-3">
                        <input v-model="discogsQuery"
                               @keyup.enter="searchDiscogs"
                               type="text"
                               placeholder="Artiste, album, code [r123456]..."
                               class="flex-1 px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                        <button @click="searchDiscogs"
                                :disabled="isSearchingDiscogs || !discogsQuery.trim()"
                                class="w-full sm:w-auto px-6 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 font-medium transition-colors">
                            {{ isSearchingDiscogs ? 'Recherche...' : 'Rechercher' }}
                        </button>
                    </div>
                    <div class="text-center">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Introuvable sur Discogs ?</span>
                        <button @click="openManualModal"
                                type="button"
                                class="ml-2 px-3 py-1.5 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 transition-colors">
                            Ajouter manuellement
                        </button>
                    </div>
                </div>

                <!-- Résultats -->
                <div class="min-h-[200px]">
                    <div v-if="!hasSearched && !isSearchingDiscogs"
                         class="text-gray-500 dark:text-gray-400 text-center py-8 sm:py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <p class="text-sm sm:text-base">Recherchez votre vinyle sur Discogs</p>
                        <p class="mt-2 text-xs sm:text-sm">
                            Ou <button @click="openManualModal" class="text-purple-600 hover:text-purple-700 underline">ajoutez manuellement</button>
                        </p>
                    </div>

                    <div v-else-if="isSearchingDiscogs"
                         class="text-gray-500 dark:text-gray-400 text-center py-8 sm:py-12">
                        <svg class="animate-spin mx-auto h-8 w-8 text-purple-600 mb-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p>Recherche en cours...</p>
                    </div>

                    <div v-else-if="hasSearched && discogsResults.length === 0"
                         class="text-gray-500 dark:text-gray-400 text-center py-8 sm:py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>Aucun résultat pour "{{ discogsQuery }}"</p>
                        <button @click="openManualModal" class="mt-3 text-purple-600 hover:text-purple-700 underline text-sm">
                            Ajouter manuellement
                        </button>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="result in discogsResults" :key="result.id"
                             class="flex flex-col sm:flex-row sm:items-center p-3 sm:p-4 border dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">

                            <!-- Image et infos -->
                            <div class="flex items-start sm:items-center flex-1 min-w-0">
                                <img v-if="result.thumb"
                                     :src="result.thumb"
                                     :alt="result.title"
                                     class="w-14 h-14 sm:w-16 sm:h-16 object-cover rounded flex-shrink-0">
                                <div v-else class="w-14 h-14 sm:w-16 sm:h-16 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                        <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                    </svg>
                                </div>

                                <div class="ml-3 sm:ml-4 flex-1 min-w-0">
                                    <h4 class="font-medium text-gray-900 dark:text-white text-sm sm:text-base line-clamp-2">
                                        {{ result.title }}
                                    </h4>
                                    <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                                        {{ result.year }} {{ result.format?.join(', ') }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 truncate">
                                        {{ result.label?.join(', ') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Bouton Ajouter -->
                            <button @click="addVinylFromDiscogs(result)"
                                    class="mt-3 sm:mt-0 sm:ml-4 w-full sm:w-auto px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors flex-shrink-0">
                                Ajouter
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer fixe -->
            <div class="flex-shrink-0 p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <div class="flex justify-end">
                    <button @click="closeModal"
                            class="px-6 py-2.5 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 font-medium transition-colors">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
