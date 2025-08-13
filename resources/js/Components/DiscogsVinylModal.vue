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
    <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative p-6 border w-4/5 max-w-4xl shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Ajouter un vinyle depuis Discogs</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Recherchez par nom d'artiste, album ou utilisez un code Discogs : 
                    <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">[r123456]</code> pour un release ou 
                    <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">[m123456]</code> pour un master
                </p>
                
                <!-- Collection selector si plusieurs collections -->
                <div v-if="collections.length > 1" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Collection de destination <span class="text-red-500">*</span>
                    </label>
                    <select v-model="selectedCollectionId" 
                            :class="[
                                'w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white',
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
                        ⚠️ Veuillez sélectionner une collection avant d'ajouter un vinyle.
                    </p>
                    <p v-else class="text-xs text-green-600 dark:text-green-400 mt-1">
                        ✓ Le vinyle sera ajouté à "{{ collections.find(c => c.id == selectedCollectionId)?.collection_nom }}"
                    </p>
                </div>
                
                <div v-if="showCollectionError" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <strong>Erreur :</strong> Veuillez sélectionner une collection avant d'ajouter un vinyle.
                </div>
                
                <div class="mb-4">
                    <div class="flex gap-2 mb-3">
                        <input v-model="discogsQuery"
                               @keyup.enter="searchDiscogs"
                               type="text"
                               placeholder="Rechercher un vinyle sur Discogs (artiste, album, code [r123456] ou [m123456]...)"
                               class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                        <button @click="searchDiscogs"
                                :disabled="isSearchingDiscogs || !discogsQuery.trim()"
                                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50">
                            {{ isSearchingDiscogs ? 'Recherche...' : 'Rechercher' }}
                        </button>
                    </div>
                    <div class="text-center">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Vinyle introuvable sur Discogs ?</span>
                        <button @click="openManualModal"
                                type="button"
                                class="ml-2 px-3 py-1 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700 transition-colors">
                            Ajouter manuellement
                        </button>
                    </div>
                </div>
                <div class="mb-6 max-h-96 overflow-y-auto">
                    <div v-if="!hasSearched && !isSearchingDiscogs"
                         class="text-gray-500 dark:text-gray-400 text-center py-8">
                        <p>Recherchez votre vinyle sur Discogs pour l'ajouter à votre collection...</p>
                        <p class="mt-2 text-sm">Ou <button @click="openManualModal" class="text-purple-600 hover:text-purple-700 underline">ajoutez un vinyle manuellement</button> s'il n'est pas sur Discogs</p>
                    </div>
                    <div v-else-if="isSearchingDiscogs"
                         class="text-gray-500 dark:text-gray-400 text-center py-8">
                        Recherche en cours...
                    </div>
                    <div v-else-if="hasSearched && discogsResults.length === 0"
                         class="text-gray-500 dark:text-gray-400 text-center py-8">
                        Aucun résultat trouvé pour "{{ discogsQuery }}"
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="result in discogsResults" :key="result.id"
                             class="flex items-center p-4 border dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                            <img v-if="result.thumb"
                                 :src="result.thumb"
                                 :alt="result.title"
                                 class="w-16 h-16 object-cover rounded mr-4">
                            <div v-else class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded mr-4 flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ result.title }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ result.year }} • {{ result.format?.join(', ') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500">{{ result.label?.join(', ') }}</p>
                            </div>
                            <button @click="addVinylFromDiscogs(result)"
                                    class="ml-4 px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                Ajouter
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button @click="closeModal"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>