<script setup>
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useGlobalMessaging } from '@/composables/useGlobalMessaging';
import ChatConversationList from './ChatConversationList.vue';
import ChatConversation from './ChatConversation.vue';
import ChatNewConversation from './ChatNewConversation.vue';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const {
    isOpen,
    conversations,
    activeConversation,
    messages,
    isLoadingConversations,
    isLoadingMessages,
    view,
    setView,
    selectConversation,
    closeConversation,
    loadConversations,
    openSupportConversation,
} = useGlobalMessaging();

const isOpeningSupport = ref(false);

const handleOpenSupport = async () => {
    if (isOpeningSupport.value) return;
    isOpeningSupport.value = true;
    try {
        await openSupportConversation();
    } catch (error) {
        console.error('Failed to open support:', error);
    } finally {
        isOpeningSupport.value = false;
    }
};

// Load conversations when panel opens
watch(isOpen, (open) => {
    if (open && conversations.value.length === 0) {
        loadConversations();
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-4 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-4 scale-95"
    >
        <div
            v-if="isOpen"
            class="fixed bottom-24 right-6 z-40 flex h-[500px] w-[380px] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-800"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 bg-blue-600 px-4 py-3 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <button
                        v-if="view !== 'list'"
                        @click="view === 'conversation' ? closeConversation() : setView('list')"
                        class="rounded-lg p-1 text-white/80 hover:bg-white/10 hover:text-white"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <h3 class="font-semibold text-white">
                        <template v-if="view === 'list'">Messages</template>
                        <template v-else-if="view === 'new'">Nouvelle conversation</template>
                        <template v-else-if="activeConversation">{{ activeConversation.name }}</template>
                    </h3>
                </div>
                <div v-if="view === 'list'" class="flex items-center gap-1">
                    <!-- Support button -->
                    <button
                        @click="handleOpenSupport"
                        :disabled="isOpeningSupport"
                        class="rounded-lg p-1.5 text-white/80 hover:bg-white/10 hover:text-white disabled:opacity-50"
                        title="Contacter le support"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.562C15.802 8.249 16 9.1 16 10zm-5.165 3.913l1.58 1.58A5.98 5.98 0 0110 16a5.976 5.976 0 01-2.516-.552l1.562-1.562a4.006 4.006 0 001.789.027zm-4.677-2.796a4.002 4.002 0 01-.041-2.08l-.08.08-1.53-1.533A5.98 5.98 0 004 10c0 .954.223 1.856.619 2.657l1.54-1.54zm1.088-6.45A5.974 5.974 0 0110 4c.954 0 1.856.223 2.657.619l-1.54 1.54a4.002 4.002 0 00-2.346.033L7.246 4.668zM12 10a2 2 0 11-4 0 2 2 0 014 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <!-- New conversation button -->
                    <button
                        @click="setView('new')"
                        class="rounded-lg p-1.5 text-white/80 hover:bg-white/10 hover:text-white"
                        title="Nouvelle conversation"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-hidden">
                <!-- Conversation List -->
                <ChatConversationList
                    v-if="view === 'list'"
                    :conversations="conversations"
                    :is-loading="isLoadingConversations"
                    @select="selectConversation"
                    @new="setView('new')"
                />

                <!-- Active Conversation -->
                <ChatConversation
                    v-else-if="view === 'conversation' && activeConversation"
                    :conversation="activeConversation"
                    :messages="messages"
                    :is-loading="isLoadingMessages"
                    :current-user="currentUser"
                />

                <!-- New Conversation -->
                <ChatNewConversation
                    v-else-if="view === 'new'"
                    @created="selectConversation"
                    @cancel="setView('list')"
                />
            </div>
        </div>
    </Transition>
</template>
