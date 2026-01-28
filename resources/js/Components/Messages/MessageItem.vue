<script setup>
import { ref } from 'vue';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import ImageLightbox from './ImageLightbox.vue';

const props = defineProps({
    message: {
        type: Object,
        required: true,
    },
    isOwnMessage: {
        type: Boolean,
        default: false,
    },
    showAvatar: {
        type: Boolean,
        default: false,
    },
});

const lightboxImage = ref(null);

const formatTime = (dateString) => {
    try {
        return format(new Date(dateString), 'HH:mm', { locale: fr });
    } catch {
        return '';
    }
};

const openLightbox = (imageUrl) => {
    lightboxImage.value = imageUrl;
};
</script>

<template>
    <div
        class="flex gap-2"
        :class="isOwnMessage ? 'flex-row-reverse' : 'flex-row'"
    >
        <!-- Avatar (for group conversations) -->
        <div v-if="showAvatar && !isOwnMessage" class="flex-shrink-0">
            <img
                v-if="message.sender?.avatar"
                :src="message.sender.avatar"
                :alt="message.sender.name"
                class="h-8 w-8 rounded-full object-cover"
            />
            <div
                v-else
                class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-400 text-xs text-white"
            >
                {{ message.sender?.name?.charAt(0)?.toUpperCase() || '?' }}
            </div>
        </div>

        <!-- Message content -->
        <div
            class="max-w-[70%] space-y-1"
            :class="isOwnMessage ? 'items-end' : 'items-start'"
        >
            <!-- Sender name (for group conversations) -->
            <p
                v-if="showAvatar && !isOwnMessage"
                class="px-1 text-xs font-medium text-gray-700 dark:text-gray-300"
            >
                {{ message.sender?.name }}
            </p>

            <!-- Text content -->
            <div
                v-if="message.content"
                class="rounded-2xl px-4 py-2"
                :class="
                    isOwnMessage
                        ? 'bg-blue-600 text-white'
                        : 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-gray-100'
                "
            >
                <p class="whitespace-pre-wrap break-words text-sm">{{ message.content }}</p>
            </div>

            <!-- Attachments -->
            <div
                v-if="message.attachments?.length"
                class="flex flex-wrap gap-2"
                :class="isOwnMessage ? 'justify-end' : 'justify-start'"
            >
                <template v-for="attachment in message.attachments" :key="attachment.id">
                    <button
                        v-if="attachment.type === 'image'"
                        @click="openLightbox(attachment.url)"
                        class="overflow-hidden rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <img
                            :src="attachment.url"
                            :alt="attachment.filename"
                            class="max-h-60 max-w-60 object-cover transition-transform hover:scale-105"
                        />
                    </button>
                </template>
            </div>

            <!-- Timestamp -->
            <p
                class="px-1 text-xs text-gray-500 dark:text-gray-400"
                :class="isOwnMessage ? 'text-right' : 'text-left'"
            >
                {{ formatTime(message.created_at) }}
            </p>
        </div>
    </div>

    <!-- Image lightbox -->
    <ImageLightbox
        v-if="lightboxImage"
        :image-url="lightboxImage"
        @close="lightboxImage = null"
    />
</template>
