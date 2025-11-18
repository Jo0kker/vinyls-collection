<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import VinylImage from '@/Components/VinylImage.vue';
import DiscogsVinylModal from '@/Components/DiscogsVinylModal.vue';
import ManualVinylModal from '@/Components/ManualVinylModal.vue';
import EditVinylModal from '@/Components/EditVinylModal.vue';
import ExportCollectionModal from '@/Components/ExportCollectionModal.vue';
import DeleteCollectionModal from '@/Components/DeleteCollectionModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch, nextTick } from 'vue';

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
const showExportModal = ref(false);
const showDeleteCollectionModal = ref(false);

// État pour l'export
const isExporting = ref(false);

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

// Références reactives pour les données modifiables
const paginationData = ref(props.pagination?.data ? [...props.pagination.data] : []);
const collectionVinyls = ref(props.collection?.collection_vinyls ? [...props.collection.collection_vinyls] : []);

// Watcher pour mettre à jour les références quand les props changent
watch(() => props.pagination?.data, (newData) => {
    if (newData) {
        paginationData.value = [...newData];
    }
});

watch(() => props.collection?.collection_vinyls, (newVinyls) => {
    if (newVinyls) {
        collectionVinyls.value = [...newVinyls];
    }
});

// Synchroniser les filtres avec les props
watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        searchQuery.value = newFilters.search || '';
        sortBy.value = newFilters.sort || 'date_ajout';
        sortOrder.value = newFilters.order || 'desc';
        perPage.value = newFilters.per_page || 20;
    }
}, { deep: true, immediate: true });

// Filtres avancés
const showAdvancedFilters = ref(false);
const filters = ref({
    titre: '',
    artiste: '',
    annee_min: '',
    annee_max: '',
    genre: '',
    label: '',
    format: '',
    pays: '',
    commentaires: ''
});

// Collection filtrée - maintenant tous les filtres sont gérés par le backend
const filteredVinyls = computed(() => {
    // Les résultats sont toujours filtrés par le backend (recherche + filtres avancés)
    // On retourne directement paginationData.value
    return paginationData.value || [];
});

// Options pour les filtres (à générer dynamiquement depuis les données)
const filterOptions = computed(() => {
    if (!props.collection.collection_vinyls) return {};
    
    const vinyls = props.collection.collection_vinyls;
    return {
        genres: [...new Set(vinyls.map(v => v.vinyl?.genre).filter(Boolean))].sort(),
        labels: [...new Set(vinyls.map(v => v.vinyl?.label).filter(Boolean))].sort(),
        formats: [...new Set(vinyls.map(v => v.vinyl?.format).filter(Boolean))].sort(),
        pays: [...new Set(vinyls.map(v => v.vinyl?.pays).filter(Boolean))].sort(),
    };
});

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

// Configuration des colonnes disponibles
const availableColumns = [
    { key: 'select', label: 'Sélection', enabled: false },
    { key: 'pochette', label: 'Pochette', enabled: true },
    { key: 'titre', label: 'Titre', enabled: true },
    { key: 'artiste', label: 'Artiste', enabled: true },
    { key: 'annee', label: 'Année', enabled: true },
    { key: 'label', label: 'Label', enabled: true },
    { key: 'format', label: 'Format', enabled: false },
    { key: 'pays', label: 'Pays', enabled: false },
    { key: 'genre', label: 'Genre', enabled: false },
    { key: 'style', label: 'Style', enabled: false },
    { key: 'commentaires', label: 'Commentaires', enabled: false },
    { key: 'date_ajout', label: 'Ajouté le', enabled: true },
    { key: 'actions', label: 'Actions', enabled: true }
];

