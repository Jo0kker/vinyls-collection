<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import VinylImage from '@/Components/VinylImage.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    collection: {
        type: Object,
        required: true
    }
});

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
</script>

<template>
    <Head :title="`${collection.collection_nom} - ${user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link :href="`/collectors/${user.id}`" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                        ← Retour au profil de {{ user.name }}
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        {{ collection.collection_nom }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Informations de la collection -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                    {{ collection.collection_nom }}
                                </h1>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Collection de <Link :href="`/collectors/${user.id}`" class="text-purple-600 hover:text-purple-800">{{ user.name }}</Link>
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
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

                <!-- Mode d'affichage -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Vinyles de la collection
                            </h3>
                            
                            <!-- Sélecteur de mode d'affichage -->
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
                        </div>
                    </div>
                </div>

                <!-- Liste des vinyles -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
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
                        </div>

                        <!-- Affichage grille -->
                        <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="collectionVinyl in collection.collection_vinyls" :key="collectionVinyl.id"
                                 class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 transition-colors">
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
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Affichage compact - Cards avec image overlay -->
                        <div v-else-if="viewMode === 'compact'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                            <div v-for="collectionVinyl in collection.collection_vinyls" :key="collectionVinyl.id"
                                 class="relative aspect-square rounded-lg overflow-hidden hover:scale-105 transition-transform shadow-md">
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>