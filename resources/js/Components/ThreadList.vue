<template>
    <div class="space-y-3">
        <div v-for="thread in threads.data" :key="thread.id" 
             class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
            <!-- Desktop Layout -->
            <div class="hidden md:flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <Link :href="route('forum.thread.show', { thread_id: thread.id })" 
                              class="text-lg font-medium text-blue-600 dark:text-blue-400 hover:underline">
                            {{ thread.title }}
                        </Link>
                        <!-- Indicateurs visuels -->
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
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Par {{ thread.author.name }} • {{ formatDate(thread.created_at) }}
                        </p>
                        <span v-if="thread.category" class="text-xs">
                            dans 
                            <Link :href="route('forum.category.show', { category_id: thread.category.id })" 
                                  class="text-blue-600 dark:text-blue-400 hover:underline">
                                {{ thread.category.title }}
                            </Link>
                        </span>
                    </div>
                </div>
                <div class="text-right text-sm text-gray-500 dark:text-gray-400 ml-4 min-w-0">
                    <div class="font-medium">{{ thread.reply_count }} réponses</div>
                    <div v-if="thread.lastPost" class="mt-1">
                        <div class="text-xs">Dernier message par</div>
                        <div class="font-medium text-gray-700 dark:text-gray-300">{{ thread.lastPost.author.name }}</div>
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
            
            <!-- Mobile Layout -->
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
                    <div class="flex items-center gap-1 flex-wrap">
                        <span>Par {{ thread.author.name }}</span>
                        <span>•</span>
                        <span>{{ formatDate(thread.created_at) }}</span>
                        <span v-if="thread.category" class="flex items-center gap-1">
                            <span>•</span>
                            <Link :href="route('forum.category.show', { category_id: thread.category.id })" 
                                  class="text-blue-600 dark:text-blue-400 hover:underline">
                                {{ thread.category.title }}
                            </Link>
                        </span>
                    </div>
                </div>
                
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <div class="font-medium">{{ thread.reply_count }} réponses</div>
                    <div v-if="thread.lastPost" class="text-right">
                        <div>{{ thread.lastPost.author.name }}</div>
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
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
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