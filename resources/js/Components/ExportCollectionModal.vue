<template>
    <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative w-full max-w-4xl bg-gradient-to-br from-gray-900 to-gray-800 border border-indigo-500/30 shadow-2xl rounded-2xl" @click.stop>
            <!-- Header -->
            <div class="relative px-6 py-4 border-b border-gray-700/50 bg-gradient-to-r from-indigo-600/20 to-purple-600/20 rounded-t-2xl">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exporter la collection
                </h2>
                <button
                    @click="closeModal"
                    class="absolute right-4 top-4 text-gray-400 hover:text-white transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                <!-- Presets -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-3">Modèles prédéfinis</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <button
                            v-for="preset in presets"
                            :key="preset.id"
                            @click="applyPreset(preset)"
                            class="px-4 py-3 rounded-lg border-2 transition-all duration-200 text-sm font-medium hover:scale-105"
                            :class="[
                                currentPreset === preset.id
                                    ? 'border-indigo-500 bg-indigo-500/20 text-indigo-300'
                                    : 'border-gray-600 bg-gray-800/50 text-gray-300 hover:border-indigo-400'
                            ]"
                        >
                            <div class="flex flex-col items-center gap-1">
                                <span>{{ preset.name }}</span>
                                <span class="text-xs text-gray-400">{{ preset.columns.length }} colonnes</span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Columns Selection -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-gray-300">
                            Colonnes à exporter ({{ selectedColumns.length }}/{{ availableColumns.length }})
                        </label>
                        <div class="flex gap-2">
                            <button
                                @click="selectAllColumns"
                                class="text-xs px-3 py-1 rounded bg-indigo-600 hover:bg-indigo-500 text-white transition-colors"
                            >
                                Tout sélectionner
                            </button>
                            <button
                                @click="deselectAllColumns"
                                class="text-xs px-3 py-1 rounded bg-gray-700 hover:bg-gray-600 text-white transition-colors"
                            >
                                Tout désélectionner
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <label
                            v-for="column in availableColumns"
                            :key="column.key"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-700 bg-gray-800/50 hover:bg-gray-800 transition-colors cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="column.key"
                                v-model="selectedColumns"
                                class="rounded text-indigo-600 focus:ring-indigo-500 focus:ring-offset-gray-900"
                            />
                            <span class="text-sm text-gray-300">{{ column.label }}</span>
                        </label>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6 p-4 rounded-lg bg-gray-800/50 border border-gray-700">
                    <h3 class="text-sm font-medium text-gray-300 mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filtres
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Date d'ajout -->
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Date d'ajout (depuis)</label>
                            <input
                                type="date"
                                v-model="filters.dateAjoutFrom"
                                class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Date d'ajout (jusqu'à)</label>
                            <input
                                type="date"
                                v-model="filters.dateAjoutTo"
                                class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Année d'achat -->
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Année d'achat (min)</label>
                            <input
                                type="number"
                                v-model="filters.anneeAchatMin"
                                placeholder="Ex: 2020"
                                class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Année d'achat (max)</label>
                            <input
                                type="number"
                                v-model="filters.anneeAchatMax"
                                placeholder="Ex: 2024"
                                class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Année de sortie -->
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Année de sortie (min)</label>
                            <input
                                type="number"
                                v-model="filters.anneeSortieMin"
                                placeholder="Ex: 1970"
                                class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Année de sortie (max)</label>
                            <input
                                type="number"
                                v-model="filters.anneeSortieMax"
                                placeholder="Ex: 2024"
                                class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Prix -->
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Prix min (€)</label>
                            <input
                                type="number"
                                step="0.01"
                                v-model="filters.prixMin"
                                placeholder="Ex: 10.00"
                                class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Prix max (€)</label>
                            <input
                                type="number"
                                step="0.01"
                                v-model="filters.prixMax"
                                placeholder="Ex: 50.00"
                                class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Note -->
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Note minimum</label>
                            <select
                                v-model="filters.noteMin"
                                class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                                <option value="">Toutes</option>
                                <option value="1">⭐ 1+</option>
                                <option value="2">⭐ 2+</option>
                                <option value="3">⭐ 3+</option>
                                <option value="4">⭐ 4+</option>
                                <option value="5">⭐ 5</option>
                            </select>
                        </div>

                        <!-- Provenance -->
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Provenance</label>
                            <input
                                type="text"
                                v-model="filters.provenance"
                                placeholder="Ex: Fnac, Discogs..."
                                class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>
                    </div>
                    <button
                        @click="clearFilters"
                        class="mt-3 text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                    >
                        Réinitialiser les filtres
                    </button>
                </div>

                <!-- Sort Options -->
                <div class="mb-6 p-4 rounded-lg bg-gray-800/50 border border-gray-700">
                    <h3 class="text-sm font-medium text-gray-300 mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                        </svg>
                        Tri
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Trier par</label>
                            <select
                                v-model="sortBy"
                                class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                                <option v-for="column in sortableColumns" :key="column.key" :value="column.key">
                                    {{ column.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Ordre</label>
                            <select
                                v-model="sortOrder"
                                class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                                <option value="asc">Croissant (A-Z, 0-9)</option>
                                <option value="desc">Décroissant (Z-A, 9-0)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="p-4 rounded-lg bg-indigo-500/10 border border-indigo-500/30">
                    <p class="text-sm text-indigo-300">
                        <strong>Aperçu :</strong> Export de {{ totalVinyls }} vinyles avec {{ selectedColumns.length }} colonnes
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-700/50 bg-gray-900/50 rounded-b-2xl flex justify-end gap-3">
                <button
                    @click="closeModal"
                    class="px-4 py-2 rounded-lg border border-gray-600 text-gray-300 hover:bg-gray-800 transition-colors"
                >
                    Annuler
                </button>
                <button
                    @click="handleExport"
                    :disabled="selectedColumns.length === 0"
                    class="px-6 py-2 rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exporter en Excel
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: Boolean,
    collectionId: Number,
    totalVinyls: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['close']);

// Available columns
const availableColumns = [
    { key: 'vinyl_nom', label: 'Nom du vinyle' },
    { key: 'artiste', label: 'Artiste' },
    { key: 'vinyl_titre', label: 'Titre' },
    { key: 'vinyl_format', label: 'Format' },
    { key: 'label', label: 'Label' },
    { key: 'reference', label: 'Référence' },
    { key: 'annee', label: 'Année de sortie' },
    { key: 'pays', label: 'Pays' },
    { key: 'prix_achat', label: 'Prix d\'achat' },
    { key: 'annee_achat', label: 'Année d\'achat' },
    { key: 'provenance', label: 'Provenance' },
    { key: 'note', label: 'Note' },
    { key: 'commentaires', label: 'Commentaires' },
    { key: 'date_ajout', label: 'Date d\'ajout' },
    { key: 'discogs_id', label: 'Discogs ID' }
];

// Sortable columns (pour le tri dans l'export)
const sortableColumns = [
    { key: 'date_ajout', label: 'Date d\'ajout' },
    { key: 'updated_at', label: 'Dernière modification' },
    { key: 'artiste', label: 'Artiste' },
    { key: 'vinyl_titre', label: 'Titre' },
    { key: 'vinyl_nom', label: 'Nom du vinyle' },
    { key: 'annee', label: 'Année de sortie' },
    { key: 'annee_achat', label: 'Année d\'achat' },
    { key: 'prix_achat', label: 'Prix d\'achat' },
    { key: 'note', label: 'Note' },
    { key: 'label', label: 'Label' },
    { key: 'reference', label: 'Référence' }
];

// Selected columns
const selectedColumns = ref([
    'vinyl_nom', 'artiste', 'vinyl_titre', 'vinyl_format',
    'label', 'reference', 'annee', 'pays', 'prix_achat',
    'annee_achat', 'provenance', 'note', 'commentaires',
    'date_ajout', 'discogs_id'
]);

// Filters
const filters = ref({
    dateAjoutFrom: '',
    dateAjoutTo: '',
    anneeAchatMin: '',
    anneeAchatMax: '',
    anneeSortieMin: '',
    anneeSortieMax: '',
    prixMin: '',
    prixMax: '',
    noteMin: '',
    provenance: ''
});

// Sort options
const sortBy = ref('date_ajout');
const sortOrder = ref('desc');

// Current preset
const currentPreset = ref('complet');

// Presets
const presets = [
    {
        id: 'complet',
        name: 'Complet',
        columns: [
            'vinyl_nom', 'artiste', 'vinyl_titre', 'vinyl_format',
            'label', 'reference', 'annee', 'pays', 'prix_achat',
            'annee_achat', 'provenance', 'note', 'commentaires',
            'date_ajout', 'discogs_id'
        ]
    },
    {
        id: 'imprimable',
        name: 'Imprimable',
        columns: ['artiste', 'vinyl_titre', 'vinyl_format', 'annee', 'prix_achat', 'note']
    },
    {
        id: 'basique',
        name: 'Basique',
        columns: ['artiste', 'vinyl_titre', 'annee', 'date_ajout']
    },
    {
        id: 'inventaire',
        name: 'Inventaire',
        columns: ['vinyl_nom', 'artiste', 'vinyl_titre', 'reference', 'prix_achat', 'provenance', 'date_ajout']
    }
];

// Methods
const applyPreset = (preset) => {
    currentPreset.value = preset.id;
    selectedColumns.value = [...preset.columns];
};

const selectAllColumns = () => {
    selectedColumns.value = availableColumns.map(col => col.key);
    currentPreset.value = null;
};

const deselectAllColumns = () => {
    selectedColumns.value = [];
    currentPreset.value = null;
};

const clearFilters = () => {
    filters.value = {
        dateAjoutFrom: '',
        dateAjoutTo: '',
        anneeAchatMin: '',
        anneeAchatMax: '',
        anneeSortieMin: '',
        anneeSortieMax: '',
        prixMin: '',
        prixMax: '',
        noteMin: '',
        provenance: ''
    };
};

const handleExport = () => {
    if (selectedColumns.value.length === 0) {
        return;
    }

    // Build URL with parameters
    const params = new URLSearchParams();

    // Columns
    params.append('columns', selectedColumns.value.join(','));

    // Filters
    if (filters.value.dateAjoutFrom) params.append('date_ajout_from', filters.value.dateAjoutFrom);
    if (filters.value.dateAjoutTo) params.append('date_ajout_to', filters.value.dateAjoutTo);
    if (filters.value.anneeAchatMin) params.append('annee_achat_min', filters.value.anneeAchatMin);
    if (filters.value.anneeAchatMax) params.append('annee_achat_max', filters.value.anneeAchatMax);
    if (filters.value.anneeSortieMin) params.append('annee_sortie_min', filters.value.anneeSortieMin);
    if (filters.value.anneeSortieMax) params.append('annee_sortie_max', filters.value.anneeSortieMax);
    if (filters.value.prixMin) params.append('prix_min', filters.value.prixMin);
    if (filters.value.prixMax) params.append('prix_max', filters.value.prixMax);
    if (filters.value.noteMin) params.append('note_min', filters.value.noteMin);
    if (filters.value.provenance) params.append('provenance', filters.value.provenance);

    // Sort
    params.append('sort_by', sortBy.value);
    params.append('sort_order', sortOrder.value);

    // Open export URL in new tab
    window.open(`/collections/${props.collectionId}/export?${params.toString()}`, '_blank');

    closeModal();
};

const closeModal = () => {
    emit('close');
};
</script>
