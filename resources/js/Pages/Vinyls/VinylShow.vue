<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import YouTubeAudioPlayer from '@/Components/YouTubeAudioPlayer.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { useVinylFormats } from '@/composables/useVinylFormats';
import VinylFormatWarning from '@/Components/VinylFormatWarning.vue';

const props = defineProps({
    vinyl: {
        type: Object,
        required: true
    },
    owners: {
        type: Array,
        default: () => []
    },
    userCollections: {
        type: Array,
        default: () => []
    }
});

// État des accordéons
const expandedSections = ref({
    owners: true,
    technical: false,
    tracklist: true,
    genres: true,
    audio: true,
    gallery: false,
    notes: false,
    discogs: false
});

const toggleSection = (section) => {
    expandedSections.value[section] = !expandedSections.value[section];
};

const isManualVinyl = computed(() => {
    return props.vinyl.discogs_type === 'manual' || !props.vinyl.discogs_id;
});

// Modal d'ajout à une collection
const showAddToCollectionModal = ref(false);
const selectedCollectionId = ref(null);
const isAddingToCollection = ref(false);

const page = usePage();
const isAuthenticated = computed(() => page.props.auth?.user !== null);

// Vérifier si l'utilisateur possède déjà ce vinyle (dans au moins une collection)
const userAlreadyOwnsVinyl = computed(() => {
    if (!isAuthenticated.value) return false;
    return props.owners.some(owner => owner.user_id === page.props.auth.user.id);
});

// Obtenir les collections où l'utilisateur peut encore ajouter ce vinyle
const availableCollections = computed(() => {
    if (!isAuthenticated.value || !props.userCollections.length) return [];
    
    // Récupérer les IDs des collections où l'utilisateur a déjà ce vinyle
    const collectionsWithVinyl = props.owners
        .filter(owner => owner.user_id === page.props.auth.user.id)
        .map(owner => owner.collection.id);
    
    // Retourner les collections qui n'ont pas encore ce vinyle
    return props.userCollections.filter(collection => 
        !collectionsWithVinyl.includes(collection.id)
    );
});

const addToCollection = () => {
    if (!selectedCollectionId.value || isAddingToCollection.value) return;
    
    isAddingToCollection.value = true;
    
    router.post(route('vinyls.add-to-collection'), {
        vinyl_id: props.vinyl.id,
        collection_id: selectedCollectionId.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showAddToCollectionModal.value = false;
            selectedCollectionId.value = null;
        },
        onFinish: () => {
            isAddingToCollection.value = false;
        }
    });
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

const { getFormatLabel } = useVinylFormats();

const getYouTubeId = (url) => {
    if (!url) return null;
    const match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\?\/]+)/);
    return match ? match[1] : null;
};

const getVinylStructuredData = (vinyl) => {
    return JSON.stringify({
        "@context": "https://schema.org",
        "@type": "MusicAlbum",
        "name": vinyl.vinyl_nom,
        "byArtist": {
            "@type": "MusicGroup",
            "name": vinyl.artiste
        },
        "datePublished": vinyl.annee,
        "recordLabel": vinyl.label,
        "genre": vinyl.discogs_data?.genres || [],
        "image": vinyl.pochette,
        "offers": {
            "@type": "Offer",
            "availability": "https://schema.org/InStock"
        }
    });
};

// Ajouter les données structurées au montage
onMounted(() => {
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.textContent = getVinylStructuredData(props.vinyl);
    document.head.appendChild(script);
});
</script>

<style scoped>
.accordion-content-enter-active,
.accordion-content-leave-active {
    transition: all 0.3s ease;
    max-height: 1000px;
    overflow: hidden;
}

.accordion-content-enter-from,
.accordion-content-leave-to {
    max-height: 0;
    opacity: 0;
}

