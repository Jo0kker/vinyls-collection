<template>
    <Head title="Gestion des catégories" />

    <ForumLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Gestion des catégories
                </h2>
                <button @click="showCreateModal = true"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Créer une catégorie
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                💡 Glissez-déposez les catégories pour changer leur ordre d'affichage
                            </p>
                            <draggable 
                                v-model="sortableCategories" 
                                @end="updateCategoriesOrder"
                                :options="{ animation: 150, ghostClass: 'sortable-ghost' }"
                                class="space-y-4"
                            >
                                <div v-for="category in sortableCategories" :key="category.id"
                                     class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 cursor-move hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2"
                                            :style="{ color: category.color_light_mode }">
                                            {{ category.title }}
                                        </h3>
                                        <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">
                                            {{ category.description }}
                                        </p>
                                        
                                        
                                        <div v-if="category.children && category.children.length > 0" class="ml-6 space-y-2">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                📋 Sous-catégories (glissez-déposez pour réorganiser)
                                            </p>
                                            <draggable 
                                                v-model="category.children"
                                                @end="() => updateSubCategoriesOrder(category)"
                                                :options="{ animation: 150, ghostClass: 'sortable-ghost' }"
                                                class="space-y-2"
                                            >
                                                <div v-for="child in category.children" :key="child.id"
                                                     class="bg-gray-50 dark:bg-gray-600 border border-gray-200 dark:border-gray-500 rounded p-3 cursor-move hover:shadow-sm transition-shadow">
                                                <div class="flex justify-between items-start">
                                                    <div class="flex-1">
                                                        <h4 class="font-medium text-gray-800 dark:text-white"
                                                            :style="{ color: child.color_light_mode }">
                                                            {{ child.title }}
                                                        </h4>
                                                        <p class="text-gray-600 dark:text-gray-300 text-sm">
                                                            {{ child.description }}
                                                        </p>
                                                    </div>
                                                    <div class="flex gap-2 ml-4">
                                                        <button @click="editCategory(child)"
                                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                                            Modifier
                                                        </button>
                                                        <button @click="deleteCategory(child)"
                                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm">
                                                            Supprimer
                                                        </button>
                                                    </div>
                                                </div>
                                                </div>
                                            </draggable>
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-2 ml-4">
                                        <button @click="editCategory(category)"
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                            Modifier
                                        </button>
                                        <button @click="deleteCategory(category)"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm">
                                            Supprimer
                                        </button>
                                    </div>
                                    </div>
                                </div>
                            </draggable>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showCreateModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800" @click.stop>
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Créer une catégorie
                    </h3>
                    <form @submit.prevent="submitCreate">
                        <div class="space-y-4">
                            <div>
                                <label for="create-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Titre
                                </label>
                                <input v-model="createForm.title" 
                                       id="create-title"
                                       type="text" 
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            
                            <div>
                                <label for="create-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Description
                                </label>
                                <textarea v-model="createForm.description" 
                                          id="create-description"
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                            </div>
                            
                            <div>
                                <label for="create-parent" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Catégorie parent (optionnel)
                                </label>
                                <select v-model="createForm.parent_id" 
                                        id="create-parent"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Aucune (catégorie principale)</option>
                                    <option v-for="category in mainCategories" :key="category.id" :value="category.id">
                                        {{ category.title }}
                                    </option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="create-color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Couleur
                                </label>
                                <input v-model="createForm.color_light_mode" 
                                       id="create-color"
                                       type="color" 
                                       class="w-full h-10 px-1 py-1 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="flex items-center">
                                <input v-model="createForm.accepts_threads" 
                                       id="create-accepts-threads"
                                       type="checkbox" 
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="create-accepts-threads" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Accepte les threads
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input v-model="createForm.is_private" 
                                       id="create-is-private"
                                       type="checkbox" 
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="create-is-private" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Catégorie privée
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" 
                                    @click="showCreateModal = false"
                                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md text-sm">
                                Annuler
                            </button>
                            <button type="submit" 
                                    :disabled="createForm.processing"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-md text-sm">
                                Créer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
        <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showEditModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800" @click.stop>
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Modifier la catégorie
                    </h3>
                    <form @submit.prevent="submitEdit">
                        <div class="space-y-4">
                            <div>
                                <label for="edit-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Titre
                                </label>
                                <input v-model="editForm.title" 
                                       id="edit-title"
                                       type="text" 
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            
                            <div>
                                <label for="edit-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Description
                                </label>
                                <textarea v-model="editForm.description" 
                                          id="edit-description"
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                            </div>
                            
                            <div>
                                <label for="edit-color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Couleur
                                </label>
                                <input v-model="editForm.color_light_mode" 
                                       id="edit-color"
                                       type="color" 
                                       class="w-full h-10 px-1 py-1 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="flex items-center">
                                <input v-model="editForm.accepts_threads" 
                                       id="edit-accepts-threads"
                                       type="checkbox" 
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="edit-accepts-threads" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Accepte les threads
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input v-model="editForm.is_private" 
                                       id="edit-is-private"
                                       type="checkbox" 
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="edit-is-private" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Catégorie privée
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" 
                                    @click="showEditModal = false"
                                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md text-sm">
                                Annuler
                            </button>
                            <button type="submit" 
                                    :disabled="editForm.processing"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-md text-sm">
                                Modifier
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </ForumLayout>
</template>

