<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import VinylImage from '@/Components/VinylImage.vue';
import DiscogsVinylModal from '@/Components/DiscogsVinylModal.vue';
import ManualVinylModal from '@/Components/ManualVinylModal.vue';
import EditVinylModal from '@/Components/EditVinylModal.vue';
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
            order: 'desc',
            per_page: 20
        })
    },
    pagination: {
        type: Object,
        default: () => null
    }
});

// États des modals
const showVinylModal = ref(false);
const showManualVinylModal = ref(false);
const showEditVinylModal = ref(false);

// États pour les actions vinyles
const showDeleteModal = ref(false);
const showMoveModal = ref(false);
const selectedVinyl = ref(null);
const vinylToEdit = ref(null);
const targetCollectionId = ref('');

// États des filtres
const searchQuery = ref(props.filters.search || '');
const sortBy = ref(props.filters.sort || 'date_ajout');
const sortOrder = ref(props.filters.order || 'desc');
const perPage = ref(props.filters.per_page || 20);

// Mode d'affichage avec cache localStorage
const getStoredViewMode = () => {
    try {
        return localStorage.getItem('vinyl-view-mode') || 'grid';
    } catch {
        return 'grid';
    }
};

const viewMode = ref(getStoredViewMode());

// Sauvegarder le mode d'affichage dans le cache
const setViewMode = (mode) => {
    viewMode.value = mode;
    try {
        localStorage.setItem('vinyl-view-mode', mode);
    } catch (error) {
        console.warn('Impossible de sauvegarder les préférences d\'affichage:', error);
    }
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

// Méthodes pour les modals
const openVinylModal = () => {
    showVinylModal.value = true;
};

const openManualVinylModal = () => {
    showVinylModal.value = false;
    showManualVinylModal.value = true;
};

// Fonctions de recherche et tri
const applyFilters = (page = 1) => {
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
    if (perPage.value != 20) {
        params.append('per_page', perPage.value);
    }
    if (page > 1) {
        params.append('page', page);
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
const openEditModal = (collectionVinyl) => {
    vinylToEdit.value = collectionVinyl;
    showEditVinylModal.value = true;
};

// Cette fonction n'est plus nécessaire car on peut toujours éditer l'exemplaire
// On pourrait éventuellement afficher un toast informatif sur les permissions limitées

const closeEditModal = () => {
    showEditVinylModal.value = false;
    vinylToEdit.value = null;
};

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

<style scoped>
@keyframes slide-up {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@keyframes slide-down {
    from { transform: translateY(0); opacity: 1; }
    to { transform: translateY(100%); opacity: 0; }
}

.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}

.animate-slide-down {
    animation: slide-down 0.3s ease-in;
}
</style>

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
                    <div class="flex items-center gap-2">
                        <button @click="openVinylModal"
                               class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors">
                            Ajouter un vinyle
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre de vinyles</h3>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ pagination?.total || collection.collection_vinyls?.length || 0 }}
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


                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">

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


                            <div class="flex items-center gap-2 border border-gray-300 dark:border-gray-600 rounded-md p-1">
                                <button @click="setViewMode('grid')"
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
                                <button @click="setViewMode('list')"
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
                                <button @click="setViewMode('compact')"
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
                                
                                <!-- Sélecteur d'items par page -->
                                <div class="flex items-center gap-2 ml-4 border-l pl-4">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Afficher :</span>
                                    <select v-model="perPage"
                                            @change="applyFilters(1)"
                                            class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                                        <option :value="20">20</option>
                                        <option :value="50">50</option>
                                        <option :value="100">100</option>
                                        <option :value="1000">1000</option>
                                    </select>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">par page</span>
                                </div>
                            </div>
                        </div>


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


                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Vinyles de la collection
                                <span v-if="pagination" class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                                    (Affichage {{ pagination.from || 0 }} à {{ pagination.to || 0 }} sur {{ pagination.total }} vinyle{{ pagination.total > 1 ? 's' : '' }})
                                </span>
                                <span v-else class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
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
                            <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                                <button @click="openVinylModal"
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                                    Ajouter depuis Discogs
                                </button>
                                <button @click="openManualVinylModal"
                                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-600 dark:text-white dark:border-gray-600 dark:hover:bg-gray-500">
                                    Ajouter manuellement
                                </button>
                            </div>
                        </div>


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


                                    <div class="flex flex-col gap-1">
                                        <Link :href="route('vinyl.show', collectionVinyl.vinyl.id)"
                                              class="p-1.5 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900 rounded-md transition-colors"
                                              title="Voir les détails">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </Link>
                                        <button @click="openEditModal(collectionVinyl)"
                                                :class="[
                                                    'p-1.5 rounded-md transition-colors',
                                                    collectionVinyl.can_edit_vinyl 
                                                        ? 'text-green-600 hover:bg-green-100 dark:hover:bg-green-900'
                                                        : 'text-yellow-600 hover:bg-yellow-100 dark:hover:bg-yellow-900'
                                                ]"
                                                :title="collectionVinyl.can_edit_vinyl 
                                                    ? 'Éditer le vinyle et votre exemplaire'
                                                    : 'Éditer uniquement votre exemplaire'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
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
                                                <Link :href="route('vinyl.show', collectionVinyl.vinyl.id)"
                                                      class="p-1.5 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900 rounded-md transition-colors"
                                                      title="Voir les détails">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </Link>
                                                <button @click="openEditModal(collectionVinyl)"
                                                        :class="[
                                                            'p-1.5 rounded-md transition-colors',
                                                            collectionVinyl.can_edit_vinyl 
                                                                ? 'text-green-600 hover:bg-green-100 dark:hover:bg-green-900'
                                                                : 'text-yellow-600 hover:bg-yellow-100 dark:hover:bg-yellow-900'
                                                        ]"
                                                        :title="collectionVinyl.can_edit_vinyl 
                                                            ? 'Éditer le vinyle et votre exemplaire'
                                                            : 'Éditer uniquement votre exemplaire'">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                    </svg>
                                                </button>
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


                        <div v-else-if="viewMode === 'compact'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                            <div v-for="collectionVinyl in collection.collection_vinyls" :key="collectionVinyl.id"
                                 class="relative aspect-square rounded-lg overflow-hidden group hover:scale-105 transition-transform shadow-md">

                                <Link :href="route('vinyl.show', collectionVinyl.vinyl.id)"
                                      class="absolute inset-0 z-0 cursor-pointer">
                                    <div class="absolute inset-0 bg-gray-200 dark:bg-gray-700">
                                        <img v-if="collectionVinyl.vinyl?.pochette"
                                             :src="collectionVinyl.vinyl.pochette"
                                             :alt="collectionVinyl.vinyl?.vinyl_nom || 'Pochette de vinyle'"
                                             class="w-full h-full object-cover"
                                             @error="$event.target.style.display = 'none'; $event.target.nextElementSibling.style.display = 'flex'"
                                        />

                                        <div :style="{ display: collectionVinyl.vinyl?.pochette ? 'none' : 'flex' }"
                                             class="w-full h-full items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

                                    <div class="absolute bottom-0 left-0 right-0 p-3 text-white">
                                        <h4 class="font-medium text-sm leading-tight mb-1 line-clamp-2">
                                            {{ collectionVinyl.vinyl?.vinyl_nom || 'Nom inconnu' }}
                                        </h4>
                                        <p class="text-xs text-gray-200 truncate">
                                            {{ collectionVinyl.vinyl?.artiste || 'Artiste inconnu' }}
                                        </p>
                                    </div>
                                </Link>

                                <div class="absolute top-2 right-2 flex flex-col gap-1 z-10">
                                    <button @click="openEditModal(collectionVinyl)"
                                            :class="[
                                                'p-1.5 text-white rounded-full backdrop-blur-sm transition-all',
                                                collectionVinyl.can_edit_vinyl 
                                                    ? 'bg-green-600/80 hover:bg-green-600'
                                                    : 'bg-yellow-600/80 hover:bg-yellow-600'
                                            ]"
                                            :title="collectionVinyl.can_edit_vinyl 
                                                ? 'Éditer le vinyle et votre exemplaire'
                                                : 'Éditer uniquement votre exemplaire'">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button @click="openMoveModal(collectionVinyl)"
                                            class="p-1.5 bg-blue-600/80 hover:bg-blue-600 text-white rounded-full backdrop-blur-sm transition-all"
                                            title="Déplacer vers une autre collection">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                        </svg>
                                    </button>
                                    <button @click="openDeleteModal(collectionVinyl)"
                                            class="p-1.5 bg-red-600/80 hover:bg-red-600 text-white rounded-full backdrop-blur-sm transition-all"
                                            title="Supprimer de la collection">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contrôles de pagination -->
                    <div v-if="pagination && pagination.last_page > 1" class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            Affichage de <span class="font-medium">{{ pagination.from }}</span> à <span class="font-medium">{{ pagination.to }}</span> sur <span class="font-medium">{{ pagination.total }}</span> résultats
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <!-- Bouton page précédente -->
                            <button @click="applyFilters(pagination.current_page - 1)"
                                    :disabled="pagination.current_page === 1"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                Précédent
                            </button>
                            
                            <!-- Numéros de pages -->
                            <div class="flex items-center gap-1">
                                <!-- Première page -->
                                <button v-if="pagination.current_page > 3"
                                        @click="applyFilters(1)"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    1
                                </button>
                                <span v-if="pagination.current_page > 4" class="px-2 text-gray-500">...</span>
                                
                                <!-- Pages autour de la page courante -->
                                <template v-for="page in [pagination.current_page - 2, pagination.current_page - 1, pagination.current_page, pagination.current_page + 1, pagination.current_page + 2]" :key="page">
                                    <button v-if="page > 0 && page <= pagination.last_page"
                                            @click="applyFilters(page)"
                                            :class="[
                                                'px-3 py-2 border rounded-md text-sm font-medium',
                                                page === pagination.current_page
                                                    ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400'
                                                    : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'
                                            ]">
                                        {{ page }}
                                    </button>
                                </template>
                                
                                <!-- Dernière page -->
                                <span v-if="pagination.current_page < pagination.last_page - 3" class="px-2 text-gray-500">...</span>
                                <button v-if="pagination.current_page < pagination.last_page - 2"
                                        @click="applyFilters(pagination.last_page)"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    {{ pagination.last_page }}
                                </button>
                            </div>
                            
                            <!-- Bouton page suivante -->
                            <button @click="applyFilters(pagination.current_page + 1)"
                                    :disabled="pagination.current_page === pagination.last_page"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                Suivant
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal Discogs -->
        <DiscogsVinylModal
            :show="showVinylModal"
            :collection-id="collection.id"
            :collections="[collection]"
            @close="showVinylModal = false"
            @openManualModal="openManualVinylModal"
        />


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

        <!-- Modal ajout vinyle manuel -->
        <ManualVinylModal
            :show="showManualVinylModal"
            :collection-id="collection.id"
            :collections="[collection]"
            @close="showManualVinylModal = false"
        />

        <!-- Modal édition vinyle -->
        <EditVinylModal
            v-if="vinylToEdit"
            :show="showEditVinylModal"
            :collection-vinyl="vinylToEdit"
            :collections="userCollections"
            @close="closeEditModal"
        />
    </AuthenticatedLayout>
</template>
