<script setup>
import { ref, onMounted } from 'vue';

const STORAGE_KEY = 'domain-change-banner-dismissed-v1';
const isVisible = ref(false);

onMounted(() => {
    isVisible.value = localStorage.getItem(STORAGE_KEY) !== '1';
});

const dismiss = () => {
    localStorage.setItem(STORAGE_KEY, '1');
    isVisible.value = false;
};
</script>

<template>
    <div
        v-if="isVisible"
        class="bg-gradient-to-r from-amber-500 to-orange-500 border-b border-orange-600"
        role="alert"
    >
        <div class="max-w-7xl mx-auto py-2 px-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-1">
                    <span class="flex p-1 rounded-lg bg-orange-700 shrink-0">
                        <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <p class="text-sm font-medium text-white">
                        <span class="sm:hidden">
                            Le site est désormais sur <strong>vinyls-collection.fr</strong>.
                        </span>
                        <span class="hidden sm:inline">
                            📢 Le nom de domaine a changé : <strong>vinyls-collection.com</strong> est devenu <strong>vinyls-collection.fr</strong>. Pensez à mettre à jour vos favoris !
                        </span>
                    </p>
                </div>
                <button
                    type="button"
                    @click="dismiss"
                    class="shrink-0 p-1 rounded-md hover:bg-orange-700/50 focus:outline-none focus:ring-2 focus:ring-white transition-colors"
                    aria-label="Fermer"
                >
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
