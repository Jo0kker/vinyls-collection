<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    isSending: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['send', 'typing']);

const content = ref('');
const lastTypingTime = ref(0);

const canSend = computed(() => {
    return content.value.trim() && !props.isSending;
});

const handleInput = () => {
    const now = Date.now();
    // Throttle typing indicator to once every 2 seconds
    if (now - lastTypingTime.value > 2000) {
        emit('typing');
        lastTypingTime.value = now;
    }
};

const handleKeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
    }
};

const send = () => {
    if (!canSend.value) return;

    emit('send', content.value.trim(), []);
    content.value = '';
};
</script>

<template>
    <div class="border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <!-- Input area -->
        <div class="flex items-end gap-2">
            <!-- Text input -->
            <div class="relative flex-1">
                <textarea
                    v-model="content"
                    @input="handleInput"
                    @keydown="handleKeydown"
                    placeholder="Écrire un message..."
                    rows="1"
                    class="w-full resize-none rounded-2xl border border-gray-300 bg-gray-50 px-4 py-3 pr-12 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400"
                    :class="{ 'opacity-50': isSending }"
                    :disabled="isSending"
                    style="max-height: 150px; min-height: 44px;"
                ></textarea>
            </div>

            <!-- Send button -->
            <button
                @click="send"
                :disabled="!canSend"
                class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <svg
                    v-if="isSending"
                    class="h-5 w-5 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg
                    v-else
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                </svg>
            </button>
        </div>
    </div>
</template>
