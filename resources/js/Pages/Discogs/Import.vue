<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const page = usePage();

const props = defineProps({
    isConnected: Boolean,
    discogsUsername: String,
    connectedAt: String,
    folders: {
        type: Array,
        default: () => []
    },
    collections: {
        type: Array,
        default: () => []
    },
    folderMappings: {
        type: Array,
        default: () => []
    },
    recentImports: {
        type: Object,
        default: () => ({ data: [] })
    },
    activeImport: Object,
    folderError: String,
});

// Flash messages
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Import form
const importForm = useForm({
    folder_id: null,
    folder_name: '',
    collection_id: null,
    new_collection_name: '',
});

const selectedFolder = ref(null);
const createNewCollection = ref(false);
const folderItemCount = ref(null);
const loadingPreview = ref(false);

// Active import polling
const pollingInterval = ref(null);
const currentImportStatus = ref(props.activeImport);

// Get mapping for a folder
function getFolderMapping(folderId) {
    return props.folderMappings.find(m => m.discogs_folder_id === folderId);
}

// Select a folder
async function selectFolder(folder) {
    selectedFolder.value = folder;
    importForm.folder_id = folder.id;
    importForm.folder_name = folder.name;
    folderItemCount.value = null;

    // Check if there's an existing mapping
    const mapping = getFolderMapping(folder.id);
    if (mapping) {
        importForm.collection_id = mapping.collection_id;
        createNewCollection.value = false;
    } else {
        importForm.collection_id = null;
        importForm.new_collection_name = folder.name;
        createNewCollection.value = true;
    }

    // Fetch preview count
    loadingPreview.value = true;
    try {
        const response = await fetch(`/discogs/folder-preview?folder_id=${folder.id}`);
        const data = await response.json();
        folderItemCount.value = data.total_items;
    } catch (e) {
        console.error('Failed to load folder preview', e);
    } finally {
        loadingPreview.value = false;
    }
}

// Start import
function startImport() {
    if (createNewCollection.value) {
        importForm.collection_id = null;
    } else {
        importForm.new_collection_name = '';
    }

    importForm.post(route('discogs.start-import'), {
        onSuccess: () => {
            selectedFolder.value = null;
            startPolling();
        }
    });
}

// Poll for import status
async function pollImportStatus() {
    if (!currentImportStatus.value) return;

    try {
        const response = await fetch(`/discogs/import/${currentImportStatus.value.id}/status`);
        const data = await response.json();
        currentImportStatus.value = data;

        if (data.status === 'completed' || data.status === 'failed') {
            stopPolling();
            // Refresh page to get updated data
            router.reload({ only: ['recentImports', 'activeImport', 'folderMappings'] });
        }
    } catch (e) {
        console.error('Failed to poll import status', e);
    }
}

function startPolling() {
    if (pollingInterval.value) return;
    pollingInterval.value = setInterval(pollImportStatus, 2000);
}

