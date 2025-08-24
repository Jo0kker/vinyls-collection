<template>
    <Head>
        <title>{{ category.title }} - Forum | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="description" :content="category.description || 'Catégorie ' + category.title + ' du forum de discussion sur les vinyles'" />
        <meta name="keywords" :content="category.title + ', forum vinyles, discussion vinyles'" />
        <meta property="og:title" :content="category.title + ' - Forum | ' + ($page.props.app?.name || 'Vinyls Collection')" />
        <meta property="og:description" :content="category.description || 'Catégorie ' + category.title + ' du forum de discussion sur les vinyles'" />
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="route('forum.category.show', category.id)" />
        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" :content="category.title + ' - Forum'" />
        <meta name="twitter:description" :content="category.description || 'Catégorie ' + category.title" />
        <link rel="canonical" :href="route('forum.category.show', category.id)" />
    </Head>
    
    <ForumStructuredData 
        type="category" 
        :data="category" 
    />

    <ForumLayout>
        <template #header>
            <div class="flex items-center space-x-3">
                <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: category.color_light_mode }"></div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ category.title }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        
                        <nav class="mb-6 text-sm">
                            <Link :href="route('forum.index')" 
                                  class="text-blue-600 dark:text-blue-400 hover:underline">
                                Forum
                            </Link>
                            <span class="mx-2 text-gray-500">/</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ category.title }}</span>
                        </nav>

                        
                        <div v-if="category.description" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-gray-700 dark:text-gray-300">{{ category.description }}</p>
                        </div>

                        
                        <div class="mb-6 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Discussions
                            </h3>
                            <Link :href="route('forum.thread.create', { category_id: category.id })" 
                                  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                Nouvelle discussion
                            </Link>
                        </div>

                        
                        <div class="space-y-3">
                            <div v-for="thread in threads.data" :key="thread.id" 
                                 class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                                
                                <div class="hidden md:flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <Link :href="route('forum.thread.show', { thread_id: thread.id })" 
                                                  class="text-lg font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                                {{ thread.title }}
                                            </Link>
                                            
                                            <div class="flex items-center gap-1">
                                                <span v-if="thread.pinned" 
                                                      class="inline-flex items-center px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 rounded-full">
                                                    📌 Épinglé
                                                </span>
                                                <span v-if="thread.locked" 
                                                      class="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded-full">
                                                    🔒 Verrouillé
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Par <Link :href="`/collectors/${thread.author.id}`" 
                                                class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">{{ thread.author.name }}</Link> • {{ formatDate(thread.created_at) }}
                                        </p>
                                    </div>
                                    <div class="text-right text-sm text-gray-500 dark:text-gray-400 ml-4 min-w-0">
                                        <div class="font-medium">{{ thread.reply_count }} réponses</div>
                                        <div v-if="thread.lastPost" class="mt-1">
                                            <div class="text-xs">Dernier message par</div>
                                            <div class="font-medium text-gray-700 dark:text-gray-300">
                                                <Link :href="`/collectors/${thread.lastPost.author.id}`" 
                                                      class="hover:text-purple-600 dark:hover:text-purple-400">{{ thread.lastPost.author.name }}</Link>
                                            </div>
                                            <Link :href="getLastPageUrl(thread)" 
                                                  class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                                {{ formatRelativeTime(thread.lastPost.created_at) }}
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </Link>
                                        </div>
                                        <div v-else class="mt-1 text-xs text-gray-400">
                                            Aucun message
                                        </div>
                                    </div>
                                </div>
                                
                                
                                <div class="md:hidden">
                                    <div class="flex items-start justify-between mb-2">
                                        <Link :href="route('forum.thread.show', { thread_id: thread.id })" 
                                              class="text-base font-medium text-blue-600 dark:text-blue-400 hover:underline flex-1 mr-2">
                                            {{ thread.title }}
                                        </Link>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <span v-if="thread.pinned" 
                                                  class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 rounded-full">
                                                📌
                                            </span>
                                            <span v-if="thread.locked" 
                                                  class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded-full">
                                                🔒
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                        Par <Link :href="`/collectors/${thread.author.id}`" 
                                            class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">{{ thread.author.name }}</Link> • {{ formatDate(thread.created_at) }}
                                    </div>
                                    
                                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                        <div class="font-medium">{{ thread.reply_count }} réponses</div>
                                        <div v-if="thread.lastPost" class="text-right">
                                            <div>
                                                <Link :href="`/collectors/${thread.lastPost.author.id}`" 
                                                      class="hover:text-purple-600 dark:hover:text-purple-400">{{ thread.lastPost.author.name }}</Link>
                                            </div>
                                            <Link :href="getLastPageUrl(thread)" 
                                                  class="text-blue-600 dark:text-blue-400 hover:underline">
                                                {{ formatRelativeTime(thread.lastPost.created_at) }}
                                            </Link>
                                        </div>
                                        <div v-else class="text-right">
                                            Aucun message
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div v-if="threads.links" class="mt-6">
                            <Pagination :links="threads.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ForumLayout>
</template>

<script setup>
import ForumLayout from '@/Layouts/ForumLayout.vue';
import ForumStructuredData from '@/Components/Forum/ForumStructuredData.vue';
import { Head, Link } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    category: Object,
    threads: Object
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatRelativeTime(date) {
    const now = new Date();
    const messageDate = new Date(date);
    const diffInSeconds = Math.floor((now - messageDate) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Il y a moins d\'une minute';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`;
    } else if (diffInSeconds < 2592000) {
        const days = Math.floor(diffInSeconds / 86400);
        return `Il y a ${days} jour${days > 1 ? 's' : ''}`;
    } else {
        return messageDate.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    }
}

function getLastPageUrl(thread) {
    // Calculer la dernière page (20 posts par page)
    const postsPerPage = 20;
    const totalPosts = thread.reply_count + 1; // +1 pour le post initial
    const lastPage = Math.ceil(totalPosts / postsPerPage);
    
    // Générer l'URL vers la dernière page
    const baseUrl = route('forum.thread.show', { thread_id: thread.id });
    return lastPage > 1 ? `${baseUrl}?page=${lastPage}` : baseUrl;
}
</script>