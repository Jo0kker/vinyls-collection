<template>
    <Head>
        <title>{{ query ? `Recherche : ${query}` : 'Recherche' }} | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="description" content="Recherchez des discussions, messages et sujets dans le forum des collectionneurs de vinyles" />
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <ForumLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Recherche
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6 text-gray-900 dark:text-gray-100">
                        <!-- Results -->
                        <div v-if="query && threads.data && threads.data.length > 0">
                            <div class="mb-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ threads.total }} résultat{{ threads.total > 1 ? 's' : '' }} pour "<span class="font-medium">{{ query }}</span>"
                                </p>
                            </div>

                            <ThreadList :threads="threads" />

                            <div v-if="threads.links && threads.links.length > 3" class="mt-6">
                                <Pagination :pagination="threads" />
                            </div>
                        </div>

                        <!-- No results -->
                        <div v-else-if="query && (!threads.data || threads.data.length === 0)" class="text-center py-12">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <p class="text-lg">Aucun résultat pour "{{ query }}"</p>
                                <p class="text-sm mt-2">Essayez avec des termes différents ou moins spécifiques</p>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-else class="text-center py-12">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <p class="text-lg">Rechercher dans le forum</p>
                                <p class="text-sm mt-2">Utilisez la barre de recherche ci-dessus pour trouver des discussions</p>
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
    threads: Object,
    query: String
});
</script>
