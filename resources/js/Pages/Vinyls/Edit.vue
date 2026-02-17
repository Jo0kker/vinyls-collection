<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    collectionVinyl: {
        type: Object,
        required: true
    },
    collections: {
        type: Array,
        default: () => []
    },
    canEditVinyl: {
        type: Boolean,
        default: false
    },
    canEditInstance: {
        type: Boolean,
        default: true
    },
    vinylCreator: {
        type: Object,
        default: null
    }
});

const vinyl = props.collectionVinyl.vinyl;
const isManualVinyl = computed(() => !vinyl.discogs_id);
const isDiscogsVinyl = computed(() => !isManualVinyl.value);
const canEditVinylFields = computed(() => props.canEditVinyl);
const canEditInstanceFields = computed(() => props.canEditInstance);

const form = useForm({
    // Champs modifiables pour tous les vinyles
    collection_id: props.collectionVinyl.collection_id,
    prix_achat: props.collectionVinyl.prix_achat || null,
    annee_achat: props.collectionVinyl.annee_achat || null,
    provenance: props.collectionVinyl.provenance || 0,
    commentaires: props.collectionVinyl.commentaires || '',
    note: props.collectionVinyl.note || null,
    quantite: props.collectionVinyl.quantite || 1,
    
    // Champs vinyles - basiques pour Discogs (avec valeurs par défaut)
    vinyl_nom: vinyl.vinyl_nom || '',
    vinyl_titre: vinyl.vinyl_titre || '',
    vinyl_format: vinyl.vinyl_format || 1,
    
    // Champs additionnels pour les vinyles manuels
    artiste: vinyl.artiste || '',
    label: vinyl.label || '',
    reference: vinyl.reference || '',
    annee: vinyl.annee || null,
    pays: vinyl.pays || null,
    tracks: vinyl.tracks || '',
    specificite: vinyl.specificite || '',
    refMatrice: vinyl.refMatrice || '',
    distribution: vinyl.distribution || '',
    edition: vinyl.edition || null,
    anneeOriginal: vinyl.anneeOriginal || null,
    pochette_url: vinyl.pochette || '',
    pochette_file: null
});

const selectedImage = ref(null);
const imagePreview = ref(vinyl.pochette);

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.pochette_file = file;
        
        // Preview
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const clearImage = () => {
    form.pochette_file = null;
    form.pochette_url = '';
    imagePreview.value = null;
    // Reset file input
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) fileInput.value = '';
};

const submit = () => {
    // Use POST with _method field to simulate PUT for file uploads
    form.transform((data) => ({
        ...data,
        _method: 'PUT'
    })).post(route('vinyls.update', props.collectionVinyl.id), {
        forceFormData: true,
        onSuccess: () => {
            // Redirect handled by controller
        }
    });
};

// Modal de duplication
const showDuplicateModal = ref(false);

const duplicateVinyl = () => {
    showDuplicateModal.value = true;
};

