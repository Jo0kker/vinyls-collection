<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DiscogsVinylModal from '@/Components/DiscogsVinylModal.vue';
import ManualVinylModal from '@/Components/ManualVinylModal.vue';
import EditVinylModal from '@/Components/EditVinylModal.vue';
import VinylImage from '@/Components/VinylImage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    vinyls: {
        type: Object,
        required: true
    },
    allCollections: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            sort: 'date_ajout',
            order: 'desc',
            per_page: 20,
            letter: '',
        })
    }
});

// États des modales
const showVinylModal = ref(false);
const showManualVinylModal = ref(false);
const showEditVinylModal = ref(false);
const vinylToEdit = ref(null);

// États des filtres
const searchQuery = ref(props.filters.search || '');
const sortBy = ref(props.filters.sort || 'date_ajout');
const sortOrder = ref(props.filters.order || 'desc');
const perPage = ref(props.filters.per_page || 20);
const selectedLetter = ref(props.filters.letter || '');

// Filtres avancés
const showAdvancedFilters = ref(false);
const advancedFilters = ref({
    titre: props.filters.filter_titre || '',
    artiste: props.filters.filter_artiste || '',
    annee_min: props.filters.filter_annee_min || '',
    annee_max: props.filters.filter_annee_max || '',
    label: props.filters.filter_label || '',
    format: props.filters.filter_format || '',
    collection: props.filters.filter_collection || '',
});

// Mode d'affichage
const getStoredViewMode = () => {
    try {
        return localStorage.getItem('vinyl-view-mode') || 'grid';
    } catch {
        return 'grid';
    }
};

const viewMode = ref(getStoredViewMode());

const setViewMode = (mode) => {
    viewMode.value = mode;
    try {
        localStorage.setItem('vinyl-view-mode', mode);
    } catch {}
};

// Alphabet pour l'index
const alphabet = ['#', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

// Fonctions de filtre
const applyFilters = (page = 1) => {
    const params = new URLSearchParams();

    if (searchQuery.value) params.append('search', searchQuery.value);
    if (sortBy.value !== 'date_ajout') params.append('sort', sortBy.value);
    if (sortOrder.value !== 'desc') params.append('order', sortOrder.value);
    if (perPage.value !== 20) params.append('per_page', perPage.value);
    if (selectedLetter.value) params.append('letter', selectedLetter.value);
    if (page > 1) params.append('page', page);

    // Filtres avancés
    if (advancedFilters.value.titre) params.append('filter_titre', advancedFilters.value.titre);
    if (advancedFilters.value.artiste) params.append('filter_artiste', advancedFilters.value.artiste);
    if (advancedFilters.value.annee_min) params.append('filter_annee_min', advancedFilters.value.annee_min);
    if (advancedFilters.value.annee_max) params.append('filter_annee_max', advancedFilters.value.annee_max);
    if (advancedFilters.value.label) params.append('filter_label', advancedFilters.value.label);
    if (advancedFilters.value.format) params.append('filter_format', advancedFilters.value.format);
    if (advancedFilters.value.collection) params.append('filter_collection', advancedFilters.value.collection);

    const queryString = params.toString();
    router.get(`/mes-vinyles${queryString ? '?' + queryString : ''}`, {}, {
        preserveState: true,
        preserveScroll: false
    });
};

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters();
};

const selectLetter = (letter) => {
    selectedLetter.value = selectedLetter.value === letter ? '' : letter;
    applyFilters();
};

const clearAllFilters = () => {
    searchQuery.value = '';
    sortBy.value = 'date_ajout';
    sortOrder.value = 'desc';
    selectedLetter.value = '';
    advancedFilters.value = {
        titre: '',
        artiste: '',
        annee_min: '',
        annee_max: '',
        label: '',
        format: '',
        collection: '',
    };
    applyFilters();
};

const toggleSortOrder = () => {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return searchQuery.value ||
           selectedLetter.value ||
           sortBy.value !== 'date_ajout' ||
           sortOrder.value !== 'desc' ||
           Object.values(advancedFilters.value).some(v => v);
});

// Modales
const openVinylModal = () => {
    showVinylModal.value = true;
};

const openManualVinylModal = () => {
    showVinylModal.value = false;
    showManualVinylModal.value = true;
};

const openEditModal = (vinyl) => {
    vinylToEdit.value = vinyl;
    showEditVinylModal.value = true;
};

const closeEditModal = () => {
    showEditVinylModal.value = false;
    vinylToEdit.value = null;
};

