<script setup>
import { onMounted, onUnmounted } from 'vue';

defineProps({
    imageUrl: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['close']);

const handleKeydown = (e) => {
    if (e.key === 'Escape') {
        emit('close');
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    document.body.style.overflow = 'hidden';
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/90"
            @click.self="$emit('close')"
        >
            <!-- Close button -->
            <button
                @click="$emit('close')"
                class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white transition-colors hover:bg-white/20"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Image -->
            <img
                :src="imageUrl"
                class="max-h-[90vh] max-w-[90vw] object-contain"
                @click.stop
            />

            <!-- Download button -->
            <a
                :href="imageUrl"
                download
                target="_blank"
                class="absolute bottom-4 right-4 rounded-full bg-white/10 p-2 text-white transition-colors hover:bg-white/20"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
            </a>
        </div>
    </Teleport>
</template>
