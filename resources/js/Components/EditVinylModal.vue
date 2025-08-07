<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    collectionVinyl: {
        type: Object,
        required: true
    },
    collections: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close']);

// Données du formulaire
const form = ref({
    collection_id: props.collectionVinyl.collection_id,
    prix_achat: props.collectionVinyl.prix_achat || null,
    annee_achat: props.collectionVinyl.annee_achat || null,
    provenance: props.collectionVinyl.provenance || 0,
    commentaires: props.collectionVinyl.commentaires || '',
    note: props.collectionVinyl.note || null,
    
    // Champs vinyles (seulement pour manuels)
    vinyl_nom: props.collectionVinyl.vinyl?.vinyl_nom || '',
    vinyl_titre: props.collectionVinyl.vinyl?.vinyl_titre || '',
    vinyl_format: props.collectionVinyl.vinyl?.vinyl_format || 1,
    artiste: props.collectionVinyl.vinyl?.artiste || '',
    label: props.collectionVinyl.vinyl?.label || '',
    reference: props.collectionVinyl.vinyl?.reference || '',
    annee: props.collectionVinyl.vinyl?.annee || null,
    pays: props.collectionVinyl.vinyl?.pays || null,
    tracks: props.collectionVinyl.vinyl?.tracks || '',
    specificite: props.collectionVinyl.vinyl?.specificite || '',
    refMatrice: props.collectionVinyl.vinyl?.refMatrice || '',
    distribution: props.collectionVinyl.vinyl?.distribution || '',
    edition: props.collectionVinyl.vinyl?.edition || null,
    anneeOriginal: props.collectionVinyl.vinyl?.anneeOriginal || null,
    pochette_url: props.collectionVinyl.vinyl?.pochette || '',
    pochette_file: null
});

const isManualVinyl = computed(() => {
    return props.collectionVinyl.vinyl?.discogs_type === 'manual' || 
           !props.collectionVinyl.vinyl?.discogs_id;
});

const isSaving = ref(false);
const imagePreview = ref(props.collectionVinyl.vinyl?.pochette);
const imageFile = ref(null);

// Réinitialiser les données quand le vinyle change
watch(() => props.collectionVinyl, (newValue) => {
    if (newValue) {
        form.value = {
            collection_id: newValue.collection_id,
            prix_achat: newValue.prix_achat || null,
            annee_achat: newValue.annee_achat || null,
            provenance: newValue.provenance || 0,
            commentaires: newValue.commentaires || '',
            note: newValue.note || null,
            vinyl_nom: newValue.vinyl?.vinyl_nom || '',
            vinyl_titre: newValue.vinyl?.vinyl_titre || '',
            vinyl_format: newValue.vinyl?.vinyl_format || 1,
            artiste: newValue.vinyl?.artiste || '',
            label: newValue.vinyl?.label || '',
            reference: newValue.vinyl?.reference || '',
            annee: newValue.vinyl?.annee || null,
            pays: newValue.vinyl?.pays || null,
            tracks: newValue.vinyl?.tracks || '',
            specificite: newValue.vinyl?.specificite || '',
            refMatrice: newValue.vinyl?.refMatrice || '',
            distribution: newValue.vinyl?.distribution || '',
            edition: newValue.vinyl?.edition || null,
            anneeOriginal: newValue.vinyl?.anneeOriginal || null,
            pochette_url: newValue.vinyl?.pochette || '',
            pochette_file: null
        };
        imagePreview.value = newValue.vinyl?.pochette;
        imageFile.value = null;
    }
});

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('L\'image est trop grande. Taille maximum: 5MB');
            return;
        }
        
        imageFile.value = file;
        form.value.pochette_file = file;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
        
        form.value.pochette_url = '';
    }
};

const clearImage = () => {
    form.value.pochette_file = null;
    form.value.pochette_url = '';
    imagePreview.value = null;
    imageFile.value = null;
    const fileInput = document.querySelector('#edit-image-upload');
    if (fileInput) fileInput.value = '';
};

const closeModal = () => {
    form.value = {
        collection_id: props.collectionVinyl.collection_id,
        prix_achat: props.collectionVinyl.prix_achat || null,
        annee_achat: props.collectionVinyl.annee_achat || null,
        provenance: props.collectionVinyl.provenance || 0,
        commentaires: props.collectionVinyl.commentaires || '',
        note: props.collectionVinyl.note || null,
        vinyl_nom: props.collectionVinyl.vinyl?.vinyl_nom || '',
        vinyl_titre: props.collectionVinyl.vinyl?.vinyl_titre || '',
        vinyl_format: props.collectionVinyl.vinyl?.vinyl_format || 1,
        artiste: props.collectionVinyl.vinyl?.artiste || '',
        label: props.collectionVinyl.vinyl?.label || '',
        reference: props.collectionVinyl.vinyl?.reference || '',
        annee: props.collectionVinyl.vinyl?.annee || null,
        pays: props.collectionVinyl.vinyl?.pays || null,
        tracks: props.collectionVinyl.vinyl?.tracks || '',
        specificite: props.collectionVinyl.vinyl?.specificite || '',
        refMatrice: props.collectionVinyl.vinyl?.refMatrice || '',
        distribution: props.collectionVinyl.vinyl?.distribution || '',
        edition: props.collectionVinyl.vinyl?.edition || null,
        anneeOriginal: props.collectionVinyl.vinyl?.anneeOriginal || null,
        pochette_url: props.collectionVinyl.vinyl?.pochette || '',
        pochette_file: null
    };
    imagePreview.value = props.collectionVinyl.vinyl?.pochette;
    imageFile.value = null;
    emit('close');
};