// Gestion de la mise à jour d'image
const handleImageUpdated = (data) => {
    // Refresh la page pour récupérer les nouvelles données
    router.reload();
};

// Formats de vinyle
const formatLabels = {
    1: 'LP (33 tours)',
    2: '45 tours',
    3: 'CD',
    4: 'Cassette',
    5: 'DVD',
    6: 'Blu-ray',
    7: 'Box Set',
    8: '78 tours',
    9: 'Digital'
};
</script>

<template>
    <Head>
        <title>Mes Vinyles - Ma Collection | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="description" content="Gérez votre collection personnelle de vinyles. Ajoutez, modifiez et organisez vos albums préférés." />
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Mes Vinyles
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                        ({{ vinyls.total }} vinyle{{ vinyls.total > 1 ? 's' : '' }})
                    </span>
                </h2>
                <button @click="openVinylModal"
                   class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors">
                    Ajouter un vinyle
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Barre de recherche et filtres -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <!-- Recherche principale -->
                        <div class="flex flex-col lg:flex-row gap-4 mb-4">
                            <div class="flex-1">
                                <div class="relative">
                                    <input
                                        v-model="searchQuery"
                                        @keyup.enter="applyFilters"
                                        type="text"
                                        placeholder="Rechercher un vinyle (titre, artiste, label, année...)"
                                        class="w-full pl-10 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500"
                                    >
                                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <button v-if="searchQuery" @click="clearSearch" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button @click="applyFilters" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                Rechercher
                            </button>
                        </div>

                        <!-- Index alphabétique -->
                        <div class="flex flex-wrap gap-1 mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <button
                                v-for="letter in alphabet"
                                :key="letter"
                                @click="selectLetter(letter)"
                                :class="[
                                    'w-8 h-8 text-sm font-medium rounded transition-colors',
                                    selectedLetter === letter
                                        ? 'bg-purple-600 text-white'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-purple-100 dark:hover:bg-purple-900'
                                ]"
                            >
                                {{ letter }}
                            </button>
                            <button
                                v-if="selectedLetter"
                                @click="selectLetter('')"
                                class="ml-2 px-3 h-8 text-sm font-medium rounded bg-gray-300 dark:bg-gray-500 text-gray-800 dark:text-white hover:bg-gray-400 dark:hover:bg-gray-400 transition-colors"
                            >
                                Effacer
                            </button>
                        </div>

                        <!-- Options de tri et affichage -->
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <!-- Tri -->
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Trier par :</span>
                                    <select v-model="sortBy" @change="applyFilters" class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white">
                                        <option value="date_ajout">Date d'ajout</option>
                                        <option value="vinyl_nom">Titre</option>
                                        <option value="artiste">Artiste</option>
                                        <option value="annee">Année</option>
                                        <option value="prix_achat">Prix</option>
                                        <option value="note">Note</option>
                                    </select>
                                    <button @click="toggleSortOrder" class="p-2 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': sortOrder === 'asc' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Filtre par collection -->
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Collection :</span>
                                    <select v-model="advancedFilters.collection" @change="applyFilters" class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white">
                                        <option value="">Toutes</option>
                                        <option v-for="col in allCollections" :key="col.id" :value="col.id">
                                            {{ col.collection_nom }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Filtres avancés toggle -->
                                <button @click="showAdvancedFilters = !showAdvancedFilters"
                                        :class="[
                                            'px-3 py-2 text-sm border rounded-md transition-colors',
                                            showAdvancedFilters ? 'bg-purple-600 text-white border-purple-600' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                                        ]">
                                    Filtres avancés
                                </button>
                            </div>

                            <div class="flex items-center gap-4">
                                <!-- Par page -->
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Afficher :</span>
                                    <select v-model="perPage" @change="applyFilters(1)" class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white">
                                        <option :value="20">20</option>
                                        <option :value="50">50</option>
                                        <option :value="100">100</option>
                                        <option :value="500">500</option>
                                    </select>
                                </div>

                                <!-- Mode d'affichage -->
                                <div class="flex items-center gap-1 border border-gray-300 dark:border-gray-600 rounded-md p-1">
                                    <button @click="setViewMode('grid')" :class="['p-2 rounded', viewMode === 'grid' ? 'bg-purple-600 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700']">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                        </svg>
                                    </button>
                                    <button @click="setViewMode('list')" :class="['p-2 rounded', viewMode === 'list' ? 'bg-purple-600 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700']">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                        </svg>
                                    </button>
                                    <button @click="setViewMode('compact')" :class="['p-2 rounded', viewMode === 'compact' ? 'bg-purple-600 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700']">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Indicateurs de filtres actifs -->
                        <div v-if="hasActiveFilters" class="mt-4 flex flex-wrap items-center gap-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Filtres actifs :</span>
                            <span v-if="searchQuery" class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                "{{ searchQuery }}"
                                <button @click="clearSearch" class="ml-1 hover:text-purple-600">×</button>
                            </span>
                            <span v-if="selectedLetter" class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                Lettre: {{ selectedLetter }}
                                <button @click="selectLetter('')" class="ml-1 hover:text-blue-600">×</button>
                            </span>
                            <button @click="clearAllFilters" class="text-xs text-red-600 hover:text-red-800">
                                Effacer tous les filtres
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filtres avancés -->
                <div v-if="showAdvancedFilters" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Filtres avancés</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titre</label>
                                <input v-model="advancedFilters.titre" type="text" class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Artiste</label>
                                <input v-model="advancedFilters.artiste" type="text" class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Label</label>
                                <input v-model="advancedFilters.label" type="text" class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Format</label>
                                <select v-model="advancedFilters.format" class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white">
                                    <option value="">Tous</option>
                                    <option v-for="(label, key) in formatLabels" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Année min</label>
                                <input v-model="advancedFilters.annee_min" type="number" min="1900" :max="new Date().getFullYear()" class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Année max</label>
                                <input v-model="advancedFilters.annee_max" type="number" min="1900" :max="new Date().getFullYear()" class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button @click="applyFilters" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                                Appliquer
                            </button>
                            <button @click="showAdvancedFilters = false" class="px-4 py-2 text-gray-600 hover:text-gray-800 dark:text-gray-400">
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Liste des vinyles -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="!vinyls.data || vinyls.data.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun vinyle trouvé</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ hasActiveFilters ? 'Essayez de modifier vos filtres.' : 'Commencez par ajouter votre premier vinyle.' }}
                            </p>
                            <div v-if="!hasActiveFilters" class="mt-6">
                                <button @click="openVinylModal" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                                    Ajouter un vinyle
                                </button>
                            </div>
                        </div>

                        <!-- Mode Grille -->
                        <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="vinyl in vinyls.data" :key="vinyl.id"
                                 class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors group">
                                <div class="flex items-start space-x-4">
                                    <VinylImage :src="vinyl.vinyl?.pochette" :alt="vinyl.vinyl?.vinyl_nom" size="md" />

                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-gray-900 dark:text-white text-sm">
                                            {{ vinyl.vinyl?.vinyl_nom || 'Nom inconnu' }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ vinyl.vinyl?.artiste || 'Artiste inconnu' }}
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            {{ vinyl.collection?.collection_nom || 'Collection inconnue' }}
                                        </p>
                                        <div class="flex items-center mt-2 flex-wrap gap-2 text-xs text-gray-400">
                                            <span v-if="vinyl.quantite > 1" class="px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded-full font-medium">
                                                x{{ vinyl.quantite }}
                                            </span>
                                            <span v-if="vinyl.prix_achat">{{ vinyl.prix_achat }}€</span>
                                            <span v-if="vinyl.note">⭐ {{ vinyl.note }}/10</span>
                                            <span>{{ formatDate(vinyl.date_ajout) }}</span>
                                        </div>
                                    </div>

                                    <div class="flex space-x-1">
                                        <Link :href="route('vinyl.show', vinyl.vinyl.id)"
                                              class="p-1.5 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900 rounded-md transition-colors"
                                              title="Voir les détails">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </Link>
                                        <button @click="openEditModal(vinyl)"
                                                class="p-1.5 text-green-600 hover:bg-green-100 dark:hover:bg-green-900 rounded-md transition-colors"
                                                title="Éditer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mode Liste -->
                        <div v-else-if="viewMode === 'list'" class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Pochette</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Titre</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Artiste</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Collection</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Année</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Ajouté le</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="vinyl in vinyls.data" :key="vinyl.id"
                                        class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="py-3 px-4">
                                            <VinylImage :src="vinyl.vinyl?.pochette" :alt="vinyl.vinyl?.vinyl_nom" size="sm" />
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                                {{ vinyl.vinyl?.vinyl_nom || 'Nom inconnu' }}
                                                <span v-if="vinyl.quantite > 1" class="px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded-full text-xs font-medium">
                                                    x{{ vinyl.quantite }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ vinyl.vinyl?.artiste || '-' }}</td>
                                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ vinyl.collection?.collection_nom || '-' }}</td>
                                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ vinyl.vinyl?.annee || '-' }}</td>
                                        <td class="py-3 px-4 text-gray-500 dark:text-gray-400 text-xs">{{ formatDate(vinyl.date_ajout) }}</td>
                                        <td class="py-3 px-4">
                                            <div class="flex space-x-1">
                                                <Link :href="route('vinyl.show', vinyl.vinyl.id)" class="p-1.5 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900 rounded-md">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </Link>
                                                <button @click="openEditModal(vinyl)" class="p-1.5 text-green-600 hover:bg-green-100 dark:hover:bg-green-900 rounded-md">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mode Compact -->
                        <div v-else-if="viewMode === 'compact'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                            <div v-for="vinyl in vinyls.data" :key="vinyl.id"
                                 class="relative aspect-square rounded-lg overflow-hidden group hover:scale-105 transition-transform shadow-md">

                                <span v-if="vinyl.quantite > 1" class="absolute top-2 left-2 z-20 px-1.5 py-0.5 bg-purple-600 text-white rounded-full text-xs font-medium shadow">
                                    x{{ vinyl.quantite }}
                                </span>

                                <Link :href="route('vinyl.show', vinyl.vinyl.id)" class="absolute inset-0 z-0">
                                    <div class="absolute inset-0 bg-gray-200 dark:bg-gray-700">
                                        <img v-if="vinyl.vinyl?.pochette" :src="vinyl.vinyl.pochette" :alt="vinyl.vinyl?.vinyl_nom" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                                    <div class="absolute bottom-0 left-0 right-0 p-3 text-white">
                                        <h4 class="font-medium text-sm leading-tight mb-1 line-clamp-2">{{ vinyl.vinyl?.vinyl_nom || 'Nom inconnu' }}</h4>
                                        <p class="text-xs text-gray-200 truncate">{{ vinyl.vinyl?.artiste || 'Artiste inconnu' }}</p>
                                    </div>
                                </Link>

                                <button @click.prevent="openEditModal(vinyl)"
                                        class="absolute top-2 right-2 z-10 p-1.5 bg-white/90 dark:bg-gray-800/90 rounded-md opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="vinyls.last_page > 1" class="mt-6 flex items-center justify-between">
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                Affichage de {{ vinyls.from }} à {{ vinyls.to }} sur {{ vinyls.total }} vinyles
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="applyFilters(vinyls.current_page - 1)"
                                        :disabled="vinyls.current_page === 1"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Précédent
                                </button>

                                <div class="flex items-center gap-1">
                                    <button v-if="vinyls.current_page > 3" @click="applyFilters(1)" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm hover:bg-gray-50 dark:hover:bg-gray-700">1</button>
                                    <span v-if="vinyls.current_page > 4" class="px-2 text-gray-500">...</span>

                                    <template v-for="page in [vinyls.current_page - 2, vinyls.current_page - 1, vinyls.current_page, vinyls.current_page + 1, vinyls.current_page + 2]" :key="page">
                                        <button v-if="page > 0 && page <= vinyls.last_page"
                                                @click="applyFilters(page)"
                                                :class="[
                                                    'px-3 py-2 border rounded-md text-sm',
                                                    page === vinyls.current_page
                                                        ? 'bg-purple-600 text-white border-purple-600'
                                                        : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                                                ]">
                                            {{ page }}
                                        </button>
                                    </template>

                                    <span v-if="vinyls.current_page < vinyls.last_page - 3" class="px-2 text-gray-500">...</span>
                                    <button v-if="vinyls.current_page < vinyls.last_page - 2" @click="applyFilters(vinyls.last_page)" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm hover:bg-gray-50 dark:hover:bg-gray-700">{{ vinyls.last_page }}</button>
                                </div>

                                <button @click="applyFilters(vinyls.current_page + 1)"
                                        :disabled="vinyls.current_page === vinyls.last_page"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Suivant
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <DiscogsVinylModal
            :show="showVinylModal"
            :collections="allCollections"
            @close="showVinylModal = false"
            @openManualModal="openManualVinylModal"
        />

        <ManualVinylModal
            :show="showManualVinylModal"
            :collections="allCollections"
            @close="showManualVinylModal = false"
        />

        <EditVinylModal
            v-if="vinylToEdit"
            :show="showEditVinylModal"
            :collection-vinyl="vinylToEdit"
            :collections="allCollections"
            @close="closeEditModal"
            @image-updated="handleImageUpdated"
        />
    </AuthenticatedLayout>
</template>
