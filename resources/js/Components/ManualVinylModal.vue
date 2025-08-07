<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    collectionId: {
        type: Number,
        default: null
    },
    collections: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close']);

// Données pour le vinyle manuel
const manualVinyl = ref({
    vinyl_nom: '',
    artiste: '',
    vinyl_titre: '',
    label: '',
    reference: '',
    annee: null,
    pochette: '',
    discogs_type: '',
    collection_id: props.collectionId,
    prix_achat: null,
    annee_achat: null,
    provenance: 0,
    commentaires: '',
    note: null
});

const isSavingManualVinyl = ref(false);
const manualImagePreview = ref(null);
const manualImageFile = ref(null);

// Réinitialiser collection_id quand elle change
watch(() => props.collectionId, (newValue) => {
    manualVinyl.value.collection_id = newValue;
});

const closeModal = () => {
    // Réinitialiser le formulaire
    manualVinyl.value = {
        vinyl_nom: '',
        artiste: '',
        vinyl_titre: '',
        label: '',
        reference: '',
        annee: null,
        pochette: '',
        discogs_type: '',
        collection_id: props.collectionId,
        prix_achat: null,
        annee_achat: null,
        provenance: 0,
        commentaires: '',
        note: null
    };
    manualImagePreview.value = null;
    manualImageFile.value = null;
    emit('close');
};

const handleManualImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Vérifier la taille (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('L\'image est trop grande. Taille maximum: 5MB');
            return;
        }
        
        manualImageFile.value = file;
        
        // Prévisualisation
        const reader = new FileReader();
        reader.onload = (e) => {
            manualImagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
        
        // Clear URL si on upload un fichier
        manualVinyl.value.pochette = '';
    }
};

const saveManualVinyl = () => {
    if (!manualVinyl.value.vinyl_nom || !manualVinyl.value.artiste || !manualVinyl.value.collection_id) {
        alert('Veuillez remplir les champs obligatoires');
        return;
    }

    // Créer un objet avec toutes les données
    const dataToSend = {
        ...manualVinyl.value,
        pochette_file: manualImageFile.value
    };

    router.post('/vinyls/manual', dataToSend, {
        forceFormData: true, // Force l'utilisation de FormData pour l'upload de fichier
        preserveState: true,
        preserveScroll: true,
        onStart: () => {
            isSavingManualVinyl.value = true;
        },
        onSuccess: () => {
            closeModal();
        },
        onError: (errors) => {
            console.error('Erreurs de validation:', errors);
            alert('Erreur lors de l\'ajout du vinyle. Vérifiez les champs.');
        },
        onFinish: () => {
            isSavingManualVinyl.value = false;
        }
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative p-6 border w-4/5 max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Ajouter un vinyle manuellement</h3>
                <form @submit.prevent="saveManualVinyl">
                    <!-- Collection (si plusieurs collections disponibles) -->
                    <div v-if="collections.length > 1" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Collection <span class="text-red-500">*</span>
                        </label>
                        <select v-model="manualVinyl.collection_id" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            <option value="">Sélectionnez une collection</option>
                            <option v-for="collection in collections" 
                                    :key="collection.id" 
                                    :value="collection.id">
                                {{ collection.collection_nom }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nom du vinyle -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nom du vinyle <span class="text-red-500">*</span>
                            </label>
                            <input v-model="manualVinyl.vinyl_nom"
                                   type="text"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Artiste -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Artiste <span class="text-red-500">*</span>
                            </label>
                            <input v-model="manualVinyl.artiste"
                                   type="text"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Titre -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Titre de l'album
                            </label>
                            <input v-model="manualVinyl.vinyl_titre"
                                   type="text"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Label -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Label
                            </label>
                            <input v-model="manualVinyl.label"
                                   type="text"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Référence -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Référence
                            </label>
                            <input v-model="manualVinyl.reference"
                                   type="text"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Année -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Année de sortie
                            </label>
                            <input v-model="manualVinyl.annee"
                                   type="number"
                                   min="1900"
                                   :max="new Date().getFullYear()"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Type Discogs -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Type de version
                            </label>
                            <select v-model="manualVinyl.discogs_type"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                <option value="">Non spécifié</option>
                                <option value="release">Release (version spécifique)</option>
                                <option value="master">Master (version principale)</option>
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Release = version spécifique avec détails précis, Master = version principale générique
                            </p>
                        </div>

                        <!-- Upload d'image ou URL -->
                        <div class="mb-4 md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Image de pochette
                            </label>
                            <div class="space-y-3">
                                <!-- Upload de fichier -->
                                <div>
                                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                                        Télécharger une image
                                    </label>
                                    <input type="file" 
                                           accept="image/*"
                                           @change="handleManualImageUpload"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        JPG, PNG, GIF. Max 5MB
                                    </p>
                                </div>
                                
                                <!-- Ou URL -->
                                <div>
                                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                                        Ou URL d'image
                                    </label>
                                    <input v-model="manualVinyl.pochette"
                                           type="url"
                                           placeholder="https://..."
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>
                                
                                <!-- Prévisualisation -->
                                <div v-if="manualImagePreview || manualVinyl.pochette" class="mt-3">
                                    <img :src="manualImagePreview || manualVinyl.pochette" 
                                         alt="Prévisualisation"
                                         class="w-24 h-24 object-cover rounded border">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-4 mt-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Informations de collection</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Prix d'achat -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Prix d'achat (€)
                                </label>
                                <input v-model="manualVinyl.prix_achat"
                                       type="number"
                                       step="0.01"
                                       min="0"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            </div>

                            <!-- Année d'achat -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Année d'achat
                                </label>
                                <input v-model="manualVinyl.annee_achat"
                                       type="number"
                                       min="1900"
                                       :max="new Date().getFullYear()"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            </div>

                            <!-- Note -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Note (/10)
                                </label>
                                <input v-model="manualVinyl.note"
                                       type="number"
                                       min="1"
                                       max="10"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <!-- Commentaires -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Commentaires
                            </label>
                            <textarea v-model="manualVinyl.commentaires"
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button @click="closeModal"
                                type="button"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Annuler
                        </button>
                        <button type="submit"
                                :disabled="isSavingManualVinyl"
                                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50">
                            {{ isSavingManualVinyl ? 'Ajout en cours...' : 'Ajouter le vinyle' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>