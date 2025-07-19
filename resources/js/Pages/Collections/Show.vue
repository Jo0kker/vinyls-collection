<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import VinylImage from '@/Components/VinylImage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    collection: {
        type: Object,
        required: true
    },
    userCollections: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            sort: 'date_ajout',
            order: 'desc'
        })
    }
});

// États de la modal Discogs
const showVinylModal = ref(false);
const discogsQuery = ref('');
const discogsResults = ref([]);
const isSearchingDiscogs = ref(false);

// États pour les actions vinyles
const showDeleteModal = ref(false);
const showMoveModal = ref(false);
const selectedVinyl = ref(null);
const targetCollectionId = ref('');

// États des filtres
const searchQuery = ref(props.filters.search || '');
const sortBy = ref(props.filters.sort || 'date_ajout');
const sortOrder = ref(props.filters.order || 'desc');

// Mode d'affichage
const viewMode = ref('grid'); // 'grid', 'list', 'compact'

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

// Méthodes pour la modal
const openVinylModal = () => {
    showVinylModal.value = true;
};

const closeVinylModal = () => {
    showVinylModal.value = false;
    discogsQuery.value = '';
    discogsResults.value = [];
};

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
    router.post('/vinyls/from-discogs', {
        discogs_id: discogsItem.id,
        discogs_data: discogsItem,
        collection_id: props.collection.id // Pré-rempli avec cette collection
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

// Fonctions de recherche et tri
const applyFilters = () => {
    const params = new URLSearchParams();
    
    if (searchQuery.value) {
        params.append('search', searchQuery.value);
    }
    if (sortBy.value !== 'date_ajout') {
        params.append('sort', sortBy.value);
    }
    if (sortOrder.value !== 'desc') {
        params.append('order', sortOrder.value);
    }
    
    const queryString = params.toString();
    const url = `/collections/${props.collection.id}${queryString ? '?' + queryString : ''}`;
    
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true
    });
};

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters();
};

const toggleSortOrder = () => {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    applyFilters();
};

// Actions vinyles
const openDeleteModal = (vinyl) => {
    selectedVinyl.value = vinyl;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedVinyl.value = null;
};

const confirmDelete = () => {
    if (selectedVinyl.value) {
        router.delete(`/collections/${props.collection.id}/vinyl/${selectedVinyl.value.id}`, {
            onSuccess: () => {
                closeDeleteModal();
                router.reload();
            },
            onError: (errors) => {
                console.error('Erreur lors de la suppression:', errors);
            }
        });
    }
};

const openMoveModal = (vinyl) => {
    selectedVinyl.value = vinyl;
    targetCollectionId.value = '';
    showMoveModal.value = true;
};

const closeMoveModal = () => {
    showMoveModal.value = false;
    selectedVinyl.value = null;
    targetCollectionId.value = '';
};

const confirmMove = () => {
    if (selectedVinyl.value && targetCollectionId.value) {
        router.patch(`/collections/${props.collection.id}/vinyl/${selectedVinyl.value.id}/move`, {
            target_collection_id: targetCollectionId.value
        }, {
            onSuccess: () => {
                closeMoveModal();
                router.reload();
            },
            onError: (errors) => {
                console.error('Erreur lors du déplacement:', errors);
            }
        });
    }
};
</script>

