<template>
    <div v-if="show"
         role="dialog"
         aria-modal="true"
         aria-labelledby="reply-modal-title"
         class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50"
         @click.self="close"
         @keydown.esc="close">
        <div class="relative top-4 mx-auto p-6 border w-full max-w-5xl shadow-lg rounded-lg bg-white dark:bg-gray-800" @click.stop>
            <div class="flex items-center justify-between mb-6">
                <h3 id="reply-modal-title" class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    Répondre à la discussion
                </h3>
                <button @click="close"
                        aria-label="Fermer la modale"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <TinyMCEEditor
                    v-model="localContent"
                    :height="500"
                    :disabled="disabled"
                />

                <p v-if="error" class="text-sm text-red-600 dark:text-red-400">
                    {{ error }}
                </p>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Ce formulaire est protégé par Google reCAPTCHA.
                </p>

                <div class="flex justify-end gap-3 pt-4 border-t dark:border-gray-700">
                    <button type="button"
                            @click="close"
                            class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-medium">
                        Annuler
                    </button>
                    <button type="submit"
                            :disabled="disabled"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg font-medium">
                        Répondre
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import TinyMCEEditor from '@/Components/TinyMCEEditor.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    content: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['close', 'submit', 'update:content']);

const localContent = ref(props.content || '');

watch(() => props.content, (newValue) => {
    localContent.value = newValue || '';
});

watch(localContent, (newValue) => {
    emit('update:content', newValue);
});

function close() {
    emit('close');
}

function submit() {
    emit('submit');
}
</script>
