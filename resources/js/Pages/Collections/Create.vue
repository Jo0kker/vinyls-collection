<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    collection_nom: '',
    collection_commentaires: '',
    visibility: 'public'
});

const submit = () => {
    form.post('/collections');
};
</script>

<template>
    <Head title="Créer une collection" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link href="/collections" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                        ← Retour aux collections
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Créer une nouvelle collection
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <div class="mb-6">
                                <label for="collection_nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nom de la collection <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    id="collection_nom"
                                    v-model="form.collection_nom"
                                    type="text" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-500': form.errors.collection_nom }"
                                    placeholder="Ma nouvelle collection"
                                >
                                <p v-if="form.errors.collection_nom" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.collection_nom }}
                                </p>
                            </div>

                            <div class="mb-6">
                                <label for="collection_commentaires" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Description
                                </label>
                                <textarea 
                                    id="collection_commentaires"
                                    v-model="form.collection_commentaires"
                                    rows="4"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-500': form.errors.collection_commentaires }"
                                    placeholder="Décrivez votre collection..."
                                ></textarea>
                                <p v-if="form.errors.collection_commentaires" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.collection_commentaires }}
                                </p>
                            </div>

                            <div class="mb-6">
                                <label for="visibility" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Visibilité de la collection
                                </label>
                                <select 
                                    id="visibility"
                                    v-model="form.visibility"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-500': form.errors.visibility }"
                                >
                                    <option value="public">🌍 Publique - Visible par tous les collectionneurs</option>
                                    <option value="private">🔒 Privée - Visible uniquement par moi</option>
                                    <option value="friends">👥 Amis - Visible par mes amis (prochainement)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Les collections publiques apparaissent sur votre profil et dans les recherches.
                                </p>
                                <p v-if="form.errors.visibility" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.visibility }}
                                </p>
                            </div>

                            <div class="flex space-x-3">
                                <button 
                                    type="submit"
                                    :disabled="form.processing"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-colors disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Création...</span>
                                    <span v-else>Créer la collection</span>
                                </button>
                                
                                <Link href="/collections"
                                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-md transition-colors">
                                    Annuler
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Aide -->
                <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                Conseil
                            </h3>
                            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                <p>Organisez vos vinyles par thème, genre, époque ou selon vos préférences personnelles. Vous pourrez toujours modifier le nom et la description plus tard.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>