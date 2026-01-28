<script setup>
import { Link } from '@inertiajs/vue3';
import { formatDistanceToNow } from 'date-fns';
import { fr } from 'date-fns/locale';

const props = defineProps({
    conversations: {
        type: Array,
        required: true,
    },
    currentConversationId: {
        type: Number,
        default: null,
    },
    isOnline: {
        type: Function,
        default: () => false,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const formatTime = (dateString) => {
    if (!dateString) return '';
    try {
        return formatDistanceToNow(new Date(dateString), { addSuffix: true, locale: fr });
    } catch {
        return '';
    }
};

const truncate = (text, length = 50) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};
</script>

<template>
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        <Link
            v-for="conversation in conversations"
            :key="conversation.id"
            :href="route('messages.show', conversation.id)"
            class="flex items-center gap-3 p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
            :class="{
                'bg-blue-50 dark:bg-blue-900/20': currentConversationId === conversation.id,
                'p-3': compact,
            }"
        >
            <div class="relative flex-shrink-0">
                <!-- Support conversation icon -->
                <div
                    v-if="conversation.type === 'support'"
                    class="flex items-center justify-center rounded-full bg-emerald-500 text-white"
                    :class="compact ? 'h-10 w-10' : 'h-12 w-12'"
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
                    class="rounded-full object-cover"
                    :class="compact ? 'h-10 w-10' : 'h-12 w-12'"
                />
                <div
                    v-else
                    class="flex items-center justify-center rounded-full bg-blue-500 text-white"
                    :class="compact ? 'h-10 w-10 text-sm' : 'h-12 w-12'"
                >
                    {{ conversation.name?.charAt(0)?.toUpperCase() || '?' }}
                </div>

                <!-- Online indicator for direct conversations -->
                <span
                    v-if="conversation.type === 'direct' && conversation.participants?.length"
                    :class="[
                        'absolute bottom-0 right-0 rounded-full border-2 border-white dark:border-gray-800',
                        isOnline(conversation.participants.find(p => p.id !== $page.props.auth?.user?.id)?.id)
                            ? 'bg-green-500'
                            : 'bg-gray-400',
                        compact ? 'h-2.5 w-2.5' : 'h-3 w-3',
                    ]"
                ></span>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <h4
                        class="truncate font-medium text-gray-900 dark:text-gray-100"
                        :class="{ 'text-sm': compact }"
                    >
                        {{ conversation.name }}
                    </h4>
                    <span
                        v-if="conversation.latest_message"
                        class="flex-shrink-0 text-xs text-gray-500 dark:text-gray-400"
                    >
                        {{ formatTime(conversation.latest_message.created_at) }}
                    </span>
                </div>

                <div class="mt-0.5 flex items-center justify-between gap-2">
                    <p
                        class="truncate text-gray-600 dark:text-gray-400"
                        :class="[
                            compact ? 'text-xs' : 'text-sm',
                            conversation.unread_count > 0 ? 'font-medium text-gray-900 dark:text-gray-100' : '',
                        ]"
                    >
                        <span v-if="conversation.latest_message">
                            <span v-if="(conversation.type === 'group' || conversation.type === 'support') && conversation.latest_message.sender_name" class="text-gray-500">
                                {{ conversation.latest_message.sender_name }}:
                            </span>
                            {{ truncate(conversation.latest_message.content, compact ? 30 : 50) || '[Image]' }}
                        </span>
                        <span v-else class="italic text-gray-400">
                            Aucun message
                        </span>
                    </p>

                    <span
                        v-if="conversation.unread_count > 0"
                        class="flex h-5 min-w-5 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 px-1.5 text-xs font-medium text-white"
                    >
                        {{ conversation.unread_count > 99 ? '99+' : conversation.unread_count }}
                    </span>
                </div>
            </div>
        </Link>

        <div v-if="conversations.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
            Aucune conversation
        </div>
    </div>
</template>
