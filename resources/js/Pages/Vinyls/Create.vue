<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

// Props reçues du contrôleur
const props = defineProps({
    collections: {
        type: Array,
        default: () => []
    }
});

// Rediriger vers le dashboard pour ouvrir la modal
router.visit('/dashboard', {
    onSuccess: () => {
        // On pourrait passer un paramètre pour ouvrir automatiquement la modal
        window.dispatchEvent(new CustomEvent('open-vinyl-modal'));
    }
});
</script>

<template>
    <Head title="Ajouter un vinyle" />
    
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Redirection...
            </h2>
        </template>
    </AuthenticatedLayout>
</template>