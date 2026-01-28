<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConversationList from '@/Components/Messages/ConversationList.vue';
import NewConversationModal from '@/Components/Messages/NewConversationModal.vue';
import { useMessaging } from '@/composables/useMessaging';
import { useEcho } from '@/composables/useEcho';
import { usePresence } from '@/composables/usePresence';

const props = defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const { setConversations, incrementUnreadCount, updateConversationLatestMessage } = useMessaging();
const { subscribeToUser, leaveChannel } = useEcho();
const { isOnline } = usePresence();

const showNewConversationModal = ref(false);

onMounted(() => {
    setConversations(props.conversations);

    // Subscribe to user channel for new message notifications
    const userId = page.props.auth?.user?.id;
    if (userId) {
        subscribeToUser(userId, (event, data) => {
            if (event === 'new.message') {
                incrementUnreadCount(data.conversation_id);
                updateConversationLatestMessage(data.conversation_id, {
                    content: data.message.content,
                    sender_name: data.sender.name,
                    created_at: data.message.created_at,
                });
            }
        });
    }
});

onUnmounted(() => {
    const userId = page.props.auth?.user?.id;
    if (userId) {
        leaveChannel(`user.${userId}`);
    }
});
</script>

<template>
    <Head title="Messages" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Messages
                </h2>
                <button
                    @click="showNewConversationModal = true"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Nouvelle conversation
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div v-if="conversations.length === 0" class="p-12 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                            Aucune conversation
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Commencez une nouvelle conversation pour discuter avec d'autres collectionneurs.
                        </p>
                        <button
                            @click="showNewConversationModal = true"
                            class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                        >
                            Nouvelle conversation
                        </button>
                    </div>

                    <ConversationList
                        v-else
                        :conversations="conversations"
                        :is-online="isOnline"
                    />
                </div>
            </div>
        </div>

        <NewConversationModal
            :show="showNewConversationModal"
            @close="showNewConversationModal = false"
        />
    </AuthenticatedLayout>
</template>