.rotate-180 {
    transform: rotate(180deg);
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>

<template>
    <Head>
        <title>{{ vinyl.vinyl_nom }} - {{ vinyl.artiste }} | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="description" :content="`${vinyl.vinyl_nom} par ${vinyl.artiste} (${vinyl.annee || 'Année inconnue'}) - ${getFormatLabel(vinyl.vinyl_format)}. ${vinyl.label ? 'Label: ' + vinyl.label + '. ' : ''}${vinyl.discogs_data?.genres?.join(', ') || ''}`" />
        <meta name="keywords" :content="`${vinyl.artiste}, ${vinyl.vinyl_nom}, vinyle, ${vinyl.discogs_data?.genres?.join(', ') || ''}`" />
        <meta property="og:title" :content="`${vinyl.vinyl_nom} - ${vinyl.artiste}`" />
        <meta property="og:description" :content="`Album vinyle ${vinyl.vinyl_nom} par ${vinyl.artiste}`" />
        <meta property="og:image" :content="vinyl.pochette" v-if="vinyl.pochette" />
        <meta property="og:type" content="music.album" />
        <meta property="music:musician" :content="vinyl.artiste" />
        <meta property="music:release_date" :content="String(vinyl.annee)" v-if="vinyl.annee" />
        <link rel="canonical" :href="route('vinyl.show', vinyl.id)" />
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <Link href="/" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                    Accueil
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
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <VinylFormatWarning :vinyl="vinyl" />
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
                                <div class="mb-4 space-y-2">
                                    <div>
                                        <span v-if="isManualVinyl" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            📝 Vinyle manuel
                                        </span>
                                        <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            🎵 Vinyle Discogs ({{ vinyl.discogs_type }})
                                        </span>
                                    </div>
                                    <div v-if="isManualVinyl && vinyl.creator" class="text-sm text-gray-600 dark:text-gray-400">
                                        Créé par <span class="font-medium text-gray-900 dark:text-white">{{ vinyl.creator.name }}</span>
                                    </div>
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

                                <!-- Bouton Ajouter à ma collection -->
                                <div v-if="isAuthenticated" class="mt-6 space-y-3">
                                    <!-- Bouton d'ajout si collections disponibles -->
                                    <div v-if="availableCollections.length > 0">
                                        <button @click="showAddToCollectionModal = true"
                                                class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            Ajouter à ma collection
                                        </button>
                                    </div>
                                    
                                    <!-- Message si déjà dans toutes les collections -->
                                    <div v-else-if="userAlreadyOwnsVinyl && userCollections.length > 0">
                                        <div class="inline-flex items-center px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 font-medium rounded-lg">
                                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Déjà dans toutes vos collections
                                        </div>
                                    </div>
                                    
                                    <!-- Message si aucune collection -->
                                    <div v-else-if="userCollections.length === 0">
                                        <Link :href="route('collections.create')"
                                              class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            Créer une collection
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Possesseurs du vinyle -->
                        <div v-if="owners.length > 0" class="border-t pt-8 mb-8">
                            <button @click="toggleSection('owners')"
                                    class="w-full flex items-center justify-between text-left mb-4 group">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    Possesseurs de ce vinyle ({{ owners.length }})
                                </h3>
                                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                     :class="{ 'rotate-180': expandedSections.owners }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <Transition name="accordion-content">
                                <div v-show="expandedSections.owners" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="owner in owners" :key="owner.id" 
                                     class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                            {{ owner.user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <Link :href="`/collectors/${owner.user.id}`" 
                                                  class="font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                                {{ owner.user.name }}
                                            </Link>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Collection: 
                                                <Link :href="route('collectors.collection', { user: owner.user.id, collection: owner.collection.id })" 
                                                      class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                    {{ owner.collection.collection_nom }}
                                                </Link>
                                            </p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span v-if="owner.quantite > 1" class="px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded-full text-xs font-medium">
                                                    x{{ owner.quantite }} exemplaires
                                                </span>
                                                <span v-if="owner.note" class="text-xs text-gray-400 dark:text-gray-500">
                                                    ⭐ {{ owner.note }}/10
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </Transition>
                        </div>

                        <!-- Détails techniques -->
                        <div v-if="vinyl.reference || vinyl.pays || vinyl.specificite || vinyl.refMatrice || vinyl.distribution || vinyl.edition || vinyl.anneeOriginal" 
                             class="border-t pt-8">
                            <button @click="toggleSection('technical')"
                                    class="w-full flex items-center justify-between text-left mb-4 group">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    Détails techniques
                                </h3>
                                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                     :class="{ 'rotate-180': expandedSections.technical }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <Transition name="accordion-content">
                                <div v-show="expandedSections.technical">
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
                            </Transition>
                        </div>

                        <!-- Liste des pistes -->
                        <div v-if="vinyl.tracks || vinyl.discogs_data?.tracklist?.length > 0" class="border-t pt-8">
                            <button @click="toggleSection('tracklist')"
                                    class="w-full flex items-center justify-between text-left mb-4 group">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    Liste des pistes {{ vinyl.discogs_data?.tracklist?.length ? `(${vinyl.discogs_data.tracklist.length})` : '' }}
                                </h3>
                                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                     :class="{ 'rotate-180': expandedSections.tracklist }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <Transition name="accordion-content">
                                <div v-show="expandedSections.tracklist">
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
                            </Transition>
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

                        <!-- Audio disponible (Discogs) -->
                        <div v-if="vinyl.discogs_data?.videos?.length > 0" class="border-t pt-8">
                            <button @click="toggleSection('audio')"
                                    class="w-full flex items-center justify-between text-left mb-4 group">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    Pistes audio disponibles ({{ vinyl.discogs_data.videos.length }})
                                </h3>
                                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                     :class="{ 'rotate-180': expandedSections.audio }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <Transition name="accordion-content">
                                <div v-show="expandedSections.audio" class="space-y-4">
                                <div v-for="(audio, index) in vinyl.discogs_data.videos" :key="index">
                                    <!-- Lecteur audio custom avec lien YouTube intégré -->
                                    <YouTubeAudioPlayer 
                                        v-if="getYouTubeId(audio.uri)"
                                        :video-id="getYouTubeId(audio.uri)"
                                        :title="audio.title">
                                        <template #extra-actions>
                                            <!-- Lien YouTube intégré dans la carte -->
                                            <a :href="audio.uri"
                                               target="_blank"
                                               class="flex-shrink-0 p-1.5 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-full transition-colors"
                                               title="Ouvrir sur YouTube">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M23.498 6.186a2.86 2.86 0 0 0-2.016-2.027C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.482.614A2.86 2.86 0 0 0 .502 6.186C-.003 8.175-.003 12.545-.003 12.545s0 4.37.505 6.359a2.86 2.86 0 0 0 2.016 2.027c1.977.614 9.482.614 9.482.614s7.505 0 9.482-.614a2.86 2.86 0 0 0 2.016-2.027c.505-1.989.505-6.359.505-6.359s0-4.37-.505-6.359z"/>
                                                    <path fill="white" d="m9.545 15.568 6.364-3.023-6.364-3.023v6.046z"/>
                                                </svg>
                                            </a>
                                        </template>
                                    </YouTubeAudioPlayer>
                                        
                                    <!-- Message si pas d'ID YouTube valide -->
                                    <div v-else class="bg-gray-50 dark:bg-gray-600 rounded-lg p-3">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center text-gray-500 dark:text-gray-400">
                                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-sm">{{ audio.title }}</span>
                                            </div>
                                            <a :href="audio.uri"
                                               target="_blank"
                                               class="flex-shrink-0 p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-full transition-colors"
                                               title="Ouvrir sur YouTube">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M23.498 6.186a2.86 2.86 0 0 0-2.016-2.027C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.482.614A2.86 2.86 0 0 0 .502 6.186C-.003 8.175-.003 12.545-.003 12.545s0 4.37.505 6.359a2.86 2.86 0 0 0 2.016 2.027c1.977.614 9.482.614 9.482.614s7.505 0 9.482-.614a2.86 2.86 0 0 0 2.016-2.027c.505-1.989.505-6.359.505-6.359s0-4.37-.505-6.359z"/>
                                                    <path fill="white" d="m9.545 15.568 6.364-3.023-6.364-3.023v6.046z"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </Transition>
                        </div>

                        <!-- Galerie d'images (Discogs) -->
                        <div v-if="vinyl.discogs_data?.images?.length > 1" class="border-t pt-8">
                            <button @click="toggleSection('gallery')"
                                    class="w-full flex items-center justify-between text-left mb-4 group">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    Galerie d'images ({{ vinyl.discogs_data.images.length }})
                                </h3>
                                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                     :class="{ 'rotate-180': expandedSections.gallery }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <Transition name="accordion-content">
                                <div v-show="expandedSections.gallery" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
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
                            </Transition>
                        </div>

                        <!-- Notes et informations supplémentaires (Discogs) -->
                        <div v-if="vinyl.discogs_data?.notes" class="border-t pt-8">
                            <button @click="toggleSection('notes')"
                                    class="w-full flex items-center justify-between text-left mb-4 group">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    Notes additionnelles
                                </h3>
                                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                     :class="{ 'rotate-180': expandedSections.notes }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <Transition name="accordion-content">
                                <div v-show="expandedSections.notes" class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                                    <p class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ vinyl.discogs_data.notes }}</p>
                                </div>
                            </Transition>
                        </div>

                        <!-- Info Discogs -->
                        <div v-if="!isManualVinyl" class="border-t pt-8">
                            <button @click="toggleSection('discogs')"
                                    class="w-full flex items-center justify-between text-left mb-4 group">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    Informations Discogs
                                </h3>
                                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                     :class="{ 'rotate-180': expandedSections.discogs }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <Transition name="accordion-content">
                                <div v-show="expandedSections.discogs" class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
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
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal d'ajout à une collection -->
        <Transition name="modal">
            <div v-if="showAddToCollectionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Fond sombre -->
                <div class="fixed inset-0 bg-black bg-opacity-50" @click="showAddToCollectionModal = false"></div>
                
                <!-- Contenu de la modal -->
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Ajouter à une collection
                    </h3>
                    
                    <div v-if="availableCollections.length > 0">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Sélectionnez la collection dans laquelle ajouter ce vinyle :
                        </p>
                        
                        <div class="space-y-2 mb-6">
                            <label v-for="collection in availableCollections" :key="collection.id"
                                   class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors">
                                <input type="radio" 
                                       :value="collection.id" 
                                       v-model="selectedCollectionId"
                                       class="w-4 h-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                                <span class="ml-3 text-gray-900 dark:text-white">{{ collection.collection_nom }}</span>
                            </label>
                        </div>
                        
                        <!-- Afficher les collections où le vinyle est déjà présent -->
                        <div v-if="userAlreadyOwnsVinyl" class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Déjà dans :</p>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="owner in props.owners.filter(o => o.user_id === page.props.auth.user.id)" 
                                      :key="owner.collection.id"
                                      class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-xs rounded-full">
                                    {{ owner.collection.collection_nom }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else>
                        <div v-if="userAlreadyOwnsVinyl" class="text-center">
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                Ce vinyle est déjà dans toutes vos collections disponibles.
                            </p>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Présent dans :</p>
                                <div class="flex flex-wrap gap-2 justify-center">
                                    <span v-for="owner in props.owners.filter(o => o.user_id === page.props.auth.user.id)" 
                                          :key="owner.collection.id"
                                          class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-xs rounded-full">
                                        {{ owner.collection.collection_nom }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                Vous n'avez pas encore de collection. Créez-en une d'abord !
                            </p>
                            <Link :href="route('collections.create')"
                                  class="text-purple-600 hover:text-purple-700 dark:text-purple-400 font-medium">
                                Créer une collection →
                            </Link>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button @click="showAddToCollectionModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            Annuler
                        </button>
                        <button v-if="availableCollections.length > 0"
                                @click="addToCollection"
                                :disabled="!selectedCollectionId || isAddingToCollection"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50 text-white font-medium rounded-lg transition-colors">
                            {{ isAddingToCollection ? 'Ajout...' : 'Ajouter' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>