<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

// Props reçues du contrôleur
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            collections_count: 0,
            vinyls_count: 0,
            total_value: 0,
            year_additions: 0
        })
    },
    recentCollections: {
        type: Array,
        default: () => []
    },
    recentVinyls: {
        type: Array,
        default: () => []
    },
    allCollections: {
        type: Array,
        default: () => []
    }
});

// États des modales
const showCollectionModal = ref(false);
const showSearchModal = ref(false);
const showVinylModal = ref(false);
const showManualVinylModal = ref(false);

// Données des formulaires
const newCollection = ref({
    collection_nom: '',
    collection_commentaires: ''
});

const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const searchTimer = ref(null);

// Méthode pour formater les dates
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

// Méthodes pour les modales
const openCollectionModal = () => {
    showCollectionModal.value = true;
};

const closeCollectionModal = () => {
    showCollectionModal.value = false;
    newCollection.value = { collection_nom: '', collection_commentaires: '' };
};

const openSearchModal = () => {
    showSearchModal.value = true;
};

const closeSearchModal = () => {
    showSearchModal.value = false;
    searchQuery.value = '';
    searchResults.value = [];
};

const openVinylModal = () => {
    showVinylModal.value = true;
};

const closeVinylModal = () => {
    showVinylModal.value = false;
    discogsQuery.value = '';
    discogsResults.value = [];
    selectedCollectionId.value = null;
};

// Données pour le vinyle manuel
const manualVinyl = ref({
    vinyl_nom: '',
    artiste: '',
    vinyl_titre: '',
    label: '',
    reference: '',
    annee: null,
    pochette: '',
    collection_id: null,
    prix_achat: null,
    annee_achat: null,
    provenance: 0,
    commentaires: '',
    note: null
});

const isSavingManualVinyl = ref(false);

const openManualVinylModal = () => {
    showManualVinylModal.value = true;
};

const closeManualVinylModal = () => {
    showManualVinylModal.value = false;
    manualVinyl.value = {
        vinyl_nom: '',
        artiste: '',
        vinyl_titre: '',
        label: '',
        reference: '',
        annee: null,
        pochette: '',
        collection_id: null,
        prix_achat: null,
        annee_achat: null,
        provenance: 0,
        commentaires: '',
        note: null
    };
};

const saveManualVinyl = async () => {
    if (!manualVinyl.value.vinyl_nom || !manualVinyl.value.artiste || !manualVinyl.value.collection_id) {
        alert('Veuillez remplir les champs obligatoires');
        return;
    }

    isSavingManualVinyl.value = true;

    try {
        const response = await fetch('/vinyls/manual', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(manualVinyl.value)
        });

        const data = await response.json();

        if (data.success) {
            closeManualVinylModal();
            router.reload();
        } else {
            alert('Erreur lors de l\'ajout du vinyle');
        }
    } catch (error) {
        console.error('Erreur lors de l\'ajout:', error);
        alert('Erreur lors de l\'ajout du vinyle');
    } finally {
        isSavingManualVinyl.value = false;
    }
};

// Méthodes pour les actions
const createCollection = () => {
    router.post('/collections', newCollection.value, {
        onSuccess: () => {
            closeCollectionModal();
            // Rafraîchir la page pour voir la nouvelle collection
            router.reload();
        },
        onError: (errors) => {
            console.error('Erreur lors de la création:', errors);
        }
    });
};

// Variables pour Discogs
const discogsQuery = ref('');
const discogsResults = ref([]);
const isSearchingDiscogs = ref(false);
const selectedCollectionId = ref(null);
const showCollectionError = ref(false);

const searchDiscogs = async () => {
    if (!discogsQuery.value.trim()) return;

    isSearchingDiscogs.value = true;

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
    // Validation côté client
    if (!selectedCollectionId.value && allCollections.length > 0) {
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
            closeVinylModal();
            router.reload();
        },
        onError: (errors) => {
            console.error('Erreur lors de l\'ajout:', errors);
        }
    });
};

