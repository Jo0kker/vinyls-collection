<script setup>
import { ref, computed, nextTick, watch, onMounted } from 'vue';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { useGlobalMessaging } from '@/composables/useGlobalMessaging';

const props = defineProps({
    conversation: {
        type: Object,
        required: true,
    },
    messages: {
        type: Array,
        default: () => [],
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
    currentUser: {
        type: Object,
        required: true,
    },
});

const {
    sendMessage,
    isSending,
    typingUsers,
    sendTypingIndicator,
} = useGlobalMessaging();

const messageInput = ref('');
const messagesContainer = ref(null);
const fileInput = ref(null);
const selectedImages = ref([]);

const currentTypingUsers = computed(() => {
    return typingUsers.value
        .filter(t => t.conversationId === props.conversation.id && t.userId !== props.currentUser?.id);
});

const formatTime = (dateString) => {
    try {
        return format(new Date(dateString), 'HH:mm', { locale: fr });
    } catch {
        return '';
    }
};

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

onMounted(() => {
    nextTick(() => scrollToBottom());
});

watch(() => props.messages.length, () => {
    nextTick(() => scrollToBottom());
});

let typingTimeout = null;
const handleInput = () => {
    clearTimeout(typingTimeout);
    sendTypingIndicator(props.conversation.id);
    typingTimeout = setTimeout(() => {}, 2000);
};

const handleSend = async () => {
    const content = messageInput.value.trim();
    if (!content && selectedImages.value.length === 0) return;

    const images = [...selectedImages.value];
    messageInput.value = '';
    selectedImages.value = [];

    await sendMessage(props.conversation.id, content, images);
};

const handleKeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend();
    }
};

const handleImageSelect = (e) => {
    const files = Array.from(e.target.files || []);
    const remaining = 5 - selectedImages.value.length;

    for (const file of files.slice(0, remaining)) {
        if (file.type.startsWith('image/') && file.size <= 10 * 1024 * 1024) {
            selectedImages.value.push(file);
        }
    }
    e.target.value = '';
};

const removeImage = (index) => {
    selectedImages.value.splice(index, 1);
};

const getImagePreview = (file) => URL.createObjectURL(file);
</script>

<template>
    <div class="flex h-full flex-col">
        <!-- Messages -->
        <div
            ref="messagesContainer"
            class="flex-1 overflow-y-auto p-4"
        >
            <!-- Loading -->
            <div v-if="isLoading" class="flex h-full items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            <!-- Messages list -->
            <div v-else class="space-y-3">
                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="flex"
                    :class="message.sender_id === currentUser?.id ? 'justify-end' : 'justify-start'"
                >
                    <div class="max-w-[75%]">
                        <!-- Sender name for groups and support (outside bubble) -->
                        <p
                            v-if="(conversation.type === 'group' || conversation.type === 'support') && message.sender_id !== currentUser?.id"
                            class="mb-1 px-1 text-xs font-medium text-gray-700 dark:text-gray-300"
                        >
                            {{ message.sender?.name }}
                        </p>

                        <div
                            class="rounded-2xl px-4 py-2"
                            :class="message.sender_id === currentUser?.id
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-gray-100'"
                        >
                            <!-- Content -->
                            <p v-if="message.content" class="whitespace-pre-wrap break-words text-sm">
                                {{ message.content }}
                            </p>

                            <!-- Attachments -->
                            <div v-if="message.attachments?.length" class="mt-2 space-y-2">
                                <img
                                    v-for="attachment in message.attachments"
                                    :key="attachment.id"
                                    :src="attachment.url"
                                    class="max-w-full rounded-lg"
                                />
                            </div>

                            <!-- Time -->
                            <p class="mt-1 text-right text-xs opacity-70">
                                {{ formatTime(message.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="messages.length === 0 && !isLoading" class="flex h-full items-center justify-center text-center text-gray-500">
                    <p>Commencez la conversation !</p>
                </div>
            </div>
        </div>

        <!-- Typing indicator -->
        <div
            v-if="currentTypingUsers.length > 0"
            class="px-4 py-1 text-xs text-gray-500 dark:text-gray-400"
        >
            <span class="flex items-center gap-1">
                <span class="flex space-x-1">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.3s]"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.15s]"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400"></span>
                </span>
                {{ currentTypingUsers.map(t => t.userName).join(', ') }} écrit...
            </span>
        </div>

        <!-- Image previews -->
        <div v-if="selectedImages.length > 0" class="flex gap-2 border-t border-gray-200 px-4 py-2 dark:border-gray-700">
            <div
                v-for="(image, index) in selectedImages"
                :key="index"
                class="relative"
            >
                <img
                    :src="getImagePreview(image)"
                    class="h-16 w-16 rounded-lg object-cover"
                />
                <button
                    @click="removeImage(index)"
                    class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Input -->
        <div class="flex items-end gap-2 border-t border-gray-200 p-3 dark:border-gray-700">
            <button
                @click="fileInput?.click()"
                :disabled="selectedImages.length >= 5"
                class="flex-shrink-0 rounded-full p-2 text-gray-500 hover:bg-gray-100 disabled:opacity-50 dark:text-gray-400 dark:hover:bg-gray-700"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </button>
            <input
                ref="fileInput"
                type="file"
                accept="image/*"
                multiple
                class="hidden"
                @change="handleImageSelect"
            />

            <textarea
                v-model="messageInput"
                @input="handleInput"
                @keydown="handleKeydown"
                placeholder="Message..."
                rows="1"
                class="flex-1 resize-none rounded-2xl border border-gray-300 bg-gray-50 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                style="max-height: 100px;"
            ></textarea>

            <button
                @click="handleSend"
                :disabled="isSending || (!messageInput.trim() && selectedImages.length === 0)"
                class="flex-shrink-0 rounded-full bg-blue-600 p-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <svg
                    v-if="isSending"
                    class="h-5 w-5 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                </svg>
            </button>
        </div>
    </div>
</template>
