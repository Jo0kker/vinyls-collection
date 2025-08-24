<template>
    <Head>
        <title>Forum - Discussions Vinyles | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="description" content="Forum de discussion sur les vinyles, collections, conseils et échanges. Rejoignez la communauté des collectionneurs de vinyles." />
        <meta name="keywords" content="forum vinyles, discussion vinyles, collection vinyles, communauté collectionneurs" />
        <meta property="og:title" content="Forum - Discussions Vinyles | {{ $page.props.app?.name || 'Vinyls Collection' }}" />
        <meta property="og:description" content="Forum de discussion sur les vinyles, collections, conseils et échanges. Rejoignez la communauté des collectionneurs de vinyles." />
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="route('forum.index')" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Forum - Discussions Vinyles | {{ $page.props.app?.name || 'Vinyls Collection' }}" />
        <meta name="twitter:description" content="Forum de discussion sur les vinyles, collections, conseils et échanges." />
        <link rel="canonical" :href="route('forum.index')" />
    </Head>
    
    <ForumStructuredData 
        type="forum" 
        :data="{ appName: $page.props.app?.name || 'Vinyls Collection' }" 
    />

    <ForumLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Forum
                </h2>
                <div v-if="canManageCategories" class="flex gap-2">
                    <Link :href="route('forum.category.manage')"
                          class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Gérer les catégories
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        
                        <div class="space-y-6">
                            <div v-for="category in props.categories" :key="category.id">
                                
                                <div class="border dark:border-gray-700 rounded-lg overflow-hidden">
                                    <div class="bg-gray-50 dark:bg-gray-700 p-4 border-b dark:border-gray-600">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4">
                                                <div class="w-4 h-4 rounded-full" 
                                                     :style="{ backgroundColor: category.color_light_mode }"></div>
                                                <div>
                                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                        {{ category.title }}
                                                    </h3>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                        {{ category.description }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div v-if="category.accepts_threads" class="text-right text-sm text-gray-500 dark:text-gray-400">
                                                <div>{{ category.threads_count }} discussions</div>
                                                <div>{{ category.posts_count }} messages</div>
                                            </div>
                                        </div>
                                    </div>

                                    
                                    <div v-if="category.children && category.children.length > 0" class="divide-y dark:divide-gray-600">
                                        <div v-for="subCategory in category.children" :key="subCategory.id" 
                                             class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-4">
                                                    <div class="w-3 h-3 rounded-full ml-4" 
                                                         :style="{ backgroundColor: subCategory.color_light_mode }"></div>
                                                    <div>
                                                        <Link v-if="subCategory?.id && subCategory.accepts_threads" 
                                                              :href="route('forum.category.show', { category_id: subCategory.id })" 
                                                              class="text-base font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                                            {{ subCategory.title }}
                                                        </Link>
                                                        <span v-else class="text-base font-medium text-gray-800 dark:text-gray-200">
                                                            {{ subCategory?.title || 'Catégorie inconnue' }}
                                                        </span>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                            {{ subCategory.description }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                                                    <div>{{ subCategory.threads_count }} discussions</div>
                                                    <div>{{ subCategory.posts_count }} messages</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    
                                    <div v-else class="p-4 text-center text-gray-500 dark:text-gray-400">
                                        <p class="text-sm">Cette catégorie contient des sous-sections.</p>
                                    </div>
                                </div>
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
import ForumStructuredData from '@/Components/Forum/ForumStructuredData.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const $page = usePage();

const props = defineProps({
    categories: Array
});

const canManageCategories = computed(() => {
    const user = $page.props.auth.user;
    return user && user.roles?.some(role => role.name === 'admin');
});
</script>