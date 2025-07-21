<template>
    <Head title="Messages récents" />

    <ForumLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Messages récents
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Dernières discussions
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Toutes les discussions récemment mises à jour
                            </p>
                        </div>

                        <div v-if="threads.data.length > 0">
                            <ThreadList :threads="threads" />
                            
                            
                            <div v-if="threads.links" class="mt-6">
                                <Pagination :links="threads.links" />
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-12">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                <p class="text-lg">Aucune discussion récente</p>
                                <p class="text-sm mt-2">Les nouvelles discussions apparaîtront ici</p>
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
import { Head } from '@inertiajs/vue3';
import ThreadList from '@/Components/ThreadList.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    threads: Object
});
</script>