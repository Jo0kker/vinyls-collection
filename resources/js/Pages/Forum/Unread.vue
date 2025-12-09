<template>
    <Head title="Messages non lus" />

    <ForumLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Messages non lus
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Discussions non lues
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Discussions avec de nouveaux messages depuis votre dernière visite
                                </p>
                            </div>
                            <button v-if="threads.data.length > 0"
                                    @click="markAllAsRead"
                                    :disabled="markAllForm.processing"
                                    class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Tout marquer comme lu
                            </button>
                        </div>

                        <div v-if="threads.data.length > 0">
                            <ThreadList :threads="threads" />
                            
                            
                            <div v-if="threads.links && threads.links.length > 3" class="mt-6">
                                <Pagination :pagination="threads" />
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-12">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-lg">Aucun message non lu</p>
                                <p class="text-sm mt-2">Vous êtes à jour avec toutes les discussions</p>
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

defineProps({
    threads: Object
});

const markAllForm = useForm({});

function markAllAsRead() {
    if (confirm('Êtes-vous sûr de vouloir marquer toutes les discussions comme lues ?')) {
        markAllForm.post(route('forum.mark-all-read'));
    }
}
</script>