<template>
    <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="closeModal">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800" @click.stop>
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Modifier l'avatar
                </h3>
                
                <!-- Étape 1: Sélection du fichier -->
                <div v-if="step === 1" class="space-y-4">
                    <div class="flex items-center justify-center w-full">
                        <label for="avatar-upload" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 transition-colors">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg v-if="!selectedFile" class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <div v-if="!selectedFile" class="text-center">
                                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-semibold">Cliquez pour télécharger</span> ou glissez-déposez
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG ou JPEG (MAX. 2MB)</p>
                                </div>
                                <div v-else class="text-center">
                                    <img :src="previewUrl" alt="Preview" class="max-h-48 rounded-lg">
                                    <p class="mt-2 text-sm text-green-600">{{ selectedFile.name }}</p>
                                </div>
                            </div>
                            <input 
                                id="avatar-upload" 
                                type="file" 
                                class="hidden" 
                                accept="image/png,image/jpeg,image/jpg"
                                @change="handleFileSelect"
                            />
                        </label>
                    </div>
                    
                    <div v-if="uploadError" class="text-red-600 text-sm">
                        {{ uploadError }}
                    </div>
                </div>

                <!-- Étape 2: Redimensionnement/Recadrage -->
                <div v-if="step === 2" class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Ajustez et recadrez votre image pour créer un avatar parfait
                    </p>
                    
                    <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
                        <Cropper
                            ref="cropper"
                            :src="previewUrl"
                            :stencil-props="{ aspectRatio: 1 }"
                            :resize-image="{ adjustStencil: false }"
                            class="cropper-container"
                            @change="updatePreview"
                            @ready="updatePreview"
                        />
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            L'avatar sera redimensionné en 200x200 pixels
                        </div>
                        <div class="flex gap-2">
                            <button 
                                @click="resetCrop"
                                class="px-3 py-1 text-sm bg-gray-300 hover:bg-gray-400 text-gray-700 rounded"
                            >
                                Réinitialiser
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Aperçu final -->
                <div v-if="step === 2" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Aperçu :</h4>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-600">
                            <canvas ref="previewCanvas" width="64" height="64" class="w-full h-full"></canvas>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Votre nouvel avatar
                        </div>
                    </div>
                </div>
                
                <!-- Boutons d'action -->
                <div class="flex justify-end gap-3 mt-6">
                    <button 
                        @click="closeModal"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md text-sm transition-colors"
                    >
                        Annuler
                    </button>
                    
                    <button 
                        v-if="step === 1 && selectedFile"
                        @click="goToStep2"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm transition-colors"
                    >
                        Suivant
                    </button>
                    
                    <div v-if="step === 2" class="flex gap-2">
                        <button 
                            @click="step = 1"
                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md text-sm transition-colors"
                        >
                            Retour
                        </button>
                        <button 
                            @click="uploadAvatar"
                            :disabled="uploading"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white rounded-md text-sm transition-colors"
                        >
                            <span v-if="uploading">Téléchargement...</span>
                            <span v-else>Sauvegarder</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import { router } from '@inertiajs/vue3';

const emit = defineEmits(['close', 'uploaded']);

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    }
});

// État du modal
const step = ref(1);
const selectedFile = ref(null);
const previewUrl = ref('');
const uploadError = ref('');
const uploading = ref(false);

// Références
const cropper = ref(null);
const previewCanvas = ref(null);

// Gestion du fichier sélectionné
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Validation du fichier
    if (!file.type.startsWith('image/')) {
        uploadError.value = 'Veuillez sélectionner un fichier image valide.';
        return;
    }

    if (file.size > 2 * 1024 * 1024) { // 2MB
        uploadError.value = 'Le fichier ne doit pas dépasser 2MB.';
        return;
    }

    uploadError.value = '';
    selectedFile.value = file;
    
    // Créer l'URL de prévisualisation
    previewUrl.value = URL.createObjectURL(file);
}

// Aller à l'étape 2 (recadrage)
function goToStep2() {
    step.value = 2;
    // Attendre que le cropper soit monté et initialisé
    nextTick(() => {
        setTimeout(() => {
            updatePreview();
        }, 100);
    });
}

// Réinitialiser le recadrage
function resetCrop() {
    if (cropper.value) {
        cropper.value.reset();
        updatePreview();
    }
}

// Mettre à jour l'aperçu
function updatePreview() {
    if (!cropper.value || !previewCanvas.value) {
        console.log('Cropper ou canvas non disponible');
        return;
    }
    
    try {
        const canvas = cropper.value.getCanvas();
        if (canvas && canvas.width > 0 && canvas.height > 0) {
            const ctx = previewCanvas.value.getContext('2d');
            ctx.clearRect(0, 0, 64, 64);
            ctx.drawImage(canvas, 0, 0, 64, 64);
            console.log('Preview mis à jour');
        } else {
            console.log('Canvas invalide:', canvas ? `${canvas.width}x${canvas.height}` : 'null');
        }
    } catch (error) {
        console.error('Erreur lors de la mise à jour de la preview:', error);
    }
}

// Surveiller les changements du cropper
watch(() => cropper.value, () => {
    if (cropper.value) {
        // Mise à jour initiale après montage
        setTimeout(updatePreview, 300);
    }
});

// Télécharger l'avatar
async function uploadAvatar() {
    if (!cropper.value) return;
    
    uploading.value = true;
    
    try {
        const canvas = cropper.value.getCanvas();
        
        // Redimensionner à 200x200
        const resizedCanvas = document.createElement('canvas');
        resizedCanvas.width = 200;
        resizedCanvas.height = 200;
        const ctx = resizedCanvas.getContext('2d');
        ctx.drawImage(canvas, 0, 0, 200, 200);
        
        // Convertir en blob
        const blob = await new Promise(resolve => {
            resizedCanvas.toBlob(resolve, 'image/jpeg', 0.9);
        });
        
        // Créer FormData
        const formData = new FormData();
        formData.append('avatar', blob, 'avatar.jpg');
        
        // Envoyer via Inertia
        router.post(route('profile.avatar'), formData, {
            forceFormData: true,
            onSuccess: () => {
                emit('uploaded');
                closeModal();
            },
            onError: (errors) => {
                uploadError.value = errors.avatar || 'Erreur lors du téléchargement.';
            }
        });
        
    } catch (error) {
        uploadError.value = 'Erreur lors du traitement de l\'image.';
    } finally {
        uploading.value = false;
    }
}

// Fermer le modal
function closeModal() {
    // Nettoyer
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    
    // Reset état
    step.value = 1;
    selectedFile.value = null;
    previewUrl.value = '';
    uploadError.value = '';
    uploading.value = false;
    
    emit('close');
}
</script>

<style scoped>
.cropper-container {
    max-height: 400px;
}
</style>