const confirmDuplicate = () => {
    router.post(route('vinyls.duplicate', props.collectionVinyl.id), {}, {
        preserveScroll: false,
        onSuccess: () => {
            // La redirection vers le nouveau vinyle est gérée par le contrôleur
        }
    });
    showDuplicateModal.value = false;
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
</script>

<template>
    <Head :title="`Éditer ${vinyl.vinyl_nom}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Éditer le vinyle
                    </h2>
                    <div class="mt-1 space-y-1">
                        <!-- Type de vinyle -->
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span v-if="isDiscogsVinyl" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                🎵 Vinyle Discogs - Données non modifiables
                            </span>
                            <span v-else-if="canEditVinyl" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                ✅ Vinyle manuel - Vous êtes le créateur
                            </span>
                            <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                ⚠️ Vinyle manuel créé par {{ vinylCreator?.name || 'un autre utilisateur' }}
                            </span>
                        </p>
                        <!-- Permissions -->
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span v-if="canEditVinylFields">Vous pouvez modifier toutes les informations</span>
                            <span v-else>Vous pouvez modifier uniquement les informations de votre exemplaire</span>
                        </p>
                    </div>
                </div>
                <Link :href="route('vinyls.index')" 
                      class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md transition-colors">
                    Retour à mes vinyles
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <!-- Image Section pour vinyles manuels avec permissions -->
                            <div v-if="isManualVinyl && canEditVinylFields" class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Image de pochette</h3>
                                <div class="flex items-start space-x-6">
                                    <div class="w-48 h-48 rounded-lg overflow-hidden border-2 border-gray-200 dark:border-gray-600 flex-shrink-0">
                                        <img v-if="imagePreview" 
                                             :src="imagePreview" 
                                             :alt="vinyl.vinyl_nom"
                                             class="w-full h-full object-cover">
                                        <div v-else class="w-full h-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                            <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                            </svg>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-1">
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Télécharger une nouvelle image
                                            </label>
                                            <input type="file" 
                                                   @change="handleImageChange"
                                                   accept="image/*"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Formats acceptés: JPEG, PNG, GIF. Taille max: 5MB
                                            </p>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Ou URL d'image
                                            </label>
                                            <input v-model="form.pochette_url"
                                                   type="url"
                                                   placeholder="https://..."
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        </div>
                                        
                                        <button type="button" 
                                                @click="clearImage"
                                                class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                            Supprimer l'image
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Collection Selection -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Collection <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.collection_id" 
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                    <option v-for="collection in collections" 
                                            :key="collection.id" 
                                            :value="collection.id">
                                        {{ collection.collection_nom }}
                                    </option>
                                </select>
                                <p class="text-xs text-red-500 mt-1" v-if="form.errors.collection_id">
                                    {{ form.errors.collection_id }}
                                </p>
                            </div>

                            <!-- Vinyl Information -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                    Informations du vinyle
                                    <span v-if="!isManualVinyl" class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                        (Champs limités pour les vinyles Discogs)
                                    </span>
                                </h3>
                                

                                <!-- Affichage en lecture seule si pas de permissions -->
                                <div v-if="!canEditVinylFields" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                            <span v-if="isDiscogsVinyl">Informations Discogs (non modifiables)</span>
                                            <span v-else>Informations du vinyle (créé par {{ vinylCreator?.name || 'un autre utilisateur' }})</span>
                                        </h4>
                                        <!-- Bouton de duplication pour les vinyles manuels créés par d'autres -->
                                        <button v-if="isManualVinyl && !canEditVinyl"
                                                @click="duplicateVinyl"
                                                type="button"
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                            </svg>
                                            Créer ma copie
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">Nom:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.vinyl_nom }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">Titre:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.vinyl_titre }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">Format:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ getFormatLabel(vinyl.vinyl_format) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">Artiste:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.artiste }}</span>
                                        </div>
                                        <div v-if="vinyl.label">
                                            <span class="text-gray-600 dark:text-gray-400">Label:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.label }}</span>
                                        </div>
                                        <div v-if="vinyl.annee">
                                            <span class="text-gray-600 dark:text-gray-400">Année:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.annee }}</span>
                                        </div>
                                        <div v-if="vinyl.reference">
                                            <span class="text-gray-600 dark:text-gray-400">Référence:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.reference }}</span>
                                        </div>
                                        <div v-if="vinyl.pays">
                                            <span class="text-gray-600 dark:text-gray-400">Pays:</span>
                                            <span class="ml-2 text-gray-900 dark:text-white">{{ vinyl.pays }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Champs modifiables pour les vinyles manuels avec permissions -->
                                <div v-if="canEditVinylFields && isManualVinyl" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Nom du vinyle -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Nom du vinyle <span class="text-red-500">*</span>
                                        </label>
                                        <input v-model="form.vinyl_nom"
                                               type="text"
                                               required
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.vinyl_nom">
                                            {{ form.errors.vinyl_nom }}
                                        </p>
                                    </div>

                                    <!-- Titre de l'album -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Titre de l'album
                                        </label>
                                        <input v-model="form.vinyl_titre"
                                               type="text"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.vinyl_titre">
                                            {{ form.errors.vinyl_titre }}
                                        </p>
                                    </div>

                                    <!-- Format -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Format <span class="text-red-500">*</span>
                                        </label>
                                        <select v-model="form.vinyl_format" 
                                                required
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                            <option value="1">LP (33 tours)</option>
                                            <option value="2">45 tours</option>
                                            <option value="3">CD</option>
                                            <option value="4">Cassette</option>
                                            <option value="5">DVD</option>
                                            <option value="6">Blu-ray</option>
                                            <option value="7">Box Set</option>
                                            <option value="8">78 tours</option>
                                            <option value="9">Digital</option>
                                        </select>
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.vinyl_format">
                                            {{ form.errors.vinyl_format }}
                                        </p>
                                    </div>

                                    <!-- Artiste (manuel seulement) -->
                                    <div v-if="isManualVinyl">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Artiste <span class="text-red-500">*</span>
                                        </label>
                                        <input v-model="form.artiste"
                                               type="text"
                                               required
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.artiste">
                                            {{ form.errors.artiste }}
                                        </p>
                                    </div>

                                    <!-- Label (manuel seulement) -->
                                    <div v-if="isManualVinyl">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Label
                                        </label>
                                        <input v-model="form.label"
                                               type="text"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.label">
                                            {{ form.errors.label }}
                                        </p>
                                    </div>

                                    <!-- Référence (manuel seulement) -->
                                    <div v-if="isManualVinyl">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Référence
                                        </label>
                                        <input v-model="form.reference"
                                               type="text"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.reference">
                                            {{ form.errors.reference }}
                                        </p>
                                    </div>

                                    <!-- Année de sortie (manuel seulement) -->
                                    <div v-if="isManualVinyl">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Année de sortie
                                        </label>
                                        <input v-model="form.annee"
                                               type="number"
                                               min="1900"
                                               :max="new Date().getFullYear()"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.annee">
                                            {{ form.errors.annee }}
                                        </p>
                                    </div>

                                    <!-- Pays (manuel seulement) -->
                                    <div v-if="isManualVinyl">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Pays
                                        </label>
                                        <input v-model="form.pays"
                                               type="text"
                                               placeholder="France, UK, US..."
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.pays">
                                            {{ form.errors.pays }}
                                        </p>
                                    </div>

                                    <!-- Édition (manuel seulement) -->
                                    <div v-if="isManualVinyl">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Édition
                                        </label>
                                        <input v-model="form.edition"
                                               type="number"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.edition">
                                            {{ form.errors.edition }}
                                        </p>
                                    </div>

                                    <!-- Année originale (manuel seulement) -->
                                    <div v-if="isManualVinyl">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Année originale
                                        </label>
                                        <input v-model="form.anneeOriginal"
                                               type="number"
                                               min="1900"
                                               :max="new Date().getFullYear()"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.anneeOriginal">
                                            {{ form.errors.anneeOriginal }}
                                        </p>
                                    </div>

                                    <!-- Référence matrice (manuel seulement) -->
                                    <div v-if="isManualVinyl">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Référence matrice
                                        </label>
                                        <input v-model="form.refMatrice"
                                               type="text"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                    </div>

                                    <!-- Distribution (manuel seulement) -->
                                    <div v-if="isManualVinyl">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Distribution
                                        </label>
                                        <input v-model="form.distribution"
                                               type="text"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>

                                <!-- Champs textarea pour manuel avec permissions -->
                                <div v-if="canEditVinylFields && isManualVinyl" class="mt-6 space-y-4">
                                    <!-- Tracks -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Liste des pistes
                                        </label>
                                        <textarea v-model="form.tracks"
                                                  rows="4"
                                                  placeholder="1. Titre de la piste&#10;2. Autre piste..."
                                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"></textarea>
                                    </div>

                                    <!-- Spécificité -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Spécificité
                                        </label>
                                        <textarea v-model="form.specificite"
                                                  rows="2"
                                                  placeholder="Édition limitée, couleur du vinyle, particularités..."
                                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Collection Information -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Informations de collection</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Prix d'achat -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Prix d'achat (€)
                                        </label>
                                        <input v-model="form.prix_achat"
                                               type="number"
                                               step="0.01"
                                               min="0"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.prix_achat">
                                            {{ form.errors.prix_achat }}
                                        </p>
                                    </div>

                                    <!-- Année d'achat -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Année d'achat
                                        </label>
                                        <input v-model="form.annee_achat"
                                               type="number"
                                               min="1900"
                                               :max="new Date().getFullYear()"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.annee_achat">
                                            {{ form.errors.annee_achat }}
                                        </p>
                                    </div>

                                    <!-- Provenance -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Provenance
                                        </label>
                                        <select v-model="form.provenance" 
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                            <option value="0">Inconnue</option>
                                            <option value="1">Magasin de disques</option>
                                            <option value="2">Marché aux puces</option>
                                            <option value="3">Internet</option>
                                            <option value="4">Don</option>
                                            <option value="5">Échange</option>
                                            <option value="6">Concert</option>
                                            <option value="7">Autre</option>
                                        </select>
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.provenance">
                                            {{ form.errors.provenance }}
                                        </p>
                                    </div>

                                    <!-- Note -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Note (/10)
                                        </label>
                                        <input v-model="form.note"
                                               type="number"
                                               min="1"
                                               max="10"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.note">
                                            {{ form.errors.note }}
                                        </p>
                                    </div>

                                    <!-- Quantité -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Nombre d'exemplaires
                                        </label>
                                        <input v-model="form.quantite"
                                               type="number"
                                               min="1"
                                               max="999"
                                               required
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            Combien d'exemplaires de ce vinyle possédez-vous ?
                                        </p>
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.quantite">
                                            {{ form.errors.quantite }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Commentaires -->
                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Commentaires
                                    </label>
                                    <textarea v-model="form.commentaires"
                                              rows="3"
                                              placeholder="Vos commentaires sur ce vinyle..."
                                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"></textarea>
                                    <p class="text-xs text-red-500 mt-1" v-if="form.errors.commentaires">
                                        {{ form.errors.commentaires }}
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-600">
                                <Link :href="route('vinyls.index')" 
                                      class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                                    Annuler
                                </Link>
                                <button type="submit"
                                        :disabled="form.processing"
                                        :class="[
                                            'px-6 py-2 rounded-md transition-colors',
                                            form.processing 
                                                ? 'bg-gray-400 text-gray-600 cursor-not-allowed' 
                                                : 'bg-purple-600 hover:bg-purple-700 text-white'
                                        ]">
                                    {{ form.processing ? 'Sauvegarde...' : 'Sauvegarder les modifications' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal de confirmation pour la duplication -->
        <ConfirmationModal
            :show="showDuplicateModal"
            title="Créer votre propre copie"
            :message="`Ce vinyle a été créé par ${vinylCreator?.name || 'un autre utilisateur'} et vous ne pouvez pas modifier ses informations.\n\nEn créant votre propre copie :\n• Vous deviendrez propriétaire de la nouvelle version\n• Vous pourrez modifier toutes les informations (titre, artiste, etc.)\n• L'image sera dupliquée pour être indépendante\n• Votre exemplaire actuel sera remplacé par cette nouvelle version\n\nVoulez-vous continuer ?`"
            type="warning"
            confirm-text="Créer ma copie"
            cancel-text="Annuler"
            @confirm="confirmDuplicate"
            @close="showDuplicateModal = false"
        />
    </AuthenticatedLayout>
</template>