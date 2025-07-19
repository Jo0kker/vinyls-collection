<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: {
        type: Object,
        required: true
    },
    search: {
        type: String,
        default: ''
    }
});

const searchQuery = ref(props.search);

const performSearch = () => {
    router.get('/collectors', {
        search: searchQuery.value
    }, {
        preserveState: true
    });
};

const clearSearch = () => {
    searchQuery.value = '';
    performSearch();
};
</script>

<template>
    <Head title="Collectionneurs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Collectionneurs
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Barre de recherche -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center">
                            <div class="flex-1 max-w-md">
                                <div class="relative">
                                    <input 
                                        v-model="searchQuery"
                                        @keyup.enter="performSearch"
                                        type="text" 
                                        placeholder="Rechercher un collectionneur..."
                                        class="w-full pl-10 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    >
                                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <button v-if="searchQuery" 
                                            @click="clearSearch"
                                            class="absolute right-3 top-2.5 h-5 w-5 text-gray-400 hover:text-gray-600">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button @click="performSearch"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                Rechercher
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Liste des collectionneurs -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Collectionneurs 
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                    ({{ users.total }} collectionneur{{ users.total > 1 ? 's' : '' }})
                                </span>
                            </h3>
                        </div>

                        <div v-if="users.data.length === 0" class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun collectionneur trouvé</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Essayez de modifier vos critères de recherche.
                            </p>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            <Link v-for="user in users.data" :key="user.id"
                                  :href="`/collectors/${user.id}`"
                                  class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                
                                <!-- Avatar -->
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-lg">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="font-medium text-gray-900 dark:text-white">
                                            {{ user.name }}
                                        </h4>
                                        <p v-if="user.location" class="text-sm text-gray-500 dark:text-gray-400">
                                            📍 {{ user.location }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Bio -->
                                <p v-if="user.bio" class="text-sm text-gray-600 dark:text-gray-300 mb-4 line-clamp-3">
                                    {{ user.bio }}
                                </p>

                                <!-- Stats -->
                                <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ user.public_collections_count || 0 }} collection{{ (user.public_collections_count || 0) > 1 ? 's' : '' }}</span>
                                    <span>{{ user.vinyl_collections_count || 0 }} vinyle{{ (user.vinyl_collections_count || 0) > 1 ? 's' : '' }}</span>
                                </div>
                            </Link>
                        </div>

                        <!-- Pagination -->
                        <div v-if="users.last_page > 1" class="mt-8 flex justify-center">
                            <div class="flex items-center space-x-2">
                                <Link v-if="users.prev_page_url" 
                                      :href="users.prev_page_url"
                                      class="px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">
                                    Précédent
                                </Link>
                                
                                <span class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                    Page {{ users.current_page }} sur {{ users.last_page }}
                                </span>
                                
                                <Link v-if="users.next_page_url" 
                                      :href="users.next_page_url"
                                      class="px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">
                                    Suivant
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>