const saveVinyl = () => {
    if (isManualVinyl.value && (!form.value.vinyl_nom || !form.value.artiste)) {
        alert('Veuillez remplir les champs obligatoires');
        return;
    }

    const dataToSend = {
        ...form.value,
        _method: 'PUT'
    };

    router.post(`/mes-vinyles/${props.collectionVinyl.id}`, dataToSend, {
        forceFormData: true,
        preserveState: false,
        preserveScroll: true,
        onStart: () => {
            isSaving.value = true;
        },
        onSuccess: () => {
            closeModal();
        },
        onError: (errors) => {
            console.error('Erreurs de validation:', errors);
            alert('Erreur lors de la mise à jour du vinyle. Vérifiez les champs.');
        },
        onFinish: () => {
            isSaving.value = false;
        }
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
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-4xl max-h-[90vh] shadow-lg rounded-md bg-white dark:bg-gray-800 flex flex-col overflow-hidden">
            <div class="flex flex-col h-full max-h-[90vh]">
                <!-- Header -->
                <div class="p-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Éditer le vinyle
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <span v-if="isManualVinyl" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    📝 Vinyle manuel - Tous les champs modifiables
                                </span>
                                <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                    🎵 Vinyle Discogs - Champs limités
                                </span>
                            </p>
                        </div>
                        <button @click="closeModal" 
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body avec scroll -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <form @submit.prevent="saveVinyl">
                        <!-- Image Section pour vinyles manuels -->
                        <div v-if="isManualVinyl" class="mb-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Image de pochette</h4>
                            <div class="flex items-start space-x-4">
                                <div class="w-32 h-32 rounded-lg overflow-hidden border-2 border-gray-200 dark:border-gray-600 flex-shrink-0">
                                    <img v-if="imagePreview" 
                                         :src="imagePreview" 
                                         :alt="form.vinyl_nom"
                                         class="w-full h-full object-cover">
                                    <div v-else class="w-full h-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                        </svg>
                                    </div>
                                </div>
                                
                                <div class="flex-1">
                                    <div class="mb-3">
                                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                                            Télécharger une image
                                        </label>
                                        <input type="file" 
                                               id="edit-image-upload"
                                               @change="handleImageUpload"
                                               accept="image/*"
                                               class="w-full text-sm px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            JPG, PNG, GIF. Max 5MB
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                                            Ou URL d'image
                                        </label>
                                        <input v-model="form.pochette_url"
                                               type="url"
                                               placeholder="https://..."
                                               class="w-full text-sm px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                    </div>
                                    
                                    <button type="button" 
                                            @click="clearImage"
                                            class="px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">
                                        Supprimer l'image
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Collection -->
                        <div class="mb-4">
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
                        </div>

                        <!-- Informations Discogs en lecture seule -->
                        <div v-if="!isManualVinyl" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                Informations Discogs (non modifiables)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Nom:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ collectionVinyl.vinyl.vinyl_nom }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Format:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ getFormatLabel(collectionVinyl.vinyl.vinyl_format) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Artiste:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ collectionVinyl.vinyl.artiste }}</span>
                                </div>
                                <div v-if="collectionVinyl.vinyl.label">
                                    <span class="text-gray-600 dark:text-gray-400">Label:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ collectionVinyl.vinyl.label }}</span>
                                </div>
                                <div v-if="collectionVinyl.vinyl.annee">
                                    <span class="text-gray-600 dark:text-gray-400">Année:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ collectionVinyl.vinyl.annee }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Champs modifiables pour vinyles manuels -->
                        <div v-if="isManualVinyl" class="mb-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Informations du vinyle</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Nom du vinyle <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.vinyl_nom"
                                           type="text"
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Titre de l'album
                                    </label>
                                    <input v-model="form.vinyl_titre"
                                           type="text"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>

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
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Artiste <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.artiste"
                                           type="text"
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Label
                                    </label>
                                    <input v-model="form.label"
                                           type="text"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Référence
                                    </label>
                                    <input v-model="form.reference"
                                           type="text"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Année de sortie
                                    </label>
                                    <input v-model="form.annee"
                                           type="number"
                                           min="1900"
                                           :max="new Date().getFullYear()"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                        </div>

                        <!-- Informations de collection -->
                        <div class="border-t pt-4">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Informations de collection</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Prix d'achat (€)
                                    </label>
                                    <input v-model="form.prix_achat"
                                           type="number"
                                           step="0.01"
                                           min="0"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Année d'achat
                                    </label>
                                    <input v-model="form.annee_achat"
                                           type="number"
                                           min="1900"
                                           :max="new Date().getFullYear()"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Note (/10)
                                    </label>
                                    <input v-model="form.note"
                                           type="number"
                                           min="1"
                                           max="10"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>

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
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Commentaires
                                </label>
                                <textarea v-model="form.commentaires"
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"></textarea>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="flex justify-end space-x-3 px-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <button @click="closeModal"
                            type="button"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Annuler
                    </button>
                    <button @click="saveVinyl"
                            :disabled="isSaving"
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50">
                        {{ isSaving ? 'Sauvegarde...' : 'Sauvegarder' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>