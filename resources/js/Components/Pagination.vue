<template>
    <nav v-if="pagination.links && pagination.links.length > 3" class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
            
            <Link v-if="pagination.prev_page_url" 
                  :href="pagination.prev_page_url" 
                  class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:text-gray-400 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                Précédent
            </Link>
            <Link v-if="pagination.next_page_url" 
                  :href="pagination.next_page_url" 
                  class="ml-3 relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:text-gray-400 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                Suivant
            </Link>
        </div>
        
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            
            <div v-if="pagination.from && pagination.to && pagination.total">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Affichage de
                    <span class="font-medium">{{ pagination.from }}</span>
                    à
                    <span class="font-medium">{{ pagination.to }}</span>
                    sur
                    <span class="font-medium">{{ pagination.total }}</span>
                    résultats
                </p>
            </div>
            
            
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    
                    <Link v-if="pagination.prev_page_url" 
                          :href="pagination.prev_page_url" 
                          class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                        <span class="sr-only">Précédent</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </Link>
                    
                    
                    <template v-for="(link, index) in paginationLinks" :key="index">
                        <span v-if="link.label === '...'" 
                              class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                            ...
                        </span>
                        <Link v-else-if="link.url" 
                              :href="link.url" 
                              :class="[
                                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                  link.active 
                                      ? 'z-10 bg-blue-50 border-blue-500 text-blue-600 dark:bg-blue-900 dark:border-blue-400 dark:text-blue-300' 
                                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'
                              ]"
                              v-html="link.label">
                        </Link>
                        <span v-else 
                              :class="[
                                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                  link.active 
                                      ? 'z-10 bg-blue-50 border-blue-500 text-blue-600 dark:bg-blue-900 dark:border-blue-400 dark:text-blue-300' 
                                      : 'bg-white border-gray-300 text-gray-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300'
                              ]"
                              v-html="link.label">
                        </span>
                    </template>
                    
                    
                    <Link v-if="pagination.next_page_url" 
                          :href="pagination.next_page_url" 
                          class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                        <span class="sr-only">Suivant</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </Link>
                </nav>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pagination: {
        type: Object,
        required: true
    }
});

const paginationLinks = computed(() => {
    // Laravel's links array (first is prev, last is next, middle are page numbers)
    if (!props.pagination.links) return [];
    return props.pagination.links.slice(1, -1);
});
</script>