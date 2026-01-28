<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConversationList from '@/Components/Messages/ConversationList.vue';
import MessageList from '@/Components/Messages/MessageList.vue';
import MessageInput from '@/Components/Messages/MessageInput.vue';
import TypingIndicator from '@/Components/Messages/TypingIndicator.vue';
import GroupSettingsModal from '@/Components/Messages/GroupSettingsModal.vue';
import { useMessaging } from '@/composables/useMessaging';
import { useEcho } from '@/composables/useEcho';
import { usePresence } from '@/composables/usePresence';

const props = defineProps({
    conversation: {
        type: Object,
        required: true,
    },
    messages: {
        type: Object,
        required: true,
    },
    conversations: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const {
    setConversations,
    setCurrentConversation,
    setMessages,
    addMessage,
    updateConversationLatestMessage,
    incrementUnreadCount,
    setTyping,
    getTypingUsersForConversation,
    sendMessage,
    loadMoreMessages,
    sendTypingIndicator,
    markAsRead,
    isLoading,
} = useMessaging();

const { subscribeToPrivate, subscribeToUser, leaveChannel } = useEcho();
const { isOnline } = usePresence();

const messageListRef = ref(null);
const showGroupSettings = ref(false);
const nextCursor = ref(props.messages.next_cursor);
const isLoadingMore = ref(false);

// Computed
const typingUsers = computed(() => {
    return getTypingUsersForConversation(props.conversation.id)
        .filter(t => t.userId !== currentUser.value?.id);
});

const otherParticipant = computed(() => {
    if (props.conversation.type !== 'direct') return null;
    return props.conversation.participants.find(p => p.id !== currentUser.value?.id);
});

const isOtherParticipantOnline = computed(() => {
    if (!otherParticipant.value) return false;
    return isOnline(otherParticipant.value.id);
});

// Initialize
onMounted(() => {
    setConversations(props.conversations);
    setCurrentConversation(props.conversation);
    setMessages([...props.messages.data].reverse()); // Reverse for chronological order

    // Subscribe to conversation channel
    subscribeToPrivate(`conversation.${props.conversation.id}`, handleChannelEvent);

    // Subscribe to user channel for other conversations
    if (currentUser.value?.id) {
        subscribeToUser(currentUser.value.id, handleUserEvent);
    }

    // Scroll to bottom
    nextTick(() => {
        scrollToBottom();
    });
});

onUnmounted(() => {
    leaveChannel(`conversation.${props.conversation.id}`);
    if (currentUser.value?.id) {
        leaveChannel(`user.${currentUser.value.id}`);
    }
});

// Watch for prop changes (navigation between conversations)
watch(() => props.conversation.id, (newId, oldId) => {
    if (newId !== oldId) {
        leaveChannel(`conversation.${oldId}`);
        setCurrentConversation(props.conversation);
        setMessages([...props.messages.data].reverse());
        nextCursor.value = props.messages.next_cursor;
        subscribeToPrivate(`conversation.${newId}`, handleChannelEvent);
        nextTick(() => scrollToBottom());
    }
});

// Event handlers
const handleChannelEvent = (event, data) => {
    switch (event) {
        case 'message.sent':
            addMessage(data.message);
            updateConversationLatestMessage(props.conversation.id, data.message);
            nextTick(() => scrollToBottom());
            markAsRead(props.conversation.id);
            break;

        case 'user.typing':
            if (data.user.id !== currentUser.value?.id) {
                setTyping(data.conversation_id, data.user.id, data.user.name, data.is_typing);
            }
            break;

        case 'message.read':
            // Could update read receipts here
            break;

        case 'conversation.updated':
            // Reload conversation data if needed
            break;
    }
};

const handleUserEvent = (event, data) => {
    if (event === 'new.message' && data.conversation_id !== props.conversation.id) {
        incrementUnreadCount(data.conversation_id);
        updateConversationLatestMessage(data.conversation_id, {
            content: data.message.content,
            sender_name: data.sender.name,
            created_at: data.message.created_at,
        });
    }
};

const scrollToBottom = () => {
    if (messageListRef.value) {
        messageListRef.value.scrollToBottom();
    }
};

const handleSendMessage = async (content, images) => {
    try {
        await sendMessage(props.conversation.id, content, images);
        nextTick(() => scrollToBottom());
    } catch (error) {
        console.error('Failed to send message:', error);
    }
};

const handleTyping = () => {
    sendTypingIndicator(props.conversation.id);
};

const handleLoadMore = async () => {
    if (!nextCursor.value || isLoadingMore.value) return;

    isLoadingMore.value = true;
    try {
        const result = await loadMoreMessages(props.conversation.id, nextCursor.value);
        nextCursor.value = result.next_cursor;
    } catch (error) {
        console.error('Failed to load more:', error);
    } finally {
        isLoadingMore.value = false;
    }
};
</script>

<template>
    <Head :title="conversation.name" />

    <AuthenticatedLayout>
        <div class="flex h-[calc(100vh-4rem)]">
            <!-- Sidebar: Conversation list (hidden on mobile) -->
            <div class="hidden w-80 flex-shrink-0 border-r border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 lg:block">
                <div class="flex h-full flex-col">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Conversations
                        </h2>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <ConversationList
                            :conversations="conversations"
                            :current-conversation-id="conversation.id"
                            :is-online="isOnline"
                            compact
                        />
                    </div>
                </div>
            </div>

            <!-- Main chat area -->
            <div class="flex flex-1 flex-col bg-gray-50 dark:bg-gray-900">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <img
                                v-if="conversation.avatar_url"
                                :src="conversation.avatar_url"
                                :alt="conversation.name"
                                class="h-10 w-10 rounded-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500 text-white"
                            >
                                {{ conversation.name?.charAt(0)?.toUpperCase() || '?' }}
                            </div>
                            <span
                                v-if="conversation.type === 'direct' && isOtherParticipantOnline"
                                class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-green-500 dark:border-gray-800"
                            ></span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ conversation.name }}
                            </h3>
                            <p v-if="conversation.type === 'direct'" class="text-xs text-gray-500 dark:text-gray-400">
                                {{ isOtherParticipantOnline ? 'En ligne' : 'Hors ligne' }}
                            </p>
                            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
                                {{ conversation.participants.length }} participants
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="conversation.type === 'group'"
                            @click="showGroupSettings = true"
                            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <MessageList
                    ref="messageListRef"
                    :conversation="conversation"
                    :current-user-id="currentUser?.id"
                    :has-more="!!nextCursor"
                    :is-loading-more="isLoadingMore"
                    @load-more="handleLoadMore"
                />

                <!-- Typing indicator -->
                <TypingIndicator :typing-users="typingUsers" />

                <!-- Message input -->
                <MessageInput
                    :is-sending="isLoading"
                    @send="handleSendMessage"
                    @typing="handleTyping"
                />
            </div>
        </div>

        <GroupSettingsModal
            v-if="conversation.type === 'group'"
            :show="showGroupSettings"
            :conversation="conversation"
            @close="showGroupSettings = false"
        />
    </AuthenticatedLayout>
</template>