// Fonction de recherche globale
const searchGlobal = async () => {
    if (!searchQuery.value.trim()) {
        searchResults.value = [];
        return;
    }

    // Clear previous timer
    if (searchTimer.value) {
        clearTimeout(searchTimer.value);
    }

    // Debounce search by 300ms
    searchTimer.value = setTimeout(async () => {
        isSearching.value = true;

        try {
            const response = await fetch(`/dashboard/search?q=${encodeURIComponent(searchQuery.value)}`);
            const data = await response.json();
            searchResults.value = data.results || [];
        } catch (error) {
            console.error('Erreur recherche:', error);
            searchResults.value = [];
        } finally {
            isSearching.value = false;
        }
    }, 300);
};
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Tableau de bord
                </h2>
                <div class="flex items-center gap-4">
                    <Link href="/collections"
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                        Mes Collections
                    </Link>
                    <Link href="/mes-vinyles"
                       class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors">
                        Mes Vinyles
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Collections</h3>
                                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ stats.collections_count }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                        <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Vinyles</h3>
                                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ stats.vinyls_count }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Mes Collections</h3>
                                <Link href="/collections" class="text-blue-600 hover:text-blue-800 text-sm">
                                    Voir tout
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <Link v-for="collection in recentCollections" :key="collection.id"
                                     :href="`/collections/${collection.id}`"
                                     class="block p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors cursor-pointer border-l-4 border-blue-500">
                                    <h4 class="font-medium text-gray-900 dark:text-white text-sm">{{ collection.collection_nom }}</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ collection.vinyls_count }} vinyles</p>
                                </Link>
                                <div v-if="recentCollections.length === 0" class="text-center py-8">
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">Aucune collection</p>
                                    <Link href="/collections/create" class="text-blue-600 hover:text-blue-800 text-sm mt-2 inline-block">
                                        Créer votre première collection
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="lg:col-span-2 space-y-6">
                        
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Vinyles Récents</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="vinyl in recentVinyls" :key="vinyl.id"
                                         class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                        <div class="w-12 h-12 rounded-lg mr-4 overflow-hidden flex-shrink-0">
                                            <img v-if="vinyl.pochette"
                                                 :src="vinyl.pochette"
                                                 :alt="vinyl.vinyl_nom"
                                                 class="w-full h-full object-cover"
                                                 @error="$event.target.style.display = 'none'; $event.target.nextElementSibling && ($event.target.nextElementSibling.style.display = 'flex')">
                                            <div v-else class="w-full h-full bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                                                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ vinyl.vinyl_nom }}</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ vinyl.artiste }}</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-1">{{ vinyl.collection_nom }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="recentVinyls.length === 0" class="text-center py-8">
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">Aucun vinyle ajouté récemment</p>
                                </div>
                            </div>
                        </div>

                        
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Actions rapides</h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <button @click="openCollectionModal"
                                       class="flex flex-col items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        <span class="text-xs font-medium text-blue-600 dark:text-blue-400">Nouvelle collection</span>
                                    </button>

                                    <button @click="openVinylModal"
                                       class="flex flex-col items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        <span class="text-xs font-medium text-purple-600 dark:text-purple-400">Ajouter un vinyle</span>
                                    </button>

                                    <button @click="openSearchModal"
                                       class="flex flex-col items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <span class="text-xs font-medium text-green-600 dark:text-green-400">Parcourir</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div v-if="showCollectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
            <div class="relative p-6 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Créer une nouvelle collection</h3>
                    <form @submit.prevent="createCollection">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nom de la collection
                            </label>
                            <input v-model="newCollection.collection_nom"
                                   type="text"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Description (optionnel)
                            </label>
                            <textarea v-model="newCollection.collection_commentaires"
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button @click="closeCollectionModal"
                                    type="button"
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Annuler
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Créer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
        <div v-if="showSearchModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
            <div class="relative p-6 border w-4/5 max-w-4xl shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Rechercher dans toutes vos collections</h3>
                    <div class="mb-4">
                        <input v-model="searchQuery"
                               @input="searchGlobal"
                               type="text"
                               placeholder="Rechercher un vinyle, artiste, label, année..."
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="mb-6 max-h-96 overflow-y-auto">
                        <div v-if="!searchQuery && searchResults.length === 0" class="text-gray-500 dark:text-gray-400 text-center py-8">
                            Tapez pour rechercher dans toutes vos collections...
                        </div>
                        <div v-else-if="isSearching" class="text-gray-500 dark:text-gray-400 text-center py-8">
                            Recherche en cours...
                        </div>
                        <div v-else-if="searchQuery && searchResults.length === 0" class="text-gray-500 dark:text-gray-400 text-center py-8">
                            Aucun résultat trouvé pour "{{ searchQuery }}"
                        </div>
                        <div v-else class="space-y-3">
                            <Link v-for="result in searchResults" 
                                 :key="result.id"
                                 :href="`/collections/${result.collection_id}`"
                                 class="flex items-center p-4 border dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                <div class="w-16 h-16 rounded mr-4 overflow-hidden flex-shrink-0">
                                    <img v-if="result.pochette"
                                         :src="result.pochette"
                                         :alt="result.vinyl_nom"
                                         class="w-full h-full object-cover"
                                         @error="$event.target.style.display = 'none'; $event.target.nextElementSibling && ($event.target.nextElementSibling.style.display = 'flex')">
                                    <div v-else class="w-full h-full bg-purple-100 dark:bg-purple-900 rounded flex items-center justify-center">
                                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ result.vinyl_nom || result.vinyl_titre }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ result.artiste }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span v-if="result.annee" class="text-xs text-gray-500 dark:text-gray-500">{{ result.annee }}</span>
                                        <span v-if="result.annee && result.label" class="text-xs text-gray-400">•</span>
                                        <span v-if="result.label" class="text-xs text-gray-500 dark:text-gray-500">{{ result.label }}</span>
                                    </div>
                                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                        Collection: {{ result.collection_nom }}
                                    </p>
                                </div>
                                <div v-if="result.note" class="ml-4">
                                    <div class="flex items-center">
                                        <svg v-for="i in 5" :key="i" 
                                             class="w-4 h-4" 
                                             :class="i <= result.note ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'"
                                             fill="currentColor" 
                                             viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button @click="closeSearchModal"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        
        <div v-if="showVinylModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
            <div class="relative p-6 border w-4/5 max-w-4xl shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Ajouter un vinyle depuis Discogs</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Recherchez par nom d'artiste, album ou utilisez un code Discogs : 
                        <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">[r123456]</code> pour un release ou 
                        <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">[m123456]</code> pour un master
                    </p>
                    
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Collection de destination <span class="text-red-500">*</span>
                        </label>
                        <select v-model="selectedCollectionId" 
                                :class="[
                                    'w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white',
                                    selectedCollectionId ? 'border-gray-300 dark:border-gray-600' : 'border-red-300 dark:border-red-600'
                                ]">
                            <option value="">Sélectionnez une collection</option>
                            <option v-for="collection in allCollections" 
                                    :key="collection.id" 
                                    :value="collection.id">
                                {{ collection.collection_nom }}
                            </option>
                        </select>
                        <p v-if="!selectedCollectionId" class="text-xs text-red-500 dark:text-red-400 mt-1">
                            ⚠️ Veuillez sélectionner une collection avant d'ajouter un vinyle.
                        </p>
                        <p v-else class="text-xs text-green-600 dark:text-green-400 mt-1">
                            ✓ Le vinyle sera ajouté à "{{ allCollections.find(c => c.id == selectedCollectionId)?.collection_nom }}"
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
                            <button @click="() => { closeVinylModal(); openManualVinylModal(); }"
                                    type="button"
                                    class="ml-2 px-3 py-1 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700 transition-colors">
                                Ajouter manuellement
                            </button>
                        </div>
                    </div>
                    <div class="mb-6 max-h-96 overflow-y-auto">
                        <div v-if="discogsResults.length === 0 && !isSearchingDiscogs && !discogsQuery.trim()"
                             class="text-gray-500 dark:text-gray-400 text-center py-8">
                            <p>Recherchez votre vinyle sur Discogs pour l'ajouter à votre collection...</p>
                            <p class="mt-2 text-sm">Ou <button @click="() => { closeVinylModal(); openManualVinylModal(); }" class="text-purple-600 hover:text-purple-700 underline">ajoutez un vinyle manuellement</button> s'il n'est pas sur Discogs</p>
                        </div>
                        <div v-else-if="isSearchingDiscogs"
                             class="text-gray-500 dark:text-gray-400 text-center py-8">
                            Recherche en cours...
                        </div>
                        <div v-else-if="discogsResults.length === 0"
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
                        <button @click="closeVinylModal"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modale ajout vinyle manuel -->
        <div v-if="showManualVinylModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
            <div class="relative p-6 border w-4/5 max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Ajouter un vinyle manuellement</h3>
                    <form @submit.prevent="saveManualVinyl">
                        <!-- Collection -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Collection <span class="text-red-500">*</span>
                            </label>
                            <select v-model="manualVinyl.collection_id" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                <option value="">Sélectionnez une collection</option>
                                <option v-for="collection in props.allCollections" 
                                        :key="collection.id" 
                                        :value="collection.id">
                                    {{ collection.collection_nom }}
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Nom du vinyle -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nom du vinyle <span class="text-red-500">*</span>
                                </label>
                                <input v-model="manualVinyl.vinyl_nom"
                                       type="text"
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            </div>

                            <!-- Artiste -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Artiste <span class="text-red-500">*</span>
                                </label>
                                <input v-model="manualVinyl.artiste"
                                       type="text"
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            </div>

                            <!-- Titre -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Titre de l'album
                                </label>
                                <input v-model="manualVinyl.vinyl_titre"
                                       type="text"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            </div>

                            <!-- Label -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Label
                                </label>
                                <input v-model="manualVinyl.label"
                                       type="text"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            </div>

                            <!-- Référence -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Référence
                                </label>
                                <input v-model="manualVinyl.reference"
                                       type="text"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            </div>

                            <!-- Année -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Année de sortie
                                </label>
                                <input v-model="manualVinyl.annee"
                                       type="number"
                                       min="1900"
                                       :max="new Date().getFullYear()"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            </div>

                            <!-- URL de la pochette -->
                            <div class="mb-4 md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    URL de la pochette
                                </label>
                                <input v-model="manualVinyl.pochette"
                                       type="url"
                                       placeholder="https://..."
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <div class="border-t pt-4 mt-4">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Informations de collection</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Prix d'achat -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Prix d'achat (€)
                                    </label>
                                    <input v-model="manualVinyl.prix_achat"
                                           type="number"
                                           step="0.01"
                                           min="0"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>

                                <!-- Année d'achat -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Année d'achat
                                    </label>
                                    <input v-model="manualVinyl.annee_achat"
                                           type="number"
                                           min="1900"
                                           :max="new Date().getFullYear()"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>

                                <!-- Note -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Note (/10)
                                    </label>
                                    <input v-model="manualVinyl.note"
                                           type="number"
                                           min="1"
                                           max="10"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>

                            <!-- Commentaires -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Commentaires
                                </label>
                                <textarea v-model="manualVinyl.commentaires"
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button @click="closeManualVinylModal"
                                    type="button"
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Annuler
                            </button>
                            <button type="submit"
                                    :disabled="isSavingManualVinyl"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50">
                                {{ isSavingManualVinyl ? 'Ajout en cours...' : 'Ajouter le vinyle' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
