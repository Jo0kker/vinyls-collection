<template>
    <Head>
        <title>Recherche dans le forum | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="description" content="Recherchez des discussions, messages et sujets dans le forum des collectionneurs de vinyles" />
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <ForumLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Recherche
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Rechercher dans les discussions
                            </h3>
                            
                            
                            <form @submit.prevent="search" class="mb-6">
                                <div class="flex gap-2">
                                    <input 
                                        v-model="searchForm.q"
                                        type="text"
                                        placeholder="Rechercher dans les titres..."
                                        class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                    <button 
                                        type="submit"
                                        :disabled="searchForm.processing"
                                        class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-6 py-2 rounded-lg">
                                        Rechercher
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div v-if="query && threads.data.length > 0">
                            <div class="mb-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Résultats pour "{{ query }}"
                                </p>
                            </div>
                            
                            <ThreadList :threads="threads" />
                            
                            
                            <div v-if="threads.links && threads.links.length > 3" class="mt-6">
                                <Pagination :pagination="threads" />
                            </div>
                        </div>
                        
                        <div v-else-if="query && threads.data.length === 0" class="text-center py-12">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <p class="text-lg">Aucun résultat trouvé</p>
                                <p class="text-sm mt-2">Essayez avec des termes différents</p>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-12">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <p class="text-lg">Rechercher dans les discussions</p>
                                <p class="text-sm mt-2">Entrez un terme de recherche pour commencer</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ForumLayout>
</template>

<script setup>
import ForumLayout from '@/Layouts/ForumLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import ThreadList from '@/Components/ThreadList.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    threads: Object,
    query: String
});

const searchForm = useForm({
    q: props.query || ''
});

function search() {
    searchForm.get(route('forum.search'), {
        preserveState: true,
        preserveScroll: true,
    });
}
</script>