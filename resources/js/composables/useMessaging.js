import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const conversations = ref([]);
const currentConversation = ref(null);
const messages = ref([]);
const typingUsers = ref(new Map());
const isLoading = ref(false);

export function useMessaging() {
    const page = usePage();

    const unreadCount = computed(() => {
        return page.props.auth?.user?.unread_messages_count ?? 0;
    });

    const totalUnread = computed(() => {
        return conversations.value.reduce((sum, c) => sum + (c.unread_count || 0), 0);
    });

    const setConversations = (data) => {
        conversations.value = data;
    };

    const setCurrentConversation = (conversation) => {
        currentConversation.value = conversation;
    };

    const setMessages = (data) => {
        messages.value = data;
    };

    const addMessage = (message) => {
        // Check if message already exists
        const exists = messages.value.some(m => m.id === message.id);
        if (!exists) {
            messages.value.push(message);
        }
    };

    const prependMessages = (newMessages) => {
        const existingIds = new Set(messages.value.map(m => m.id));
        const uniqueMessages = newMessages.filter(m => !existingIds.has(m.id));
        messages.value = [...uniqueMessages, ...messages.value];
    };

    const updateConversationLatestMessage = (conversationId, message) => {
        const conversation = conversations.value.find(c => c.id === conversationId);
        if (conversation) {
            conversation.latest_message = {
                content: message.content,
                sender_name: message.sender?.name,
                created_at: message.created_at,
            };
            conversation.updated_at = message.created_at;

            // Sort conversations by updated_at
            conversations.value.sort((a, b) =>
                new Date(b.updated_at) - new Date(a.updated_at)
            );
        }
    };

    const incrementUnreadCount = (conversationId) => {
        const conversation = conversations.value.find(c => c.id === conversationId);
        if (conversation) {
            conversation.unread_count = (conversation.unread_count || 0) + 1;
        }
    };

    const resetUnreadCount = (conversationId) => {
        const conversation = conversations.value.find(c => c.id === conversationId);
        if (conversation) {
            conversation.unread_count = 0;
        }
    };

    const setTyping = (conversationId, userId, userName, isTyping) => {
        const key = `${conversationId}-${userId}`;
        if (isTyping) {
            typingUsers.value.set(key, { userId, userName, conversationId });
            // Auto-remove after 3 seconds
            setTimeout(() => {
                typingUsers.value.delete(key);
            }, 3000);
        } else {
            typingUsers.value.delete(key);
        }
    };

    const getTypingUsersForConversation = (conversationId) => {
        return Array.from(typingUsers.value.values())
            .filter(t => t.conversationId === conversationId);
    };

    const sendMessage = async (conversationId, content, images = []) => {
        isLoading.value = true;

        try {
            const formData = new FormData();
            if (content) {
                formData.append('content', content);
            }
            images.forEach((image, index) => {
                formData.append(`images[${index}]`, image);
            });

            const response = await axios.post(
                route('messages.send', conversationId),
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                }
            );

            if (response.data.message) {
                addMessage(response.data.message);
                updateConversationLatestMessage(conversationId, response.data.message);
            }

            return response.data;
        } catch (error) {
            console.error('Failed to send message:', error);
            throw error;
        } finally {
            isLoading.value = false;
        }
    };

    const loadMoreMessages = async (conversationId, cursor) => {
        try {
            const response = await axios.get(route('messages.load-more', conversationId), {
                params: { cursor },
            });

            if (response.data.data) {
                prependMessages(response.data.data);
            }

            return response.data;
        } catch (error) {
            console.error('Failed to load more messages:', error);
            throw error;
        }
    };

    const sendTypingIndicator = async (conversationId) => {
        try {
            await axios.post(route('messages.typing', conversationId));
        } catch (error) {
            // Silently fail for typing indicators
        }
    };

    const markAsRead = async (conversationId) => {
        try {
            await axios.post(route('messages.read', conversationId));
            resetUnreadCount(conversationId);
            // Reload auth props to update unread count in navbar
            router.reload({ only: ['auth'] });
        } catch (error) {
            console.error('Failed to mark as read:', error);
        }
    };

    const createConversation = (type, participantIds, name = null) => {
        router.post(route('messages.store'), {
            type,
            participant_ids: participantIds,
            name,
        });
    };

    return {
        conversations,
        currentConversation,
        messages,
        typingUsers,
        isLoading,
        unreadCount,
        totalUnread,
        setConversations,
        setCurrentConversation,
        setMessages,
        addMessage,
        prependMessages,
        updateConversationLatestMessage,
        incrementUnreadCount,
        resetUnreadCount,
        setTyping,
        getTypingUsersForConversation,
        sendMessage,
        loadMoreMessages,
        sendTypingIndicator,
        markAsRead,
        createConversation,
    };
}
