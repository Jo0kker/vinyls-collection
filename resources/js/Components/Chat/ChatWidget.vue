<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import ChatPanel from './ChatPanel.vue';
import { useGlobalMessaging } from '@/composables/useGlobalMessaging';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

// Hide widget on Messages, Support and Contact pages (they have their own UI)
const shouldHideWidget = computed(() => {
    const url = page.url;
    return url.startsWith('/messages') || url.startsWith('/support') || url.startsWith('/contact');
});

const {
    isOpen,
    toggleChat,
    unreadCount,
    initializeEcho,
    cleanup,
} = useGlobalMessaging();

onMounted(() => {
    if (currentUser.value) {
        initializeEcho(currentUser.value.id);
    }
});

onUnmounted(() => {
    cleanup();
});

// Re-initialize when user changes
watch(currentUser, (user) => {
    if (user) {
        initializeEcho(user.id);
    } else {
        cleanup();
    }
});
</script>

<template>
    <!-- For authenticated users: full chat widget (hidden on Messages/Support/Contact pages) -->
    <div v-if="currentUser && !shouldHideWidget">
        <!-- Floating Button -->
        <button
            @click="toggleChat"
            class="fixed bottom-6 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg transition-all hover:bg-blue-700 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            :class="{ 'bg-blue-700': isOpen }"
        >
            <!-- Chat icon -->
            <svg
                v-if="!isOpen"
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                />
            </svg>
            <!-- Close icon -->
            <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                />
            </svg>

            <!-- Unread badge -->
            <span
                v-if="unreadCount > 0 && !isOpen"
                class="absolute -right-1 -top-1 flex h-6 min-w-6 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Chat Panel -->
        <ChatPanel />
    </div>

    <!-- For non-authenticated users: contact button (hidden on contact page) -->
    <Link
        v-else-if="!shouldHideWidget"
        :href="route('contact.create')"
        class="fixed bottom-6 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg transition-all hover:bg-blue-700 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        title="Nous contacter"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"
            />
        </svg>
    </Link>
</template>
