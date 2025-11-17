<template>
    <ConfirmationModal
        :show="show"
        :title="title"
        :message="message"
        :confirmText="confirmText"
        :cancelText="cancelText"
        type="danger"
        @close="$emit('close')"
        @confirm="handleConfirm"
    />
</template>

<script setup>
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    collection: {
        type: Object,
        required: true
    },
    confirmText: {
        type: String,
        default: 'Supprimer la collection'
    },
    cancelText: {
        type: String,
        default: 'Annuler'
    }
});

const emit = defineEmits(['close', 'confirm']);

const vinylsCount = computed(() => {
    return props.collection.vinyls_count || props.collection.collection_vinyls?.length || 0;
});

const title = computed(() => {
    return `Supprimer "${props.collection.collection_nom}" ?`;
});

const message = computed(() => {
    const count = vinylsCount.value;
    
    if (count === 0) {
        return `Êtes-vous sûr de vouloir supprimer cette collection ?\n\nCette action est irréversible.`;
    } else if (count === 1) {
        return `Cette collection contient 1 vinyle.\n\nÊtes-vous sûr de vouloir supprimer cette collection et son vinyle ?\n\nCette action est irréversible.`;
    } else {
        return `Cette collection contient ${count} vinyles.\n\nÊtes-vous sûr de vouloir supprimer cette collection et ses ${count} vinyles ?\n\nCette action est irréversible.`;
    }
});

const handleConfirm = () => {
    router.delete(`/collections/${props.collection.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            emit('confirm');
        },
        onError: (errors) => {
            console.error('Erreur lors de la suppression:', errors);
        }
    });
};
</script>
