<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DiscogsVinylModal from '@/Components/DiscogsVinylModal.vue';
import ManualVinylModal from '@/Components/ManualVinylModal.vue';
import EditVinylModal from '@/Components/EditVinylModal.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    vinyls: {
        type: Array,
        default: () => []
    },
    allCollections: {
        type: Array,
        default: () => []
    }
});

// États des modales
const showVinylModal = ref(false);
const showManualVinylModal = ref(false);
const showEditVinylModal = ref(false);
const vinylToEdit = ref(null);


const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

// Méthodes pour les modales
const openVinylModal = () => {
    showVinylModal.value = true;
};

const openManualVinylModal = () => {
    showVinylModal.value = false;
    showManualVinylModal.value = true;
};

const openEditModal = (vinyl) => {
    vinylToEdit.value = vinyl;
    showEditVinylModal.value = true;
};

const showEditRestrictionToast = (vinyl) => {
    const creatorName = vinyl.vinyl?.creator?.name || 'un autre utilisateur';
    const message = `Ce vinyle manuel ne peut être modifié que par son créateur : ${creatorName}`;
    
    // Créer et afficher le toast
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 z-50 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 animate-slide-up';
    toast.innerHTML = `
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
        </svg>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    
    // Retirer le toast après 4 secondes
    setTimeout(() => {
        toast.classList.add('animate-slide-down');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 4000);
};

const closeEditModal = () => {
    showEditVinylModal.value = false;
    vinylToEdit.value = null;
};
</script>

<style scoped>
@keyframes slide-up {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@keyframes slide-down {
    from { transform: translateY(0); opacity: 1; }
    to { transform: translateY(100%); opacity: 0; }
}

.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}

.animate-slide-down {
    animation: slide-down 0.3s ease-in;
}
</style>

<template>
    <Head>
        <title>Mes Vinyles - Ma Collection | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="description" content="Gérez votre collection personnelle de vinyles. Ajoutez, modifiez et organisez vos albums préférés." />
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Mes Vinyles
                </h2>
                <button @click="openVinylModal"
                   class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors">
                    Ajouter un vinyle
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="vinyls.length === 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun vinyle</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Commencez par ajouter votre premier vinyle à votre collection.</p>
                        <div class="mt-6">
                            <button @click="openVinylModal"
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                                Ajouter un vinyle
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="vinyl in vinyls" :key="vinyl.id"
                                 class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors group">
                                <div class="flex items-start space-x-4">
                                    <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0">
                                        <img v-if="vinyl.vinyl?.pochette" 
                                             :src="vinyl.vinyl.pochette" 
                                             :alt="vinyl.vinyl?.vinyl_nom"
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
                                            {{ vinyl.vinyl?.vinyl_nom || 'Nom inconnu' }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ vinyl.vinyl?.artiste || 'Artiste inconnu' }}
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            Collection: {{ vinyl.collection?.collection_nom || 'Collection inconnue' }}
                                        </p>
                                        <div class="flex items-center mt-2 space-x-4 text-xs text-gray-400">
                                            <span v-if="vinyl.prix_achat">
                                                {{ vinyl.prix_achat }}€
                                            </span>
                                            <span v-if="vinyl.note">
                                                ⭐ {{ vinyl.note }}/10
                                            </span>
                                            <span>
                                                {{ formatDate(vinyl.date_ajout) }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex space-x-1">
                                        <Link :href="route('vinyl.show', vinyl.vinyl.id)"
                                              class="p-1.5 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900 rounded-md transition-colors"
                                              title="Voir les détails">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </Link>
                                        <button @click="openEditModal(vinyl)"
                                                class="p-1.5 text-green-600 hover:bg-green-100 dark:hover:bg-green-900 rounded-md transition-colors"
                                                :title="vinyl.can_edit ? 'Éditer le vinyle' : 'Consulter/Dupliquer le vinyle'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div v-if="vinyl.commentaires" class="mt-3 text-xs text-gray-600 dark:text-gray-300">
                                    {{ vinyl.commentaires }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Discogs -->
        <DiscogsVinylModal
            :show="showVinylModal"
            :collections="allCollections"
            @close="showVinylModal = false"
            @openManualModal="openManualVinylModal"
        />

        <!-- Modal ajout vinyle manuel -->
        <ManualVinylModal
            :show="showManualVinylModal"
            :collections="allCollections"
            @close="showManualVinylModal = false"
        />

        <!-- Modal édition vinyle -->
        <EditVinylModal
            v-if="vinylToEdit"
            :show="showEditVinylModal"
            :collection-vinyl="vinylToEdit"
            :collections="allCollections"
            @close="closeEditModal"
        />
    </AuthenticatedLayout>
</template>