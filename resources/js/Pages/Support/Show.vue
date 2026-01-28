<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MessageList from '@/Components/Messages/MessageList.vue';
import MessageInput from '@/Components/Messages/MessageInput.vue';
import TypingIndicator from '@/Components/Messages/TypingIndicator.vue';
import { useMessaging } from '@/composables/useMessaging';
import { useEcho } from '@/composables/useEcho';

const props = defineProps({
    conversation: {
        type: Object,
        required: true,
    },
    messages: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const {
    setCurrentConversation,
    setMessages,
    addMessage,
    setTyping,
    getTypingUsersForConversation,
    sendMessage,
    loadMoreMessages,
    sendTypingIndicator,
    markAsRead,
    isLoading,
} = useMessaging();

const { subscribeToPrivate, leaveChannel } = useEcho();

const messageListRef = ref(null);
const nextCursor = ref(props.messages.next_cursor);
const isLoadingMore = ref(false);

const typingUsers = computed(() => {
    return getTypingUsersForConversation(props.conversation.id)
        .filter(t => t.userId !== currentUser.value?.id);
});

const statusBadge = computed(() => {
    const status = props.conversation.support_status;
    const badges = {
        open: { label: 'Ouvert', class: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' },
        pending: { label: 'En attente', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' },
        resolved: { label: 'Resolu', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' },
        closed: { label: 'Ferme', class: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' },
    };
    return badges[status] || badges.open;
});

onMounted(() => {
    setCurrentConversation(props.conversation);
    setMessages([...props.messages.data].reverse());

    subscribeToPrivate(`conversation.${props.conversation.id}`, handleChannelEvent);

    nextTick(() => {
        scrollToBottom();
    });
});

onUnmounted(() => {
    leaveChannel(`conversation.${props.conversation.id}`);
});

const handleChannelEvent = (event, data) => {
    switch (event) {
        case 'message.sent':
            addMessage(data.message);
            nextTick(() => scrollToBottom());
            markAsRead(props.conversation.id);
            break;

        case 'user.typing':
            if (data.user.id !== currentUser.value?.id) {
                setTyping(data.conversation_id, data.user.id, data.user.name, data.is_typing);
            }
            break;
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
    <Head title="Support" />

    <AuthenticatedLayout>
        <div class="flex h-[calc(100vh-4rem)] flex-col bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                            Support
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Notre equipe vous repond
                        </p>
                    </div>
                </div>

                <span :class="['rounded-full px-3 py-1 text-xs font-medium', statusBadge.class]">
                    {{ statusBadge.label }}
                </span>
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
    </AuthenticatedLayout>
</template>
