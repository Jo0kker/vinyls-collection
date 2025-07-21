<template>
    <div class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="hidden md:flex space-x-8 overflow-x-auto">
                
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

                
                <Link :href="route('forum.search')" 
                      :class="[
                          'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
                          route().current('forum.search') 
                              ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                      ]">
                    Rechercher
                </Link>
            </div>

            
            <div class="md:hidden py-3">
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
                                🏠 Accueil Forum
                            </Link>

                            <Link :href="route('forum.recent')" 
                                  @click="showMobileMenu = false"
                                  :class="[
                                      'block px-4 py-2 text-sm',
                                      route().current('forum.recent') 
                                          ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                                          : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600'
                                  ]">
                                📰 Messages récents
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
                                🔔 Non lues
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
                                💬 Mes discussions
                            </Link>

                            <Link :href="route('forum.search')" 
                                  @click="showMobileMenu = false"
                                  :class="[
                                      'block px-4 py-2 text-sm',
                                      route().current('forum.search') 
                                          ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                                          : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600'
                                  ]">
                                🔍 Rechercher
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const showMobileMenu = ref(false);
const $page = usePage();

// Fermer le menu quand on clique à l'extérieur
const closeMenu = () => {
    showMobileMenu.value = false;
};

onMounted(() => {
    document.addEventListener('click', closeMenu);
});

onUnmounted(() => {
    document.removeEventListener('click', closeMenu);
});

function getCurrentPageLabel() {
    const routeName = route().current();
    const user = $page.props.auth.user;
    
    switch (routeName) {
        case 'forum.index':
            return '🏠 Accueil Forum';
        case 'forum.recent':
            return '📰 Messages récents';
        case 'forum.unread':
            return '🔔 Non lues';
        case 'forum.my-threads':
            return '💬 Mes discussions';
        case 'forum.search':
            return '🔍 Rechercher';
        default:
            return '🏠 Accueil Forum';
    }
}
</script>