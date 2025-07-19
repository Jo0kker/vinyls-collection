<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    collection: {
        type: Object,
        required: true
    }
});

const form = useForm({
    collection_nom: props.collection.collection_nom,
    collection_commentaires: props.collection.collection_commentaires || '',
    visibility: props.collection.visibility || 'public'
});

const submit = () => {
    form.put(`/collections/${props.collection.id}`);
};
</script>

<template>
    <Head :title="`Modifier ${collection.collection_nom}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link :href="`/collections/${collection.id}`" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                        ← Retour à la collection
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Modifier "{{ collection.collection_nom }}"
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

                            <div class="flex items-center justify-between">
                                <div class="flex space-x-3">
                                    <button 
                                        type="submit"
                                        :disabled="form.processing"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-colors disabled:opacity-50"
                                    >
                                        <span v-if="form.processing">Modification...</span>
                                        <span v-else>Modifier la collection</span>
                                    </button>
                                    
                                    <Link :href="`/collections/${collection.id}`"
                                       class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-md transition-colors">
                                        Annuler
                                    </Link>
                                </div>

                                <Link :href="`/collections/${collection.id}`" 
                                   method="delete" 
                                   as="button"
                                   class="text-red-600 hover:text-red-800 text-sm"
                                   @before="confirm('Êtes-vous sûr de vouloir supprimer cette collection ? Cette action est irréversible.')">
                                    Supprimer la collection
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>