// Charger les préférences de colonnes depuis le localStorage
const getStoredColumns = () => {
    try {
        const stored = localStorage.getItem('vinyl-list-columns');
        if (stored) {
            const storedColumns = JSON.parse(stored);
            // Créer une map pour retrouver facilement les colonnes stockées
            const storedMap = new Map(storedColumns.map(col => [col.key, col]));
            
            // Réorganiser selon l'ordre stocké et fusionner avec les nouvelles colonnes
            const orderedColumns = [];
            
            // D'abord ajouter les colonnes dans l'ordre stocké
            storedColumns.forEach(storedCol => {
                const availableCol = availableColumns.find(ac => ac.key === storedCol.key);
                if (availableCol) {
                    orderedColumns.push({
                        ...availableCol,
                        enabled: storedCol.enabled,
                        order: storedCol.order || orderedColumns.length
                    });
                }
            });
            
            // Ajouter les nouvelles colonnes qui n'étaient pas stockées
            availableColumns.forEach(col => {
                if (!orderedColumns.find(oc => oc.key === col.key)) {
                    orderedColumns.push({
                        ...col,
                        order: orderedColumns.length
                    });
                }
            });
            
            return orderedColumns;
        }
    } catch (error) {
        console.warn('Impossible de charger les préférences de colonnes:', error);
    }
    return availableColumns.map((col, index) => ({ ...col, order: index }));
};

const columns = ref(getStoredColumns());
const showColumnSettings = ref(false);
const draggedColumn = ref(null);
const dragOverColumn = ref(null);

// Sélection multiple
const selectedVinyls = ref(new Set());
const showBulkActions = ref(false);

// Vérifier si tous les vinyls sont sélectionnés
const isAllSelected = computed(() => {
    return props.collection.collection_vinyls?.length > 0 && 
           selectedVinyls.value.size === props.collection.collection_vinyls.length;
});

// Vérifier si certains vinyls sont sélectionnés (pour l'état intermédiaire)
const isPartiallySelected = computed(() => {
    return selectedVinyls.value.size > 0 && selectedVinyls.value.size < (props.collection.collection_vinyls?.length || 0);
});

// Sélectionner/désélectionner tous
const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedVinyls.value.clear();
    } else {
        selectedVinyls.value.clear();
        props.collection.collection_vinyls?.forEach(vinyl => {
            selectedVinyls.value.add(vinyl.id);
        });
    }
    updateBulkActionsVisibility();
};

// Sélectionner/désélectionner un vinyle
const toggleSelectVinyl = (vinylId) => {
    if (selectedVinyls.value.has(vinylId)) {
        selectedVinyls.value.delete(vinylId);
    } else {
        selectedVinyls.value.add(vinylId);
    }
    updateBulkActionsVisibility();
};

// Mettre à jour la visibilité des actions en lot
const updateBulkActionsVisibility = () => {
    showBulkActions.value = selectedVinyls.value.size > 0;
};

// Effacer la sélection
const clearSelection = () => {
    selectedVinyls.value.clear();
    updateBulkActionsVisibility();
};

// Actions en lot
const bulkEdit = () => {
    const selectedIds = Array.from(selectedVinyls.value);
    const selectedVinylsList = props.collection.collection_vinyls.filter(v => selectedIds.includes(v.id));
    
    // Pour l'instant, éditer le premier élément sélectionné
    // Plus tard, on pourra créer une modal d'édition en lot
    if (selectedVinylsList.length > 0) {
        openEditModal(selectedVinylsList[0]);
    }
};

const bulkMove = () => {
    const selectedIds = Array.from(selectedVinyls.value);
    if (selectedIds.length > 0 && props.userCollections.length > 1) {
        // Créer une modal de déplacement en lot
        showBulkMoveModal.value = true;
    }
};

