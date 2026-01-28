<script setup>
import { ref, computed, nextTick, watch } from 'vue';
import MessageItem from './MessageItem.vue';
import { useMessaging } from '@/composables/useMessaging';

const props = defineProps({
    conversation: {
        type: Object,
        required: true,
    },
    currentUserId: {
        type: Number,
        required: true,
    },
    hasMore: {
        type: Boolean,
        default: false,
    },
    isLoadingMore: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['loadMore']);

const { messages } = useMessaging();
const containerRef = ref(null);
const isNearBottom = ref(true);

// Group messages by date
const groupedMessages = computed(() => {
    const groups = [];
    let currentDate = null;

    for (const message of messages.value) {
        const messageDate = new Date(message.created_at).toLocaleDateString('fr-FR', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });

        if (messageDate !== currentDate) {
            currentDate = messageDate;
            groups.push({ type: 'date', date: messageDate });
        }

        groups.push({ type: 'message', message });
    }

    return groups;
});

const handleScroll = (e) => {
    const container = e.target;
    const threshold = 100;

    // Check if near bottom
    isNearBottom.value = container.scrollHeight - container.scrollTop - container.clientHeight < threshold;

    // Load more when scrolling to top
    if (container.scrollTop < threshold && props.hasMore && !props.isLoadingMore) {
        const previousScrollHeight = container.scrollHeight;
        emit('loadMore');

        // Maintain scroll position after loading more
        nextTick(() => {
            const newScrollHeight = container.scrollHeight;
            container.scrollTop = newScrollHeight - previousScrollHeight;
        });
    }
};

const scrollToBottom = (smooth = false) => {
    if (containerRef.value) {
        containerRef.value.scrollTo({
            top: containerRef.value.scrollHeight,
            behavior: smooth ? 'smooth' : 'instant',
        });
    }
};

// Auto-scroll when new messages arrive (if near bottom)
watch(
    () => messages.value.length,
    () => {
        if (isNearBottom.value) {
            nextTick(() => scrollToBottom(true));
        }
    }
);

defineExpose({ scrollToBottom });
</script>

<template>
    <div
        ref="containerRef"
        class="flex-1 overflow-y-auto px-4 py-4"
        @scroll="handleScroll"
    >
        <!-- Loading indicator -->
        <div v-if="isLoadingMore" class="mb-4 flex justify-center">
            <div class="flex items-center gap-2 rounded-full bg-gray-100 px-4 py-2 text-sm text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Chargement...
            </div>
        </div>

        <!-- Load more button -->
        <div v-else-if="hasMore" class="mb-4 flex justify-center">
            <button
                @click="$emit('loadMore')"
                class="rounded-full bg-gray-100 px-4 py-2 text-sm text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
            >
                Charger les messages précédents
            </button>
        </div>

        <!-- Messages -->
        <div class="space-y-1">
            <template v-for="(item, index) in groupedMessages" :key="index">
                <!-- Date separator -->
                <div v-if="item.type === 'date'" class="my-4 flex items-center justify-center">
                    <span class="rounded-full bg-gray-200 px-3 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        {{ item.date }}
                    </span>
                </div>

                <!-- Message -->
                <MessageItem
                    v-else
                    :message="item.message"
                    :is-own-message="item.message.sender_id === currentUserId"
                    :show-avatar="conversation.type === 'group' || conversation.type === 'support'"
                />
            </template>
        </div>

        <!-- Empty state -->
        <div v-if="messages.length === 0" class="flex h-full items-center justify-center">
            <div class="text-center text-gray-500 dark:text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <p class="mt-2">Commencez la conversation !</p>
            </div>
        </div>
    </div>
</template>
