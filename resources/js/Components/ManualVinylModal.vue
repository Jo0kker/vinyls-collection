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
    note: null,
    quantite: 1
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
        note: null,
        quantite: 1
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
    <div v-if="show" class="fixed inset-0 bg-gray-600/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 p-4 sm:p-6 md:p-8">
        <div class="relative mx-auto w-full max-w-2xl shadow-xl rounded-lg bg-white dark:bg-gray-800 max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3rem)] md:max-h-[calc(100vh-4rem)] overflow-hidden flex flex-col">

            <!-- Header fixe -->
            <div class="flex-shrink-0 p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white">
                        Ajouter un vinyle manuellement
                    </h3>
                    <button @click="closeModal"
                            class="flex-shrink-0 p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Contenu scrollable -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                <form @submit.prevent="saveManualVinyl" id="manual-vinyl-form">
                    <!-- Collection (si plusieurs collections disponibles) -->
                    <div v-if="collections.length > 1" class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Collection <span class="text-red-500">*</span>
                        </label>
                        <select v-model="manualVinyl.collection_id"
                                required
                                class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                            <option value="">Sélectionnez une collection</option>
                            <option v-for="collection in collections"
                                    :key="collection.id"
                                    :value="collection.id">
                                {{ collection.collection_nom }}
                            </option>
                        </select>
                    </div>

                    <!-- Section: Informations du vinyle -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                            </svg>
                            Informations du vinyle
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Nom du vinyle -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nom du vinyle <span class="text-red-500">*</span>
                                </label>
                                <input v-model="manualVinyl.vinyl_nom"
                                       type="text"
                                       required
                                       class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                            </div>

                            <!-- Artiste -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Artiste <span class="text-red-500">*</span>
                                </label>
                                <input v-model="manualVinyl.artiste"
                                       type="text"
                                       required
                                       class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                            </div>

                            <!-- Titre -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Titre de l'album
                                </label>
                                <input v-model="manualVinyl.vinyl_titre"
                                       type="text"
                                       class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                            </div>

                            <!-- Label -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Label
                                </label>
                                <input v-model="manualVinyl.label"
                                       type="text"
                                       class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                            </div>

                            <!-- Référence -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Référence
                                </label>
                                <input v-model="manualVinyl.reference"
                                       type="text"
                                       class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                            </div>

                            <!-- Année -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Année de sortie
                                </label>
                                <input v-model="manualVinyl.annee"
                                       type="number"
                                       min="1900"
                                       :max="new Date().getFullYear()"
                                       class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                            </div>
                        </div>

                        <!-- Type Discogs -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Type de version
                            </label>
                            <select v-model="manualVinyl.discogs_type"
                                    class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                                <option value="">Non spécifié</option>
                                <option value="release">Release (version spécifique)</option>
                                <option value="master">Master (version principale)</option>
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Release = version spécifique, Master = version principale générique
                            </p>
                        </div>

                        <!-- Image de pochette -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Image de pochette
                            </label>

                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1 space-y-3">
                                    <!-- Upload de fichier -->
                                    <div>
                                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                                            Télécharger une image
                                        </label>
                                        <input type="file"
                                               accept="image/*"
                                               @change="handleManualImageUpload"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
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
                                               class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                                    </div>
                                </div>

                                <!-- Prévisualisation -->
                                <div v-if="manualImagePreview || manualVinyl.pochette"
                                     class="flex-shrink-0">
                                    <img :src="manualImagePreview || manualVinyl.pochette"
                                         alt="Prévisualisation"
                                         class="w-24 h-24 object-cover rounded-lg border border-gray-200 dark:border-gray-600">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Informations de collection -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            Informations de votre exemplaire
                        </h4>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <!-- Prix d'achat -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Prix (€)
                                </label>
                                <input v-model="manualVinyl.prix_achat"
                                       type="number"
                                       step="0.01"
                                       min="0"
                                       class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                            </div>

                            <!-- Année d'achat -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Année achat
                                </label>
                                <input v-model="manualVinyl.annee_achat"
                                       type="number"
                                       min="1900"
                                       :max="new Date().getFullYear()"
                                       class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                            </div>

                            <!-- Note -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Note (/10)
                                </label>
                                <input v-model="manualVinyl.note"
                                       type="number"
                                       min="1"
                                       max="10"
                                       class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                            </div>

                            <!-- Quantité -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Quantité
                                </label>
                                <input v-model="manualVinyl.quantite"
                                       type="number"
                                       min="1"
                                       max="999"
                                       class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base">
                            </div>
                        </div>

                        <!-- Commentaires -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Commentaires
                            </label>
                            <textarea v-model="manualVinyl.commentaires"
                                      rows="2"
                                      placeholder="Notes personnelles sur cet exemplaire..."
                                      class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-base resize-none"></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer fixe -->
            <div class="flex-shrink-0 p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <button @click="closeModal"
                            type="button"
                            class="w-full sm:w-auto px-6 py-2.5 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 font-medium transition-colors">
                        Annuler
                    </button>
                    <button type="submit"
                            form="manual-vinyl-form"
                            :disabled="isSavingManualVinyl"
                            class="w-full sm:w-auto px-6 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 font-medium transition-colors">
                        {{ isSavingManualVinyl ? 'Ajout en cours...' : 'Ajouter le vinyle' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
