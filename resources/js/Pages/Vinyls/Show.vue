<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    collectionVinyl: {
        type: Object,
        required: true
    },
    isOwner: {
        type: Boolean,
        default: false
    },
    canEdit: {
        type: Boolean,
        default: false
    }
});

const vinyl = props.collectionVinyl.vinyl;
const collection = props.collectionVinyl.collection;

const isManualVinyl = computed(() => {
    return vinyl.discogs_type === 'manual' || !vinyl.discogs_id;
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

const getFormatLabel = (format) => {
    const formats = {
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
    return formats[format] || 'Format inconnu';
};

const getProvenanceLabel = (provenance) => {
    const provenances = {
        0: 'Inconnue',
        1: 'Magasin de disques',
        2: 'Marché aux puces',
        3: 'Internet',
        4: 'Don',
        5: 'Échange',
        6: 'Concert',
        7: 'Autre'
    };
    return provenances[provenance] || 'Inconnue';
};
</script>

<template>
    <Head>
        <title>{{ vinyl.vinyl_nom }} - {{ vinyl.artiste }} | Ma Collection de Vinyles</title>
        <meta name="description" :content="`${vinyl.vinyl_nom} par ${vinyl.artiste} (${vinyl.annee || 'Année inconnue'}) - ${getFormatLabel(vinyl.vinyl_format)}. ${vinyl.label ? 'Label: ' + vinyl.label + '. ' : ''}${vinyl.discogs_data?.genres?.join(', ') || ''}`" />
        <meta property="og:title" :content="`${vinyl.vinyl_nom} - ${vinyl.artiste}`" />
        <meta property="og:description" :content="`Album vinyle ${vinyl.vinyl_nom} par ${vinyl.artiste}`" />
        <meta property="og:image" :content="vinyl.pochette" v-if="vinyl.pochette" />
        <meta property="og:type" content="music.album" />
        <meta property="music:musician" :content="vinyl.artiste" />
        <meta property="music:release_date" :content="String(vinyl.annee)" v-if="vinyl.annee" />
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <Link :href="route('vinyls.index')" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                    Mes Vinyles
                                </Link>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                    </svg>
                                    <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">{{ vinyl.vinyl_nom }}</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                    <h2 class="mt-2 text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        {{ vinyl.vinyl_nom }}
                    </h2>
                </div>
                <div class="flex space-x-3">
                    <Link v-if="canEdit" 
                          :href="route('vinyls.edit', collectionVinyl.id)" 
                          class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
                        Éditer
                    </Link>
                    <Link :href="isOwner ? route('vinyls.index') : route('collectors.collection', { user: collectionVinyl.user_id, collection: collection.id })" 
                          class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md transition-colors">
                        Retour
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header avec image et infos principales -->
                        <div class="flex flex-col lg:flex-row gap-8 mb-8">
                            <!-- Image de pochette -->
                            <div class="w-full lg:w-80 flex-shrink-0">
                                <div class="aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                                    <img v-if="vinyl.pochette" 
                                         :src="vinyl.pochette" 
                                         :alt="vinyl.vinyl_nom"
                                         class="w-full h-full object-cover">
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <svg class="w-24 h-24 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Informations principales -->
                            <div class="flex-1">
                                <div class="mb-4">
                                    <span v-if="isManualVinyl" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        📝 Vinyle manuel
                                    </span>
                                    <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                        🎵 Vinyle Discogs ({{ vinyl.discogs_type }})
                                    </span>
                                </div>

                                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                    {{ vinyl.vinyl_nom }}
                                </h1>
                                
                                <h2 v-if="vinyl.vinyl_titre && vinyl.vinyl_titre !== vinyl.vinyl_nom" 
                                    class="text-xl text-gray-600 dark:text-gray-400 mb-4">
                                    {{ vinyl.vinyl_titre }}
                                </h2>

                                <div class="space-y-2 mb-6">
                                    <p class="text-lg">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Artiste:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.artiste }}</span>
                                    </p>
                                    
                                    <p v-if="vinyl.label" class="text-lg">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Label:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.label }}</span>
                                    </p>
                                    
                                    <p class="text-lg">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Format:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ getFormatLabel(vinyl.vinyl_format) }}</span>
                                    </p>
                                    
                                    <p v-if="vinyl.annee" class="text-lg">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Année de sortie:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.annee }}</span>
                                    </p>
                                </div>

                                <!-- Informations collection -->
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">
                                        Ma collection
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Collection:</span>
                                            <Link :href="route('collections.show', collection.id)" 
                                                  class="ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                {{ collection.collection_nom }}
                                            </Link>
                                        </div>
                                        <div v-if="collectionVinyl.prix_achat">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Prix d'achat:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ collectionVinyl.prix_achat }}€</span>
                                        </div>
                                        <div v-if="collectionVinyl.annee_achat">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Année d'achat:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ collectionVinyl.annee_achat }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Provenance:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ getProvenanceLabel(collectionVinyl.provenance) }}</span>
                                        </div>
                                        <div v-if="collectionVinyl.note">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Note:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ collectionVinyl.note }}/10 ⭐</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Ajouté le:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ formatDate(collectionVinyl.date_ajout) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Commentaires -->
                                <div v-if="collectionVinyl.commentaires" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Mes commentaires
                                    </h3>
                                    <p class="text-gray-700 dark:text-gray-300">{{ collectionVinyl.commentaires }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Détails techniques (plus pour les vinyles manuels) -->
                        <div v-if="vinyl.reference || vinyl.pays || vinyl.specificite || vinyl.refMatrice || vinyl.distribution || vinyl.edition || vinyl.anneeOriginal" 
                             class="border-t pt-8">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                Détails techniques
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div v-if="vinyl.reference">
                                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-1">Référence</h4>
                                    <p class="text-gray-900 dark:text-white">{{ vinyl.reference }}</p>
                                </div>
                                
                                <div v-if="vinyl.pays">
                                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-1">Pays</h4>
                                    <p class="text-gray-900 dark:text-white">{{ vinyl.pays }}</p>
                                </div>
                                
                                <div v-if="vinyl.edition">
                                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-1">Édition</h4>
                                    <p class="text-gray-900 dark:text-white">{{ vinyl.edition }}</p>
                                </div>
                                
                                <div v-if="vinyl.anneeOriginal">
                                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-1">Année originale</h4>
                                    <p class="text-gray-900 dark:text-white">{{ vinyl.anneeOriginal }}</p>
                                </div>
                                
                                <div v-if="vinyl.refMatrice">
                                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-1">Référence matrice</h4>
                                    <p class="text-gray-900 dark:text-white">{{ vinyl.refMatrice }}</p>
                                </div>
                                
                                <div v-if="vinyl.distribution">
                                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-1">Distribution</h4>
                                    <p class="text-gray-900 dark:text-white">{{ vinyl.distribution }}</p>
                                </div>
                            </div>
                            
                            <div v-if="vinyl.specificite" class="mt-6">
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Spécificités</h4>
                                <p class="text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 rounded-lg p-3">{{ vinyl.specificite }}</p>
                            </div>
                        </div>

                        <!-- Liste des pistes -->
                        <div v-if="vinyl.tracks || vinyl.discogs_data?.tracklist?.length > 0" class="border-t pt-8">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                Liste des pistes
                            </h3>
                            <!-- Tracklist Discogs structurée -->
                            <div v-if="vinyl.discogs_data?.tracklist?.length > 0" class="space-y-2">
                                <div v-for="(track, index) in vinyl.discogs_data.tracklist" :key="index"
                                     class="flex items-start space-x-4 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <span class="font-medium text-gray-500 dark:text-gray-400 min-w-[3rem]">
                                        {{ track.position || index + 1 }}
                                    </span>
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 dark:text-white">
                                            {{ track.title }}
                                        </h4>
                                        <div v-if="track.extraartists" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            <span v-for="(artist, idx) in track.extraartists" :key="idx">
                                                {{ artist.name }} ({{ artist.role }})<span v-if="idx < track.extraartists.length - 1">, </span>
                                            </span>
                                        </div>
                                    </div>
                                    <span v-if="track.duration" class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ track.duration }}
                                    </span>
                                </div>
                            </div>
                            <!-- Tracklist manuelle (texte brut) -->
                            <div v-else-if="vinyl.tracks" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <pre class="whitespace-pre-wrap text-gray-900 dark:text-white font-mono text-sm">{{ vinyl.tracks }}</pre>
                            </div>
                        </div>

                        <!-- Genres et Styles (Discogs) -->
                        <div v-if="vinyl.discogs_data?.genres?.length > 0 || vinyl.discogs_data?.styles?.length > 0" class="border-t pt-8">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                Genres et Styles
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="genre in vinyl.discogs_data.genres" :key="'genre-' + genre"
                                      class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200 rounded-full text-sm font-medium">
                                    {{ genre }}
                                </span>
                                <span v-for="style in vinyl.discogs_data.styles" :key="'style-' + style"
                                      class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded-full text-sm">
                                    {{ style }}
                                </span>
                            </div>
                        </div>

                        <!-- Vidéos (Discogs) -->
                        <div v-if="vinyl.discogs_data?.videos?.length > 0" class="border-t pt-8">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                Vidéos disponibles
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <a v-for="(video, index) in vinyl.discogs_data.videos" :key="index"
                                   :href="video.uri"
                                   target="_blank"
                                   class="flex items-start space-x-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"></path>
                                    </svg>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-gray-900 dark:text-white truncate">
                                            {{ video.title }}
                                        </h4>
                                        <p v-if="video.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mt-1">
                                            {{ video.description }}
                                        </p>
                                        <p v-if="video.duration" class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                            Durée: {{ Math.floor(video.duration / 60) }}:{{ String(video.duration % 60).padStart(2, '0') }}
                                        </p>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Galerie d'images (Discogs) -->
                        <div v-if="vinyl.discogs_data?.images?.length > 1" class="border-t pt-8">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                Galerie d'images
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <a v-for="(image, index) in vinyl.discogs_data.images" :key="index"
                                   :href="image.uri"
                                   target="_blank"
                                   class="relative aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 hover:opacity-90 transition-opacity">
                                    <img :src="image.uri150 || image.uri"
                                         :alt="`Image ${index + 1} du vinyle`"
                                         class="w-full h-full object-cover"
                                         loading="lazy">
                                    <span v-if="image.type" class="absolute bottom-2 left-2 px-2 py-1 bg-black/70 text-white text-xs rounded">
                                        {{ image.type }}
                                    </span>
                                </a>
                            </div>
                        </div>

                        <!-- Notes et informations supplémentaires (Discogs) -->
                        <div v-if="vinyl.discogs_data?.notes" class="border-t pt-8">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                Notes additionnelles
                            </h3>
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                                <p class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ vinyl.discogs_data.notes }}</p>
                            </div>
                        </div>

                        <!-- Info Discogs -->
                        <div v-if="!isManualVinyl" class="border-t pt-8">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                Informations Discogs
                            </h3>
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">ID Discogs:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.discogs_id }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Type:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white capitalize">{{ vinyl.discogs_type }}</span>
                                    </div>
                                    <div v-if="vinyl.discogs_data?.country">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Pays de sortie:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.discogs_data.country }}</span>
                                    </div>
                                    <div v-if="vinyl.discogs_data?.released">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Date de sortie:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.discogs_data.released }}</span>
                                    </div>
                                    <div v-if="vinyl.discogs_data?.data_quality">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Qualité des données:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.discogs_data.data_quality }}</span>
                                    </div>
                                    <div class="md:col-span-2">
                                        <a :href="`https://www.discogs.com/${vinyl.discogs_type}/${vinyl.discogs_id}`" 
                                           target="_blank" 
                                           class="inline-flex items-center text-purple-600 hover:text-purple-800 dark:text-purple-400">
                                            <span>Voir sur Discogs</span>
                                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>