<script setup>
import { formatDistanceToNow } from 'date-fns';
import { fr } from 'date-fns/locale';

defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select', 'new']);

const formatTime = (dateString) => {
    if (!dateString) return '';
    try {
        return formatDistanceToNow(new Date(dateString), { addSuffix: false, locale: fr });
    } catch {
        return '';
    }
};

const truncate = (text, length = 40) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};
</script>

<template>
    <div class="flex h-full flex-col">
        <!-- Loading -->
        <div v-if="isLoading" class="flex flex-1 items-center justify-center">
            <svg class="h-8 w-8 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Empty state -->
        <div v-else-if="conversations.length === 0" class="flex flex-1 flex-col items-center justify-center p-6 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                Aucune conversation
            </p>
            <button
                @click="$emit('new')"
                class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
            >
                Démarrer une conversation
            </button>
        </div>

        <!-- Conversation list -->
        <div v-else class="flex-1 overflow-y-auto">
            <button
                v-for="conversation in conversations"
                :key="conversation.id"
                @click="$emit('select', conversation)"
                class="flex w-full items-center gap-3 border-b border-gray-100 px-4 py-3 text-left transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50"
            >
                <!-- Avatar -->
                <div class="relative flex-shrink-0">
                    <!-- Support conversation icon -->
                    <div
                        v-if="conversation.type === 'support'"
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500 text-white"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.562C15.802 8.249 16 9.1 16 10zm-5.165 3.913l1.58 1.58A5.98 5.98 0 0110 16a5.976 5.976 0 01-2.516-.552l1.562-1.562a4.006 4.006 0 001.789.027zm-4.677-2.796a4.002 4.002 0 01-.041-2.08l-.08.08-1.53-1.533A5.98 5.98 0 004 10c0 .954.223 1.856.619 2.657l1.54-1.54zm1.088-6.45A5.974 5.974 0 0110 4c.954 0 1.856.223 2.657.619l-1.54 1.54a4.002 4.002 0 00-2.346.033L7.246 4.668zM12 10a2 2 0 11-4 0 2 2 0 014 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <!-- Regular avatar -->
                    <img
                        v-else-if="conversation.avatar_url"
                        :src="conversation.avatar_url"
                        :alt="conversation.name"
                        class="h-12 w-12 rounded-full object-cover"
                    />
                    <div
                        v-else
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-500 text-lg font-medium text-white"
                    >
                        {{ conversation.name?.charAt(0)?.toUpperCase() || '?' }}
                    </div>
                    <!-- Online indicator -->
                    <span
                        v-if="conversation.is_online"
                        class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-green-500 dark:border-gray-800"
                    ></span>
                </div>

                <!-- Content -->
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <span
                            class="truncate font-medium text-gray-900 dark:text-gray-100"
                            :class="{ 'font-semibold': conversation.unread_count > 0 }"
                        >
                            {{ conversation.name }}
                        </span>
                        <span class="flex-shrink-0 text-xs text-gray-500 dark:text-gray-400">
                            {{ formatTime(conversation.latest_message?.created_at) }}
                        </span>
                    </div>
                    <div class="mt-0.5 flex items-center justify-between gap-2">
                        <p
                            class="truncate text-sm"
                            :class="conversation.unread_count > 0 ? 'font-medium text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'"
                        >
                            {{ truncate(conversation.latest_message?.content) || 'Aucun message' }}
                        </p>
                        <span
                            v-if="conversation.unread_count > 0"
                            class="flex h-5 min-w-5 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 px-1.5 text-xs font-medium text-white"
                        >
                            {{ conversation.unread_count > 99 ? '99+' : conversation.unread_count }}
                        </span>
                    </div>
                </div>
            </button>
        </div>
    </div>
</template>
