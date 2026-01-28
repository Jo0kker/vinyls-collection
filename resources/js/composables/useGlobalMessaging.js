import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

// Global state (singleton)
const isOpen = ref(false);
const view = ref('list'); // 'list' | 'conversation' | 'new'
const conversations = ref([]);
const activeConversation = ref(null);
const messages = ref([]);
const typingUsers = ref([]);
const isLoadingConversations = ref(false);
const isLoadingMessages = ref(false);
const isSending = ref(false);

let echoInitialized = false;
let currentUserId = null;
let activeChannelId = null;

export function useGlobalMessaging() {
    const unreadCount = computed(() => {
        return conversations.value.reduce((sum, c) => sum + (c.unread_count || 0), 0);
    });

    const toggleChat = () => {
        isOpen.value = !isOpen.value;
        if (!isOpen.value) {
            // Reset view when closing
            view.value = 'list';
            activeConversation.value = null;
            messages.value = [];
        }
    };

    const openChat = () => {
        isOpen.value = true;
    };

    const closeChat = () => {
        isOpen.value = false;
        view.value = 'list';
        activeConversation.value = null;
        messages.value = [];
    };

    const setView = (newView) => {
        view.value = newView;
    };

    const loadConversations = async () => {
        if (isLoadingConversations.value) return;

        isLoadingConversations.value = true;
        try {
            const response = await axios.get(route('api.conversations.index'));
            conversations.value = response.data;
        } catch (error) {
            console.error('Failed to load conversations:', error);
        } finally {
            isLoadingConversations.value = false;
        }
    };

    const selectConversation = async (conversation) => {
        activeConversation.value = conversation;
        view.value = 'conversation';
        messages.value = [];

        // Subscribe to conversation channel
        subscribeToConversation(conversation.id);

        // Load messages
        await loadMessages(conversation.id);

        // Mark as read
        await markAsRead(conversation.id);
    };

    const closeConversation = () => {
        if (activeChannelId) {
            leaveConversationChannel(activeChannelId);
        }
        activeConversation.value = null;
        messages.value = [];
        view.value = 'list';
    };

    const loadMessages = async (conversationId) => {
        isLoadingMessages.value = true;
        try {
            const response = await axios.get(route('messages.load-more', { conversation: conversationId }));
            messages.value = response.data.data.reverse();
        } catch (error) {
            console.error('Failed to load messages:', error);
        } finally {
            isLoadingMessages.value = false;
        }
    };

    const sendMessage = async (conversationId, content, images = []) => {
        isSending.value = true;
        try {
            const formData = new FormData();
            if (content) {
                formData.append('content', content);
            }
            images.forEach((image, index) => {
                formData.append(`images[${index}]`, image);
            });

            const response = await axios.post(
                route('messages.send', { conversation: conversationId }),
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } }
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
            isSending.value = false;
        }
    };

    const addMessage = (message) => {
        const exists = messages.value.some(m => m.id === message.id);
        if (!exists) {
            messages.value.push(message);
        }
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

            // Sort by updated_at
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

    const markAsRead = async (conversationId) => {
        try {
            await axios.post(route('messages.read', { conversation: conversationId }));
            const conversation = conversations.value.find(c => c.id === conversationId);
            if (conversation) {
                conversation.unread_count = 0;
            }
        } catch (error) {
            console.error('Failed to mark as read:', error);
        }
    };

    const sendTypingIndicator = async (conversationId) => {
        try {
            await axios.post(route('messages.typing', { conversation: conversationId }));
        } catch (error) {
            // Silently fail
        }
    };

    const searchUsers = async (query) => {
        const response = await axios.get(route('api.users.search'), { params: { q: query } });
        return response.data;
    };

    const createConversation = async (type, participantIds, name = null) => {
        const response = await axios.post(route('api.conversations.store'), {
            type,
            participant_ids: participantIds,
            name,
        });

        // Reload conversations
        await loadConversations();

        // The API returns the conversation directly
        return response.data.conversation || conversations.value[0];
    };

    const openSupportConversation = async () => {
        try {
            const response = await axios.post(route('api.support.create'));
            const conversation = response.data.conversation;

            // Reload conversations to include support conversation
            await loadConversations();

            // Select the support conversation
            await selectConversation(conversation);

            return conversation;
        } catch (error) {
            console.error('Failed to open support conversation:', error);
            throw error;
        }
    };

    // Start a conversation with a specific user (from profile page, etc.)
    const startConversationWith = async (userId, userName = null) => {
        openChat();
        setView('new');

        // Pre-select the user
        if (userId && userName) {
            // This will be handled by the ChatNewConversation component
            // We can pass data through a global event or store
        }
    };

    // Echo/WebSocket handling
    const initializeEcho = (userId) => {
        if (echoInitialized || !window.Echo) return;

        currentUserId = userId;
        echoInitialized = true;

        // Subscribe to user channel for notifications
        window.Echo.private(`user.${userId}`)
            .listen('.new.message', (e) => {
                handleNewMessage(e);
            })
            .listen('.new.conversation', (e) => {
                handleNewConversation(e);
            });

        // Load initial conversations
        loadConversations();
    };

    const handleNewConversation = (e) => {
        // Add conversation to list if not already present
        const exists = conversations.value.some(c => c.id === e.conversation.id);
        if (!exists) {
            conversations.value.unshift(e.conversation);
        }
    };

    const subscribeToConversation = (conversationId) => {
        if (!window.Echo) return;

        // Leave previous channel
        if (activeChannelId && activeChannelId !== conversationId) {
            leaveConversationChannel(activeChannelId);
        }

        activeChannelId = conversationId;

        window.Echo.private(`conversation.${conversationId}`)
            .listen('.message.sent', (e) => {
                if (e.message.sender_id !== currentUserId) {
                    addMessage(e.message);
                    updateConversationLatestMessage(conversationId, e.message);
                }
            })
            .listen('.user.typing', (e) => {
                if (e.user.id !== currentUserId) {
                    handleTypingEvent(e);
                }
            })
            .listen('.message.read', (e) => {
                // Handle read receipts if needed
            });
    };

    const leaveConversationChannel = (conversationId) => {
        if (window.Echo) {
            window.Echo.leave(`conversation.${conversationId}`);
        }
        activeChannelId = null;
    };

    const handleNewMessage = (e) => {
        // Update conversation list
        const conversation = conversations.value.find(c => c.id === e.conversation_id);
        if (conversation) {
            updateConversationLatestMessage(e.conversation_id, e.message);

            // Only increment unread if not viewing this conversation
            if (!activeConversation.value || activeConversation.value.id !== e.conversation_id) {
                incrementUnreadCount(e.conversation_id);
            } else {
                // If viewing this conversation, add the message
                addMessage(e.message);
                markAsRead(e.conversation_id);
            }
        } else {
            // New conversation, reload list
            loadConversations();
        }

        // Play notification sound if chat is closed
        if (!isOpen.value) {
            playNotificationSound();
        }
    };

    const handleTypingEvent = (e) => {
        const key = `${e.conversation_id}-${e.user.id}`;
        const existing = typingUsers.value.findIndex(t =>
            t.conversationId === e.conversation_id && t.userId === e.user.id
        );

        if (e.is_typing) {
            if (existing === -1) {
                typingUsers.value.push({
                    conversationId: e.conversation_id,
                    userId: e.user.id,
                    userName: e.user.name,
                });
            }

            // Auto-remove after 3 seconds
            setTimeout(() => {
                const idx = typingUsers.value.findIndex(t =>
                    t.conversationId === e.conversation_id && t.userId === e.user.id
                );
                if (idx !== -1) {
                    typingUsers.value.splice(idx, 1);
                }
            }, 3000);
        } else if (existing !== -1) {
            typingUsers.value.splice(existing, 1);
        }
    };

    const playNotificationSound = () => {
        // Optional: play a notification sound
        // const audio = new Audio('/sounds/notification.mp3');
        // audio.volume = 0.5;
        // audio.play().catch(() => {});
    };

    const cleanup = () => {
        if (window.Echo && currentUserId) {
            window.Echo.leave(`user.${currentUserId}`);
        }
        if (activeChannelId) {
            leaveConversationChannel(activeChannelId);
        }
        echoInitialized = false;
        currentUserId = null;
    };

    return {
        // State
        isOpen,
        view,
        conversations,
        activeConversation,
        messages,
        typingUsers,
        isLoadingConversations,
        isLoadingMessages,
        isSending,
        unreadCount,

        // Actions
        toggleChat,
        openChat,
        closeChat,
        setView,
        loadConversations,
        selectConversation,
        closeConversation,
        sendMessage,
        sendTypingIndicator,
        markAsRead,
        searchUsers,
        createConversation,
        startConversationWith,
        openSupportConversation,

        // Echo
        initializeEcho,
        cleanup,
    };
}