const bulkDelete = () => {
    const selectedIds = Array.from(selectedVinyls.value);
    if (selectedIds.length > 0) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer ${selectedIds.length} vinyle(s) de cette collection ?`)) {
            // Supprimer tous les éléments sélectionnés
            selectedIds.forEach(vinylId => {
                router.delete(`/collections/${props.collection.id}/vinyl/${vinylId}`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        // Retirer de la sélection
                        selectedVinyls.value.delete(vinylId);
                    },
                    onFinish: () => {
                        updateBulkActionsVisibility();
                        router.reload({ only: ['collection'] });
                    }
                });
            });
        }
    }
};

// Sauvegarder les préférences de colonnes
const saveColumnPreferences = () => {
    try {
        const columnsToSave = columns.value.map((col, index) => ({
            key: col.key,
            enabled: col.enabled,
            order: index
        }));
        localStorage.setItem('vinyl-list-columns', JSON.stringify(columnsToSave));
    } catch (error) {
        console.warn('Impossible de sauvegarder les préférences de colonnes:', error);
    }
};

// Basculer l'état d'une colonne
const toggleColumn = (columnKey) => {
    const column = columns.value.find(col => col.key === columnKey);
    if (column) {
        column.enabled = !column.enabled;
        saveColumnPreferences();
    }
};

// Réinitialiser les colonnes par défaut
const resetColumns = () => {
    columns.value = availableColumns.map((col, index) => ({ ...col, order: index }));
    saveColumnPreferences();
    showColumnSettings.value = false;
};

// Fonctions de drag and drop
const handleDragStart = (column) => {
    draggedColumn.value = column;
};

const handleDragOver = (e, column) => {
    e.preventDefault();
    if (!draggedColumn.value) return;
    dragOverColumn.value = column;
};

const handleDragLeave = () => {
    dragOverColumn.value = null;
};

const handleDrop = (e, targetColumn) => {
    e.preventDefault();
    if (!draggedColumn.value) return;
    
    const draggedIndex = columns.value.findIndex(col => col.key === draggedColumn.value.key);
    const targetIndex = columns.value.findIndex(col => col.key === targetColumn.key);
    
    if (draggedIndex !== -1 && targetIndex !== -1 && draggedIndex !== targetIndex) {
        // Retirer l'élément déplacé
        const [removed] = columns.value.splice(draggedIndex, 1);
        // L'insérer à la nouvelle position
        columns.value.splice(targetIndex, 0, removed);
        
        // Mettre à jour l'ordre
        columns.value.forEach((col, index) => {
            col.order = index;
        });
        
        saveColumnPreferences();
    }
    
    draggedColumn.value = null;
    dragOverColumn.value = null;
};

const handleDragEnd = () => {
    draggedColumn.value = null;
    dragOverColumn.value = null;
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

// Fonction pour vérifier si un champ correspond à la recherche
const matchesSearch = (value, searchTerm) => {
    if (!searchTerm || !value) return false;
    return value.toString().toLowerCase().includes(searchTerm.toLowerCase());
};

// Fonction pour obtenir les champs qui correspondent à la recherche
const getMatchingFields = (vinyl) => {
    if (!searchQuery.value) return [];

    const matches = [];
    const search = searchQuery.value.toLowerCase();

    if (vinyl?.vinyl_nom && vinyl.vinyl_nom.toLowerCase().includes(search)) {
        matches.push('Nom');
    }
    if (vinyl?.vinyl_titre && vinyl.vinyl_titre.toLowerCase().includes(search)) {
        matches.push('Titre');
    }
    if (vinyl?.artiste && vinyl.artiste.toLowerCase().includes(search)) {
        matches.push('Artiste');
    }
    if (vinyl?.label && vinyl.label.toLowerCase().includes(search)) {
        matches.push('Label');
    }
    if (vinyl?.reference && vinyl.reference.toLowerCase().includes(search)) {
        matches.push('Référence');
    }
    if (vinyl?.annee && vinyl.annee.toString().includes(search)) {
        matches.push('Année');
    }

    return matches;
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

const getSortLabel = (sortByValue) => {
    const labels = {
        'date_ajout': 'Date d\'ajout',
        'updated_at': 'Dernière modification',
        'artiste': 'Artiste',
        'vinyl_titre': 'Titre de l\'album',
        'vinyl_nom': 'Nom',
        'annee': 'Année de sortie',
        'annee_achat': 'Année d\'achat',
        'prix_achat': 'Prix d\'achat',
        'note': 'Note'
    };
    return labels[sortByValue] || sortByValue;
};

// Fonctions pour les filtres avancés
const applyAdvancedFilters = () => {
    // Envoyer les filtres au backend via Inertia
    router.get(route('collections.show', props.collection.id), {
        search: searchQuery.value,
        sort: sortBy.value,
        order: sortOrder.value,
        per_page: perPage.value,
        filter_titre: filters.value.titre || '',
        filter_artiste: filters.value.artiste || '',
        filter_annee_min: filters.value.annee_min || '',
        filter_annee_max: filters.value.annee_max || '',
        filter_genre: filters.value.genre || '',
        filter_label: filters.value.label || '',
        filter_format: filters.value.format || '',
        filter_pays: filters.value.pays || '',
        filter_commentaires: filters.value.commentaires || '',
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearAdvancedFilters = () => {
    filters.value = {
        titre: '',
        artiste: '',
        annee_min: '',
        annee_max: '',
        genre: '',
        label: '',
        format: '',
        pays: '',
        commentaires: ''
    };
    applyAdvancedFilters();
};

// Fonctions de tri par colonne
const getSortableColumns = () => {
    return ['titre', 'artiste', 'annee', 'label', 'format', 'pays', 'genre', 'date_ajout'];
};

const sortByColumn = (columnKey) => {
    if (sortBy.value === columnKey) {
        // Si on clique sur la même colonne, inverser l'ordre
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        // Nouvelle colonne, commencer par ordre croissant
        sortBy.value = columnKey;
        sortOrder.value = 'asc';
    }
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

const handleImageUpdated = async (data) => {
    // Ajouter timestamp pour éviter le cache du navigateur
    const imageUrlWithTimestamp = data.newImageUrl + '?t=' + Date.now();

    // Approche plus radicale : recréer complètement les tableaux
    if (paginationData.value.length > 0) {
        paginationData.value = paginationData.value.map(vinyl => {
            if (vinyl.id === data.collectionVinylId) {
                return {
                    ...vinyl,
                    vinyl: {
                        ...vinyl.vinyl,
                        pochette: imageUrlWithTimestamp
                    }
                };
            }
            return vinyl;
        });
    }

    if (collectionVinyls.value.length > 0) {
        collectionVinyls.value = collectionVinyls.value.map(vinyl => {
            if (vinyl.id === data.collectionVinylId) {
                return {
                    ...vinyl,
                    vinyl: {
                        ...vinyl.vinyl,
                        pochette: imageUrlWithTimestamp
                    }
                };
            }
            return vinyl;
        });
    }

    // Forcer le re-rendu
    await nextTick();
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

// Actions pour supprimer la collection
const openDeleteCollectionModal = () => {
    showDeleteCollectionModal.value = true;
};

const closeDeleteCollectionModal = () => {
    showDeleteCollectionModal.value = false;
};

const confirmDeleteCollection = () => {
    router.delete(`/collections/${props.collection.id}`, {
        onSuccess: () => {
            closeDeleteCollectionModal();
        }
    });
};

// État pour les messages d'erreur
const exportError = ref('');

// Fonction pour gérer l'export Excel
const handleExport = async () => {
    if (isExporting.value) return; // Empêcher les double-clics

    isExporting.value = true;
    exportError.value = '';

    try {
        // Faire une requête fetch pour vérifier le rate limiting
        const response = await fetch(`/collections/${props.collection.id}/export`, {
            method: 'GET',
            credentials: 'same-origin',
        });

        if (response.status === 429) {
            // Trop de requêtes
            exportError.value = 'Trop d\'exports demandés. Veuillez patienter une minute avant de réessayer.';
            isExporting.value = false;
            setTimeout(() => {
                exportError.value = '';
            }, 5000);
            return;
        }

        if (!response.ok) {
            throw new Error('Erreur lors de l\'export');
        }

        // Déclencher le téléchargement
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `collection-${props.collection.collection_nom}-${new Date().toISOString().split('T')[0]}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        // Réactiver le bouton après un court délai
        setTimeout(() => {
            isExporting.value = false;
        }, 2000);
    } catch (error) {
        console.error('Erreur lors de l\'export:', error);
        exportError.value = 'Une erreur est survenue lors de l\'export.';
        isExporting.value = false;
        setTimeout(() => {
            exportError.value = '';
        }, 5000);
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
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <Link href="/collections" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                        ← Retour aux collections
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        {{ collection.collection_nom }}
                    </h2>
                </div>
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <!-- Message d'erreur pour l'export -->
                    <div v-if="exportError" class="w-full sm:w-auto text-red-600 text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span>{{ exportError }}</span>
                    </div>
                    <button @click="showExportModal = true"
                            class="flex-1 sm:flex-initial px-3 py-2 sm:px-4 rounded-md transition-all inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm sm:text-base"
                            title="Exporter la collection en Excel">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="hidden sm:inline">Exporter Excel</span>
                        <span class="inline sm:hidden">Export</span>
                    </button>
                    <Link :href="`/collections/${collection.id}/edit`"
                       class="flex-1 sm:flex-initial bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 sm:px-4 rounded-md transition-colors text-sm sm:text-base inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Modifier
                    </Link>
                    <button @click="openDeleteCollectionModal"
                       class="flex-1 sm:flex-initial bg-red-600 hover:bg-red-700 text-white px-3 py-2 sm:px-4 rounded-md transition-colors text-sm sm:text-base inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span class="hidden sm:inline">Supprimer</span>
                        <span class="inline sm:hidden">Suppr.</span>
                    </button>
                    <button @click="openVinylModal"
                           class="flex-1 sm:flex-initial bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 sm:px-4 rounded-md transition-colors text-sm sm:text-base inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="hidden sm:inline">Ajouter un vinyle</span>
                        <span class="inline sm:hidden">Ajouter</span>
                    </button>
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
                                    <option value="updated_at">Dernière modification</option>
                                    <option value="artiste">Artiste</option>
                                    <option value="vinyl_titre">Titre de l'album</option>
                                    <option value="annee">Année de sortie</option>
                                    <option value="annee_achat">Année d'achat</option>
                                    <option value="prix_achat">Prix d'achat</option>
                                    <option value="note">Note</option>
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
                                
                                <!-- Bouton filtres avancés -->
                                <button @click="showAdvancedFilters = !showAdvancedFilters"
                                        :class="[
                                            'p-2 border rounded-md transition-colors focus:ring-2 focus:ring-purple-500',
                                            showAdvancedFilters
                                                ? 'bg-purple-600 text-white border-purple-600'
                                                : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                                        ]"
                                        title="Filtres avancés">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
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
                                Tri: {{ getSortLabel(sortBy) }}
                            </span>
                            <span v-if="sortOrder !== 'desc'" class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Ordre: {{ sortOrder === 'asc' ? 'Croissant' : 'Décroissant' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Filtres avancés -->
                <div v-if="showAdvancedFilters" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Filtres avancés</h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ filteredVinyls.length }} / {{ props.collection.collection_vinyls?.length || 0 }} vinyles
                            </span>
                        </div>
                        
                        <!-- Filtres de recherche textuelle -->
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Recherche textuelle</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Titre de l'album</label>
                                    <input v-model="filters.titre"
                                           type="text"
                                           placeholder="Rechercher par titre..."
                                           @input="applyAdvancedFilters"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                                </div>
                                
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Artiste</label>
                                    <input v-model="filters.artiste"
                                           type="text"
                                           placeholder="Rechercher par artiste..."
                                           @input="applyAdvancedFilters"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                                </div>
                                
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Commentaires</label>
                                    <input v-model="filters.commentaires"
                                           type="text"
                                           placeholder="Rechercher dans commentaires..."
                                           @input="applyAdvancedFilters"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                                </div>
                            </div>
                        </div>

                        <!-- Séparateur -->
                        <div class="border-t border-gray-200 dark:border-gray-700 mb-6"></div>

                        <!-- Filtres par catégories -->
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Filtres par catégories</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Filtre par genre -->
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Genre</label>
                                    <select v-model="filters.genre"
                                            @change="applyAdvancedFilters"
                                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                                        <option value="">Tous les genres</option>
                                        <option v-for="genre in filterOptions.genres" :key="genre" :value="genre">
                                            {{ genre }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Filtre par label -->
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Label</label>
                                    <select v-model="filters.label"
                                            @change="applyAdvancedFilters"
                                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                                        <option value="">Tous les labels</option>
                                        <option v-for="label in filterOptions.labels" :key="label" :value="label">
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Filtre par format -->
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Format</label>
                                    <select v-model="filters.format"
                                            @change="applyAdvancedFilters"
                                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                                        <option value="">Tous les formats</option>
                                        <option v-for="format in filterOptions.formats" :key="format" :value="format">
                                            {{ format }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Filtre par pays -->
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Pays</label>
                                    <select v-model="filters.pays"
                                            @change="applyAdvancedFilters"
                                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                                        <option value="">Tous les pays</option>
                                        <option v-for="pays in filterOptions.pays" :key="pays" :value="pays">
                                            {{ pays }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Séparateur -->
                        <div class="border-t border-gray-200 dark:border-gray-700 mb-6"></div>

                        <!-- Filtre par année -->
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Plage d'années</h4>
                            <div class="flex items-center gap-4 max-w-md">
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Année min</label>
                                    <input v-model="filters.annee_min"
                                           type="number"
                                           placeholder="1950"
                                           min="1900"
                                           max="2030"
                                           @input="applyAdvancedFilters"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                                </div>
                                <div class="text-gray-400 mt-4">à</div>
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Année max</label>
                                    <input v-model="filters.annee_max"
                                           type="number"
                                           placeholder="2024"
                                           min="1900"
                                           max="2030"
                                           @input="applyAdvancedFilters"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                                </div>
                            </div>
                        </div>

                        <!-- Actions des filtres -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <button @click="clearAdvancedFilters"
                                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                    Effacer tous les filtres
                                </button>
                                <span class="text-xs text-gray-400">|</span>
                                <button @click="showAdvancedFilters = false"
                                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                    Fermer les filtres
                                </button>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Filtrage en temps réel
                            </div>
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

                        <div v-if="!filteredVinyls || filteredVinyls.length === 0"
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
                            <div v-for="collectionVinyl in filteredVinyls" :key="collectionVinyl.id"
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


                        <div v-else-if="viewMode === 'list'" class="relative">
                            <!-- Barre d'actions en lot -->
                            <div v-if="showBulkActions" 
                                 class="mb-4 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <span class="text-sm font-medium text-purple-900 dark:text-purple-100">
                                            {{ selectedVinyls.size }} élément{{ selectedVinyls.size > 1 ? 's' : '' }} sélectionné{{ selectedVinyls.size > 1 ? 's' : '' }}
                                        </span>
                                        <div class="flex items-center space-x-2">
                                            <button @click="bulkEdit"
                                                    class="px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                Éditer
                                            </button>
                                            <button @click="bulkMove"
                                                    class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                </svg>
                                                Déplacer
                                            </button>
                                            <button @click="bulkDelete"
                                                    class="px-3 py-1.5 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition-colors">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                    <button @click="clearSelection"
                                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Bouton de configuration des colonnes -->
                            <div class="absolute right-0 -top-12 z-20">
                                <button @click="showColumnSettings = !showColumnSettings"
                                        class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors"
                                        title="Configurer les colonnes">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </button>
                                
                                <!-- Dropdown de configuration des colonnes -->
                                <div v-if="showColumnSettings" 
                                     class="absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Colonnes visibles</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Glissez-déposez pour réorganiser l'ordre</p>
                                    <div class="space-y-1 max-h-64 overflow-y-auto">
                                        <div v-for="column in columns" :key="column.key"
                                             draggable="true"
                                             @dragstart="handleDragStart(column)"
                                             @dragover="handleDragOver($event, column)"
                                             @dragleave="handleDragLeave"
                                             @drop="handleDrop($event, column)"
                                             @dragend="handleDragEnd"
                                             :class="[
                                                 'flex items-center space-x-2 p-2 rounded border transition-all bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-move',
                                                 dragOverColumn?.key === column.key 
                                                     ? 'border-purple-400 bg-purple-50 dark:bg-purple-900/20' 
                                                     : '',
                                                 draggedColumn?.key === column.key 
                                                     ? 'opacity-50' 
                                                     : ''
                                             ]">
                                            <!-- Poignée de drag -->
                                            <div class="text-gray-400 hover:text-gray-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                </svg>
                                            </div>
                                            
                                            <!-- Checkbox -->
                                            <input type="checkbox"
                                                   :checked="column.enabled"
                                                   @change="toggleColumn(column.key)"
                                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                                   @click.stop>
                                            
                                            <!-- Label -->
                                            <span class="text-sm flex-1 text-gray-700 dark:text-gray-300">
                                                {{ column.label }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                                        <button @click="resetColumns"
                                                class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                            Réinitialiser
                                        </button>
                                        <button @click="showColumnSettings = false"
                                                class="text-sm bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700">
                                            Fermer
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <th v-for="column in columns.filter(c => c.enabled)" :key="column.key"
                                                :class="[
                                                    'py-3 px-4 font-medium text-gray-700 dark:text-gray-300',
                                                    column.key === 'select' ? 'w-10' : '',
                                                    getSortableColumns().includes(column.key) ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700' : ''
                                                ]"
                                                @click="column.key !== 'select' && getSortableColumns().includes(column.key) && sortByColumn(column.key)">
                                                <!-- Case à cocher "Tout sélectionner" -->
                                                <div v-if="column.key === 'select'" class="flex items-center">
                                                    <input type="checkbox"
                                                           :checked="isAllSelected"
                                                           :indeterminate="isPartiallySelected"
                                                           @change="toggleSelectAll"
                                                           class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                                </div>
                                                <!-- En-têtes de colonnes avec tri -->
                                                <div v-else-if="getSortableColumns().includes(column.key)" class="flex items-center justify-between">
                                                    <span>{{ column.label }}</span>
                                                    <div class="flex flex-col ml-1">
                                                        <svg :class="[
                                                                'w-3 h-3 transition-colors',
                                                                sortBy === column.key && sortOrder === 'asc' 
                                                                    ? 'text-purple-600' 
                                                                    : 'text-gray-400 hover:text-gray-600'
                                                             ]" 
                                                             fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                                        </svg>
                                                        <svg :class="[
                                                                'w-3 h-3 -mt-1 transition-colors',
                                                                sortBy === column.key && sortOrder === 'desc' 
                                                                    ? 'text-purple-600' 
                                                                    : 'text-gray-400 hover:text-gray-600'
                                                             ]" 
                                                             fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <!-- En-têtes de colonnes non triables -->
                                                <span v-else>{{ column.label }}</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="collectionVinyl in filteredVinyls" :key="collectionVinyl.id"
                                            class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                            
                                            <td v-for="column in columns.filter(c => c.enabled)" :key="column.key" 
                                                :class="['py-3 px-4', column.key === 'select' ? 'w-10' : '']">
                                                <!-- Sélection -->
                                                <div v-if="column.key === 'select'" class="flex items-center">
                                                    <input type="checkbox"
                                                           :checked="selectedVinyls.has(collectionVinyl.id)"
                                                           @change="toggleSelectVinyl(collectionVinyl.id)"
                                                           class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                                </div>
                                                
                                                <!-- Pochette -->
                                                <VinylImage v-else-if="column.key === 'pochette'"
                                                    :src="collectionVinyl.vinyl?.pochette"
                                                    :alt="collectionVinyl.vinyl?.vinyl_nom || 'Pochette de vinyle'"
                                                    size="sm"
                                                />
                                                
                                                <!-- Titre -->
                                                <div v-else-if="column.key === 'titre'">
                                                    <div class="font-medium text-gray-900 dark:text-white">
                                                        {{ collectionVinyl.vinyl?.vinyl_nom || 'Nom inconnu' }}
                                                    </div>
                                                    <!-- Indicateur de champs correspondants à la recherche -->
                                                    <div v-if="searchQuery && getMatchingFields(collectionVinyl.vinyl).length > 0"
                                                         class="mt-1 text-xs text-purple-600 dark:text-purple-400">
                                                        <span class="font-medium">Trouvé dans:</span>
                                                        {{ getMatchingFields(collectionVinyl.vinyl).join(', ') }}
                                                    </div>
                                                </div>
                                                
                                                <!-- Artiste -->
                                                <span v-else-if="column.key === 'artiste'" class="text-gray-700 dark:text-gray-300">
                                                    {{ collectionVinyl.vinyl?.artiste || 'Artiste inconnu' }}
                                                </span>
                                                
                                                <!-- Année -->
                                                <span v-else-if="column.key === 'annee'" class="text-gray-700 dark:text-gray-300">
                                                    {{ collectionVinyl.vinyl?.annee || '-' }}
                                                </span>
                                                
                                                <!-- Label -->
                                                <span v-else-if="column.key === 'label'" class="text-gray-700 dark:text-gray-300">
                                                    {{ collectionVinyl.vinyl?.label || '-' }}
                                                </span>
                                                
                                                <!-- Format -->
                                                <span v-else-if="column.key === 'format'" class="text-gray-700 dark:text-gray-300">
                                                    {{ collectionVinyl.vinyl?.format || '-' }}
                                                </span>
                                                
                                                <!-- Pays -->
                                                <span v-else-if="column.key === 'pays'" class="text-gray-700 dark:text-gray-300">
                                                    {{ collectionVinyl.vinyl?.pays || '-' }}
                                                </span>
                                                
                                                <!-- Genre -->
                                                <span v-else-if="column.key === 'genre'" class="text-gray-700 dark:text-gray-300">
                                                    {{ collectionVinyl.vinyl?.genre || '-' }}
                                                </span>
                                                
                                                <!-- Style -->
                                                <span v-else-if="column.key === 'style'" class="text-gray-700 dark:text-gray-300">
                                                    {{ collectionVinyl.vinyl?.style || '-' }}
                                                </span>
                                                
                                                <!-- Commentaires -->
                                                <span v-else-if="column.key === 'commentaires'" class="text-xs text-gray-600 dark:text-gray-400">
                                                    {{ collectionVinyl.commentaires || '-' }}
                                                </span>
                                                
                                                <!-- Date d'ajout -->
                                                <span v-else-if="column.key === 'date_ajout'" class="text-gray-500 dark:text-gray-400 text-xs">
                                                    {{ formatDate(collectionVinyl.date_ajout) }}
                                                </span>
                                                
                                                <!-- Actions -->
                                                <div v-else-if="column.key === 'actions'">
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
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>


                        <div v-else-if="viewMode === 'compact'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                            <div v-for="collectionVinyl in filteredVinyls" :key="collectionVinyl.id"
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

                                <div class="absolute top-2 right-2 flex flex-col gap-1 z-10 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
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
            @image-updated="handleImageUpdated"
        />

        <!-- Modal export collection -->
        <ExportCollectionModal
            :show="showExportModal"
            :collection-id="collection.id"
            :total-vinyls="pagination?.total || collection.collection_vinyls?.length || 0"
            @close="showExportModal = false"
        />

        <!-- Modal suppression collection -->
        <DeleteCollectionModal
            :show="showDeleteCollectionModal"
            :collection="collection"
            @close="closeDeleteCollectionModal"
            @confirm="confirmDeleteCollection"
        />
    </AuthenticatedLayout>
</template>
