<template>
    <Head title="Mes discussions" />

    <ForumLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Mes discussions
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Mes discussions
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Toutes les discussions que vous avez créées
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                <p class="text-lg">Aucune discussion créée</p>
                                <p class="text-sm mt-2">Créez votre première discussion pour commencer</p>
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