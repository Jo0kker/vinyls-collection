<script setup>
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    collection: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['close', 'confirm']);

const loading = ref(false);
const stats = ref(null);
const error = ref(null);

// Charger les statistiques quand la modale s'ouvre
watch(() => props.show, async (newValue) => {
    if (newValue && props.collection?.id) {
        // Reset et set loading immédiatement
        loading.value = true;
        error.value = null;
        stats.value = null;

        try {
            const response = await axios.get(`/collections/${props.collection.id}/deletion-stats`);
            stats.value = response.data;
            loading.value = false;
        } catch (err) {
            console.error('Erreur lors du chargement des statistiques:', err);
            error.value = 'Impossible de charger les statistiques.';
            loading.value = false;
        }
    } else if (!newValue) {
        // Reset quand on ferme
        stats.value = null;
        error.value = null;
        loading.value = false;
    }
});

const close = () => {
    emit('close');
};

const confirm = () => {
    emit('confirm');
};
</script>

<template>
    <Modal :show="show" @close="close" max-width="lg">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Supprimer la collection
                    </h3>
                </div>
            </div>

            <div v-if="error" class="py-4">
                <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
            </div>

            <div v-else class="space-y-4">
                <!-- Question principale -->
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Êtes-vous sûr de vouloir supprimer la collection
                    <span class="font-semibold text-gray-900 dark:text-white">{{ collection.collection_nom }}</span> ?
                </p>

                <!-- Skeleton loader pendant le chargement -->
                <div v-if="loading" class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 animate-pulse">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <div class="h-5 w-5 bg-yellow-400 rounded"></div>
                        </div>
                        <div class="ml-3 flex-1 space-y-3">
                            <div class="h-4 bg-yellow-200 dark:bg-yellow-800 rounded w-3/4"></div>
                            <div class="h-3 bg-yellow-200 dark:bg-yellow-800 rounded w-full"></div>
                            <div class="h-3 bg-yellow-200 dark:bg-yellow-800 rounded w-5/6"></div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques de suppression -->
                <div v-else-if="stats" class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                                Attention : Cette action est irréversible
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>
                                        <span class="font-semibold">{{ stats.total_vinyls }}</span>
                                        vinyle{{ stats.total_vinyls > 1 ? 's' : '' }}
                                        {{ stats.total_vinyls > 1 ? 'seront retirés' : 'sera retiré' }} de cette collection
                                    </li>
                                    <li v-if="stats.orphan_vinyls > 0">
                                        <span class="font-semibold text-red-600 dark:text-red-400">{{ stats.orphan_vinyls }}</span>
                                        vinyle{{ stats.orphan_vinyls > 1 ? 's' : '' }}
                                        {{ stats.orphan_vinyls > 1 ? 'seront définitivement supprimés' : 'sera définitivement supprimé' }}
                                        ({{ stats.orphan_vinyls > 1 ? 'ils ne sont' : 'il n\'est' }} dans aucune autre collection)
                                    </li>
                                    <li v-if="stats.orphan_vinyls > 0">
                                        Les images associées à ces vinyles orphelins seront également supprimées
                                    </li>
                                    <li v-else class="text-green-700 dark:text-green-400">
                                        Tous les vinyles existent dans d'autres collections et seront conservés
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <SecondaryButton @click="close">
                    Annuler
                </SecondaryButton>
                <DangerButton @click="confirm" :disabled="loading">
                    Supprimer définitivement
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
