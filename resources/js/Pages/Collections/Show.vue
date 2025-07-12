<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    collection: {
        type: Object,
        required: true
    }
});

// États de la modal
const showVinylModal = ref(false);
const discogsQuery = ref('');
const discogsResults = ref([]);
const isSearchingDiscogs = ref(false);

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

                <!-- Liste des vinyles -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Vinyles de la collection
                        </h3>
                        
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

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="collectionVinyl in collection.collection_vinyls" :key="collectionVinyl.id"
                                 class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <div class="flex items-start space-x-4">
                                    <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0">
                                        <img v-if="collectionVinyl.vinyl?.pochette" 
                                             :src="collectionVinyl.vinyl.pochette" 
                                             :alt="collectionVinyl.vinyl.vinyl_nom"
                                             class="w-full h-full object-cover">
                                        <div v-else class="w-full h-full bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                            </svg>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-gray-900 dark:text-white text-sm">
                                            {{ collectionVinyl.vinyl?.vinyl_nom || 'Nom inconnu' }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ collectionVinyl.vinyl?.artiste || 'Artiste inconnu' }}
                                        </p>
                                        <div class="flex items-center mt-2 space-x-4 text-xs text-gray-400">
                                            <span v-if="collectionVinyl.prix_achat">
                                                {{ collectionVinyl.prix_achat }}€
                                            </span>
                                            <span v-if="collectionVinyl.note">
                                                ⭐ {{ collectionVinyl.note }}/10
                                            </span>
                                            <span>
                                                Ajouté le {{ formatDate(collectionVinyl.date_ajout) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-if="collectionVinyl.commentaires" class="mt-3 text-xs text-gray-600 dark:text-gray-300">
                                    {{ collectionVinyl.commentaires }}
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
    </AuthenticatedLayout>
</template>