<script setup>
import ForumLayout from '@/Layouts/ForumLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { VueDraggableNext as draggable } from 'vue-draggable-next';

const props = defineProps({
    categories: Array
});

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingCategory = ref(null);

// Forms
const createForm = useForm({
    title: '',
    description: '',
    parent_id: '',
    color_light_mode: '#3b82f6',
    accepts_threads: true,
    is_private: false
});

const editForm = useForm({
    title: '',
    description: '',
    color_light_mode: '#3b82f6',
    accepts_threads: true,
    is_private: false
});

// Reactive categories for drag & drop
const sortableCategories = ref([...props.categories]);

// Computed
const mainCategories = computed(() => {
    return props.categories.filter(cat => !cat.parent_id);
});

// Methods
function submitCreate() {
    createForm.post(route('forum.category.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
}

function editCategory(category) {
    editingCategory.value = category;
    editForm.title = category.title;
    editForm.description = category.description || '';
    editForm.color_light_mode = category.color_light_mode || '#3b82f6';
    editForm.accepts_threads = category.accepts_threads;
    editForm.is_private = category.is_private;
    showEditModal.value = true;
}

function submitEdit() {
    editForm.put(route('forum.category.update', editingCategory.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editingCategory.value = null;
        }
    });
}

function deleteCategory(category) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la catégorie "${category.title}" ?`)) {
        router.delete(route('forum.category.destroy', { category_id: category.id }));
    }
}

// Drag & Drop functionality
function updateCategoriesOrder() {
    // Prepare the data for the API call
    const orderedCategories = sortableCategories.value.map((category, index) => ({
        id: category.id,
        order: index
    }));

    // Send to backend
    router.post(route('forum.category.bulk-manage'), {
        categories: orderedCategories
    }, {
        preserveScroll: true,
        onError: () => {
            // Reload page to reset order in case of error
            router.reload();
        }
    });
}

// Drag & Drop functionality for sub-categories
function updateSubCategoriesOrder(parentCategory) {
    // Prepare the data for the API call for sub-categories
    const orderedSubCategories = parentCategory.children.map((child, index) => ({
        id: child.id,
        order: index
    }));

    // Send to backend
    router.post(route('forum.category.bulk-manage'), {
        categories: orderedSubCategories
    }, {
        preserveScroll: true,
        onError: () => {
            // Reload page to reset order in case of error
            router.reload();
        }
    });
}
</script>

<style scoped>
.sortable-ghost {
    opacity: 0.5;
    background: #f3f4f6;
    border: 2px dashed #9ca3af;
}

.sortable-ghost.dark {
    background: #374151;
    border-color: #6b7280;
}

/* Drag handle style */
.cursor-move:hover {
    background-color: #f9fafb;
}

.dark .cursor-move:hover {
    background-color: #4b5563;
}
</style>