function stopPolling() {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Status badge classes
function getStatusClasses(status) {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'processing':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'failed':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
}

function getStatusLabel(status) {
    switch (status) {
        case 'completed': return 'Terminé';
        case 'processing': return 'En cours';
        case 'pending': return 'En attente';
        case 'failed': return 'Échec';
        default: return status;
    }
}

onMounted(() => {
    if (props.activeImport && ['pending', 'processing'].includes(props.activeImport.status)) {
        currentImportStatus.value = props.activeImport;
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <Head title="Import Discogs" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link href="/collections" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm mb-2 inline-block">
                        &larr; Retour aux collections
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Import Discogs
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Flash Messages -->
                <div v-if="flashSuccess" class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-green-800 dark:text-green-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ flashSuccess }}
                    </div>
                </div>

                <div v-if="flashError" class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-red-800 dark:text-red-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ flashError }}
                    </div>
                </div>

                <!-- Connection Status Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-900 rounded-lg flex items-center justify-center">
                                <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-2-8c0 1.1.9 2 2 2s2-.9 2-2-.9-2-2-2-2 .9-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    Compte Discogs
                                </h3>
                                <p v-if="isConnected" class="text-sm text-gray-600 dark:text-gray-400">
                                    Connecté en tant que <span class="font-medium">{{ discogsUsername }}</span>
                                    <span class="text-gray-400"> &middot; </span>
                                    <span class="text-xs">depuis le {{ formatDate(connectedAt) }}</span>
                                </p>
                                <p v-else class="text-sm text-gray-600 dark:text-gray-400">
                                    Connectez votre compte Discogs pour importer vos collections
                                </p>
                            </div>
                        </div>
                        <div>
                            <a
                                v-if="!isConnected"
                                :href="route('discogs.connect')"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                Connecter Discogs
                            </a>
                            <button
                                v-else
                                @click="router.post(route('discogs.disconnect'))"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Déconnecter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Info Box - How sync works -->
                <div v-if="isConnected" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-6">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm text-amber-800 dark:text-amber-200">
                            <p class="font-medium mb-1">Comment fonctionne la synchronisation ?</p>
                            <ul class="list-disc list-inside space-y-1 text-amber-700 dark:text-amber-300">
                                <li>L'import crée une <strong>synchronisation bidirectionnelle</strong> entre votre dossier Discogs et votre collection</li>
                                <li>Les nouveaux vinyles ajoutés sur Discogs seront importés lors du prochain sync</li>
                                <li>Les vinyles <strong>supprimés sur Discogs</strong> seront également <strong>supprimés de votre collection</strong></li>
                                <li>Les vinyles ajoutés manuellement dans votre collection (sans passer par Discogs) ne seront jamais affectés</li>
                                <li>Un délai de 30 minutes est requis entre chaque synchronisation d'un même dossier</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Active Import Progress -->
                <div v-if="currentImportStatus && ['pending', 'processing'].includes(currentImportStatus.status)" class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent"></div>
                        <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100">
                            Import en cours...
                        </h3>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm text-blue-800 dark:text-blue-200">
                            <span>Progression</span>
                            <span>{{ currentImportStatus.processed_items }} / {{ currentImportStatus.total_items }}</span>
                        </div>
                        <div class="w-full bg-blue-200 dark:bg-blue-800 rounded-full h-3">
                            <div
                                class="bg-blue-600 h-3 rounded-full transition-all duration-300"
                                :style="{ width: `${currentImportStatus.progress_percentage}%` }"
                            ></div>
                        </div>
                        <div class="flex gap-4 text-sm text-blue-700 dark:text-blue-300">
                            <span>Importés: {{ currentImportStatus.imported_items }}</span>
                            <span>Ignorés: {{ currentImportStatus.skipped_items }}</span>
                            <span v-if="currentImportStatus.removed_items > 0" class="text-orange-600 dark:text-orange-400">
                                Supprimés: {{ currentImportStatus.removed_items }}
                            </span>
                            <span v-if="currentImportStatus.errors_count > 0" class="text-red-600 dark:text-red-400">
                                Erreurs: {{ currentImportStatus.errors_count }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Folder Error -->
                <div v-if="folderError" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                    <p class="text-red-800 dark:text-red-200">{{ folderError }}</p>
                </div>

                <!-- Main Content -->
                <div v-if="isConnected" class="grid lg:grid-cols-2 gap-6">
                    <!-- Folders List -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Vos dossiers Discogs
                        </h3>

                        <div v-if="folders.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Aucun dossier trouvé
                        </div>

                        <div v-else class="space-y-2">
                            <button
                                v-for="folder in folders"
                                :key="folder.id"
                                @click="selectFolder(folder)"
                                :class="[
                                    'w-full text-left p-4 rounded-lg border-2 transition',
                                    selectedFolder?.id === folder.id
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ folder.name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ folder.count }} vinyles
                                        </div>
                                    </div>
                                    <div v-if="getFolderMapping(folder.id)" class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Synchronisé
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Import Configuration -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Configuration de l'import
                        </h3>

                        <div v-if="!selectedFolder" class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            <p>Sélectionnez un dossier Discogs à importer</p>
                        </div>

                        <div v-else class="space-y-6">
                            <!-- Selected folder info -->
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Dossier sélectionné</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ selectedFolder.name }}</div>
                                <div v-if="loadingPreview" class="text-sm text-gray-500 mt-1">
                                    Chargement...
                                </div>
                                <div v-else-if="folderItemCount !== null" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ folderItemCount }} vinyles à importer
                                </div>
                            </div>

                            <!-- Collection selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Collection de destination
                                </label>

                                <div class="space-y-3">
                                    <label class="flex items-center gap-3">
                                        <input
                                            type="radio"
                                            :value="false"
                                            v-model="createNewCollection"
                                            class="w-4 h-4 text-blue-600"
                                        />
                                        <span class="text-gray-700 dark:text-gray-300">Collection existante</span>
                                    </label>

                                    <select
                                        v-if="!createNewCollection"
                                        v-model="importForm.collection_id"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option :value="null" disabled>Choisir une collection...</option>
                                        <option v-for="col in collections" :key="col.id" :value="col.id">
                                            {{ col.collection_nom }}
                                        </option>
                                    </select>

                                    <label class="flex items-center gap-3">
                                        <input
                                            type="radio"
                                            :value="true"
                                            v-model="createNewCollection"
                                            class="w-4 h-4 text-blue-600"
                                        />
                                        <span class="text-gray-700 dark:text-gray-300">Créer une nouvelle collection</span>
                                    </label>

                                    <input
                                        v-if="createNewCollection"
                                        type="text"
                                        v-model="importForm.new_collection_name"
                                        placeholder="Nom de la nouvelle collection"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>
                            </div>

                            <!-- Import button -->
                            <button
                                @click="startImport"
                                :disabled="importForm.processing || (!importForm.collection_id && !importForm.new_collection_name) || currentImportStatus"
                                class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-medium rounded-lg transition flex items-center justify-center gap-2"
                            >
                                <svg v-if="importForm.processing" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Lancer l'import
                            </button>

                            <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                L'import s'effectue en arrière-plan. Vous pouvez quitter cette page.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Recent Imports -->
                <div v-if="recentImports.data?.length > 0" class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Historique des imports
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                    <th class="pb-3 font-medium">Date</th>
                                    <th class="pb-3 font-medium">Collection</th>
                                    <th class="pb-3 font-medium">Statut</th>
                                    <th class="pb-3 font-medium text-right">Importés</th>
                                    <th class="pb-3 font-medium text-right">Ignorés</th>
                                    <th class="pb-3 font-medium text-right">Supprimés</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="imp in recentImports.data" :key="imp.id">
                                    <td class="py-3 text-gray-900 dark:text-white">
                                        {{ formatDate(imp.created_at) }}
                                    </td>
                                    <td class="py-3 text-gray-600 dark:text-gray-400">
                                        {{ imp.collection?.collection_nom || '-' }}
                                    </td>
                                    <td class="py-3">
                                        <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusClasses(imp.status)]">
                                            {{ getStatusLabel(imp.status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right text-gray-900 dark:text-white">
                                        {{ imp.imported_items }}
                                    </td>
                                    <td class="py-3 text-right text-gray-500 dark:text-gray-400">
                                        {{ imp.skipped_items }}
                                    </td>
                                    <td class="py-3 text-right text-orange-600 dark:text-orange-400">
                                        {{ imp.removed_items || 0 }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="recentImports.last_page > 1" class="mt-4 flex items-center justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Page {{ recentImports.current_page }} sur {{ recentImports.last_page }}
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-if="recentImports.prev_page_url"
                                :href="recentImports.prev_page_url"
                                preserve-state
                                preserve-scroll
                                class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded transition"
                            >
                                Précédent
                            </Link>
                            <Link
                                v-if="recentImports.next_page_url"
                                :href="recentImports.next_page_url"
                                preserve-state
                                preserve-scroll
                                class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded transition"
                            >
                                Suivant
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
