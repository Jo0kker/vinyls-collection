<template>
    <div class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Desktop navigation -->
            <div class="hidden md:flex items-center justify-between">
                <div class="flex space-x-8 overflow-x-auto">
                    <Link :href="route('forum.index')"
                          :class="[
                              'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
                              route().current('forum.index')
                                  ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                          ]">
                        Accueil Forum
                    </Link>

                    <Link :href="route('forum.recent')"
                          :class="[
                              'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
                              route().current('forum.recent')
                                  ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                          ]">
                        Messages récents
                    </Link>

                    <Link v-if="$page.props.auth.user"
                          :href="route('forum.unread')"
                          :class="[
                              'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
                              route().current('forum.unread')
                                  ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                          ]">
                        Non lues
                    </Link>

                    <Link v-if="$page.props.auth.user"
                          :href="route('forum.my-threads')"
                          :class="[
                              'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
                              route().current('forum.my-threads')
                                  ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                          ]">
                        Mes discussions
                    </Link>
                </div>

                <!-- Search bar desktop -->
                <form @submit.prevent="search" class="flex items-center gap-2 py-2">
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Rechercher..."
                            class="w-48 lg:w-64 pl-9 pr-3 py-1.5 text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <button
                        type="submit"
                        :disabled="!searchQuery.trim()"
                        class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-md transition-colors">
                        Rechercher
                    </button>
                </form>
            </div>

            <!-- Mobile navigation -->
            <div class="md:hidden py-3 space-y-3">
                <!-- Search bar mobile -->
                <form @submit.prevent="search" class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Rechercher dans le forum..."
                            class="w-full pl-9 pr-3 py-2 text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <button
                        type="submit"
                        :disabled="!searchQuery.trim()"
                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-md transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </form>

                <!-- Navigation dropdown mobile -->
                <div class="relative">
                    <button @click.stop="showMobileMenu = !showMobileMenu"
                            class="w-full flex items-center justify-between px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <span>{{ getCurrentPageLabel() }}</span>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showMobileMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div v-if="showMobileMenu" class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg z-10"
                         @click.stop>
                        <div class="py-1">
                            <Link :href="route('forum.index')"
                                  @click="showMobileMenu = false"
                                  :class="[
                                      'block px-4 py-2 text-sm',
                                      route().current('forum.index')
                                          ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                                          : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600'
                                  ]">
                                Accueil Forum
                            </Link>

                            <Link :href="route('forum.recent')"
                                  @click="showMobileMenu = false"
                                  :class="[
                                      'block px-4 py-2 text-sm',
                                      route().current('forum.recent')
                                          ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                                          : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600'
                                  ]">
                                Messages récents
                            </Link>

                            <Link v-if="$page.props.auth.user"
                                  :href="route('forum.unread')"
                                  @click="showMobileMenu = false"
                                  :class="[
                                      'block px-4 py-2 text-sm',
                                      route().current('forum.unread')
                                          ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                                          : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600'
                                  ]">
                                Non lues
                            </Link>

                            <Link v-if="$page.props.auth.user"
                                  :href="route('forum.my-threads')"
                                  @click="showMobileMenu = false"
                                  :class="[
                                      'block px-4 py-2 text-sm',
                                      route().current('forum.my-threads')
                                          ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                                          : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600'
                                  ]">
                                Mes discussions
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const showMobileMenu = ref(false);
const $page = usePage();

// Pre-fill search query if we're on search page
const searchQuery = ref($page.props.query || '');

onMounted(() => {
    document.addEventListener('click', closeMenu);
});

onUnmounted(() => {
    document.removeEventListener('click', closeMenu);
});

const closeMenu = () => {
    showMobileMenu.value = false;
};

function search() {
    if (searchQuery.value.trim()) {
        router.get(route('forum.search'), { q: searchQuery.value.trim() });
    }
}

function getCurrentPageLabel() {
    const routeName = route().current();

    switch (routeName) {
        case 'forum.index':
            return 'Accueil Forum';
        case 'forum.recent':
            return 'Messages récents';
        case 'forum.unread':
            return 'Non lues';
        case 'forum.my-threads':
            return 'Mes discussions';
        case 'forum.search':
            return 'Recherche';
        default:
            return 'Forum';
    }
}
</script>