<template>
    <Head :title="collection.collection_nom" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link href="/collections" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                        ← Retour aux collections
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        {{ collection.collection_nom }}
                    </h2>
                </div>
                <div class="flex items-center gap-4">
                    <Link :href="`/collections/${collection.id}/edit`"
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                        Modifier
                    </Link>
                    <button @click="openVinylModal"
                       class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors">
                        Ajouter un vinyle (Discogs)
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Informations de la collection -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre de vinyles</h3>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ collection.collection_vinyls?.length || 0 }}
                                </p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Créée le</h3>
                                <p class="text-lg text-gray-900 dark:text-white">
                                    {{ formatDate(collection.collection_date_crea) }}
                                </p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Modifiée le</h3>
                                <p class="text-lg text-gray-900 dark:text-white">
                                    {{ formatDate(collection.collection_date_modif) }}
                                </p>
                            </div>
                        </div>
                        
                        <div v-if="collection.collection_commentaires" class="mt-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Description</h3>
                            <p class="text-gray-900 dark:text-white">{{ collection.collection_commentaires }}</p>
                        </div>
                    </div>
                </div>

                <!-- Recherche et tri -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                            <!-- Barre de recherche -->
                            <div class="flex-1 max-w-md">
                                <div class="relative">
                                    <input 
                                        v-model="searchQuery"
                                        @keyup.enter="applyFilters"
                                        type="text" 
                                        placeholder="Rechercher un vinyle..."
                                        class="w-full pl-10 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    >
                                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <button v-if="searchQuery" 
                                            @click="clearSearch"
                                            class="absolute right-3 top-2.5 h-5 w-5 text-gray-400 hover:text-gray-600">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Mode d'affichage -->
                            <div class="flex items-center gap-2 border border-gray-300 dark:border-gray-600 rounded-md p-1">
                                <button @click="viewMode = 'grid'"
                                        :class="[
                                            'p-2 rounded transition-colors',
                                            viewMode === 'grid' 
                                                ? 'bg-purple-600 text-white' 
                                                : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        ]"
                                        title="Affichage en grille">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                    </svg>
                                </button>
                                <button @click="viewMode = 'list'"
                                        :class="[
                                            'p-2 rounded transition-colors',
                                            viewMode === 'list' 
                                                ? 'bg-purple-600 text-white' 
                                                : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        ]"
                                        title="Affichage en liste">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    </svg>
                                </button>
                                <button @click="viewMode = 'compact'"
                                        :class="[
                                            'p-2 rounded transition-colors',
                                            viewMode === 'compact' 
                                                ? 'bg-purple-600 text-white' 
                                                : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        ]"
                                        title="Affichage compact">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Options de tri -->
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Trier par :</span>
                                <select v-model="sortBy" 
                                        @change="applyFilters"
                                        class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                                    <option value="date_ajout">Date d'ajout</option>
                                    <option value="nom">Nom de l'album</option>
                                    <option value="artiste">Artiste</option>
                                </select>
                                
                                <button @click="toggleSortOrder" 
                                        class="p-2 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-purple-500"
                                        :title="sortOrder === 'asc' ? 'Trier par ordre décroissant' : 'Trier par ordre croissant'">
                                    <svg v-if="sortOrder === 'asc'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path>
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Indicateur de filtre actif -->
                        <div v-if="searchQuery || sortBy !== 'date_ajout' || sortOrder !== 'desc'" class="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <span>Filtres actifs :</span>
                            <span v-if="searchQuery" class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                Recherche: "{{ searchQuery }}"
                                <button @click="clearSearch" class="ml-1 hover:text-purple-600">×</button>
                            </span>
                            <span v-if="sortBy !== 'date_ajout'" class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                Tri: {{ sortBy === 'nom' ? 'Nom' : sortBy === 'artiste' ? 'Artiste' : 'Date' }}
                            </span>
                            <span v-if="sortOrder !== 'desc'" class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Ordre: {{ sortOrder === 'asc' ? 'Croissant' : 'Décroissant' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Liste des vinyles -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Vinyles de la collection
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                                    ({{ collection.collection_vinyls?.length || 0 }} vinyle{{ (collection.collection_vinyls?.length || 0) > 1 ? 's' : '' }})
                                </span>
                            </h3>
                        </div>
                        
                        <div v-if="!collection.collection_vinyls || collection.collection_vinyls.length === 0" 
                             class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun vinyle</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Cette collection ne contient pas encore de vinyles.
                            </p>
                            <div class="mt-6">
                                <button @click="openVinylModal"
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                                    Ajouter le premier vinyle (Discogs)
                                </button>
                            </div>
                        </div>

                        <!-- Affichage grille -->
                        <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="collectionVinyl in collection.collection_vinyls" :key="collectionVinyl.id"
                                 class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors relative group">
                                <div class="flex items-start space-x-4">
                                    <VinylImage 
                                        :src="collectionVinyl.vinyl?.pochette"
                                        :alt="collectionVinyl.vinyl?.vinyl_nom || 'Pochette de vinyle'"
                                        size="md"
                                    />
                                    
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-gray-900 dark:text-white text-sm">
                                            {{ collectionVinyl.vinyl?.vinyl_nom || 'Nom inconnu' }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ collectionVinyl.vinyl?.artiste || 'Artiste inconnu' }}
                                        </p>
                                        <div class="flex items-center mt-2 text-xs text-gray-400">
                                            <span>
                                                Ajouté le {{ formatDate(collectionVinyl.date_ajout) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Boutons d'actions -->
                                    <div class="flex flex-col gap-1">
                                        <button @click="openMoveModal(collectionVinyl)"
                                                class="p-1.5 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900 rounded-md transition-colors"
                                                title="Déplacer vers une autre collection">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                            </svg>
                                        </button>
                                        <button @click="openDeleteModal(collectionVinyl)"
                                                class="p-1.5 text-red-600 hover:bg-red-100 dark:hover:bg-red-900 rounded-md transition-colors"
                                                title="Supprimer de la collection">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div v-if="collectionVinyl.commentaires" class="mt-3 text-xs text-gray-600 dark:text-gray-300">
                                    {{ collectionVinyl.commentaires }}
                                </div>
                            </div>
                        </div>

                        <!-- Affichage liste -->
                        <div v-else-if="viewMode === 'list'" class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Pochette</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Titre</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Artiste</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Année</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Label</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Ajouté le</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="collectionVinyl in collection.collection_vinyls" :key="collectionVinyl.id"
                                        class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="py-3 px-4">
                                            <VinylImage 
                                                :src="collectionVinyl.vinyl?.pochette"
                                                :alt="collectionVinyl.vinyl?.vinyl_nom || 'Pochette de vinyle'"
                                                size="sm"
                                            />
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ collectionVinyl.vinyl?.vinyl_nom || 'Nom inconnu' }}
                                            </div>
                                            <div v-if="collectionVinyl.commentaires" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ collectionVinyl.commentaires }}
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                                            {{ collectionVinyl.vinyl?.artiste || 'Artiste inconnu' }}
                                        </td>
                                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                                            {{ collectionVinyl.vinyl?.annee || '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                                            {{ collectionVinyl.vinyl?.label || '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-gray-500 dark:text-gray-400 text-xs">
                                            {{ formatDate(collectionVinyl.date_ajout) }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-1">
                                                <button @click="openMoveModal(collectionVinyl)"
                                                        class="p-1.5 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900 rounded-md transition-colors"
                                                        title="Déplacer vers une autre collection">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                    </svg>
                                                </button>
                                                <button @click="openDeleteModal(collectionVinyl)"
                                                        class="p-1.5 text-red-600 hover:bg-red-100 dark:hover:bg-red-900 rounded-md transition-colors"
                                                        title="Supprimer de la collection">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Affichage compact - Cards avec image overlay -->
                        <div v-else-if="viewMode === 'compact'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                            <div v-for="collectionVinyl in collection.collection_vinyls" :key="collectionVinyl.id"
                                 class="relative aspect-square rounded-lg overflow-hidden group cursor-pointer hover:scale-105 transition-transform shadow-md">
                                <!-- Image de fond -->
                                <div class="absolute inset-0 bg-gray-200 dark:bg-gray-700">
                                    <img v-if="collectionVinyl.vinyl?.pochette"
                                         :src="collectionVinyl.vinyl.pochette"
                                         :alt="collectionVinyl.vinyl?.vinyl_nom || 'Pochette de vinyle'"
                                         class="w-full h-full object-cover"
                                         @error="$event.target.style.display = 'none'; $event.target.nextElementSibling.style.display = 'flex'"
                                    />
                                    <!-- Fallback si pas d'image ou erreur de chargement -->
                                    <div :style="{ display: collectionVinyl.vinyl?.pochette ? 'none' : 'flex' }" 
                                         class="w-full h-full items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                        </svg>
                                    </div>
                                </div>
                                
                                <!-- Overlay gradient -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                                
                                <!-- Texte overlay -->
                                <div class="absolute bottom-0 left-0 right-0 p-3 text-white">
                                    <h4 class="font-medium text-sm leading-tight mb-1 line-clamp-2">
                                        {{ collectionVinyl.vinyl?.vinyl_nom || 'Nom inconnu' }}
                                    </h4>
                                    <p class="text-xs text-gray-200 truncate">
                                        {{ collectionVinyl.vinyl?.artiste || 'Artiste inconnu' }}
                                    </p>
                                </div>
                                
                                <!-- Actions overlay -->
                                <div class="absolute top-2 right-2 flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click.stop="openMoveModal(collectionVinyl)"
                                            class="p-1.5 bg-blue-600/80 hover:bg-blue-600 text-white rounded-full backdrop-blur-sm transition-colors"
                                            title="Déplacer vers une autre collection">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                        </svg>
                                    </button>
                                    <button @click.stop="openDeleteModal(collectionVinyl)"
                                            class="p-1.5 bg-red-600/80 hover:bg-red-600 text-white rounded-full backdrop-blur-sm transition-colors"
                                            title="Supprimer de la collection">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ajouter Vinyle depuis Discogs -->
        <div v-if="showVinylModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
            <div class="relative p-6 border w-4/5 max-w-4xl shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        Ajouter un vinyle à "{{ collection.collection_nom }}"
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Recherchez par nom d'artiste, album ou utilisez un code Discogs : 
                        <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">[r123456]</code> pour un release ou 
                        <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">[m123456]</code> pour un master
                    </p>
                    
                    <div class="mb-4 flex gap-2">
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
                    
                    <div class="mb-6 max-h-96 overflow-y-auto">
                        <div v-if="discogsResults.length === 0 && !isSearchingDiscogs && !discogsQuery.trim()" 
                             class="text-gray-500 dark:text-gray-400 text-center py-8">
                            Recherchez votre vinyle sur Discogs pour l'ajouter à cette collection...
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
                                <div class="mr-4">
                                    <VinylImage 
                                        :src="result.thumb"
                                        :alt="result.title"
                                        size="md"
                                    />
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

        <!-- Modal de confirmation de suppression -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
            <div class="relative p-6 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3 text-center">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        Confirmer la suppression
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Êtes-vous sûr de vouloir supprimer 
                        <strong>{{ selectedVinyl?.vinyl?.vinyl_nom }}</strong> 
                        de cette collection ?
                    </p>
                    <div class="flex justify-center gap-3">
                        <button @click="closeDeleteModal"
                                class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                            Annuler
                        </button>
                        <button @click="confirmDelete"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de déplacement -->
        <div v-if="showMoveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
            <div class="relative p-6 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        Déplacer le vinyle
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Déplacer <strong>{{ selectedVinyl?.vinyl?.vinyl_nom }}</strong> vers :
                    </p>
                    
                    <select v-model="targetCollectionId" 
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 mb-4 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                        <option value="">Choisir une collection...</option>
                        <option v-for="userCollection in userCollections" 
                                :key="userCollection.id" 
                                :value="userCollection.id">
                            {{ userCollection.collection_nom }}
                        </option>
                    </select>
                    
                    <div class="flex justify-center gap-3">
                        <button @click="closeMoveModal"
                                class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                            Annuler
                        </button>
                        <button @click="confirmMove"
                                :disabled="!targetCollectionId"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Déplacer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>