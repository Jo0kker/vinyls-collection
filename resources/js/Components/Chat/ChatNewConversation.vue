<script setup>
import { ref, watch, computed } from 'vue';
import { useGlobalMessaging } from '@/composables/useGlobalMessaging';

const emit = defineEmits(['created', 'cancel']);

const { createConversation, searchUsers } = useGlobalMessaging();

const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const isCreating = ref(false);
const selectedUsers = ref([]);

let searchTimeout = null;

watch(searchQuery, (query) => {
    clearTimeout(searchTimeout);

    if (query.length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const results = await searchUsers(query);
            searchResults.value = results.filter(
                u => !selectedUsers.value.some(s => s.id === u.id)
            );
        } catch (error) {
            searchResults.value = [];
        } finally {
            isSearching.value = false;
        }
    }, 300);
});

const addUser = (user) => {
    if (!selectedUsers.value.some(u => u.id === user.id)) {
        selectedUsers.value.push(user);
    }
    searchQuery.value = '';
    searchResults.value = [];
};

const removeUser = (userId) => {
    selectedUsers.value = selectedUsers.value.filter(u => u.id !== userId);
};

const startConversation = async () => {
    if (selectedUsers.value.length === 0) return;

    isCreating.value = true;
    try {
        const type = selectedUsers.value.length > 1 ? 'group' : 'direct';
        const conversation = await createConversation(
            type,
            selectedUsers.value.map(u => u.id),
            null
        );
        emit('created', conversation);
    } catch (error) {
        console.error('Failed to create conversation:', error);
    } finally {
        isCreating.value = false;
    }
};
</script>

<template>
    <div class="flex h-full flex-col">
        <!-- Selected users + button -->
        <div v-if="selectedUsers.length > 0" class="border-b border-gray-200 p-3 dark:border-gray-700">
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="user in selectedUsers"
                    :key="user.id"
                    class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-800 dark:text-blue-100"
                >
                    {{ user.name }}
                    <button @click="removeUser(user.id)" class="ml-1 hover:text-blue-600 dark:hover:text-blue-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </span>
            </div>

            <button
                @click="startConversation"
                :disabled="isCreating"
                class="mt-3 w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span v-if="isCreating" class="flex items-center justify-center gap-2">
                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </span>
                <span v-else>Démarrer</span>
            </button>
        </div>

        <!-- Search input -->
        <div class="border-b border-gray-200 p-3 dark:border-gray-700">
            <input
                v-model="searchQuery"
                type="text"
                :placeholder="selectedUsers.length > 0 ? 'Ajouter quelqu\'un...' : 'Rechercher...'"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
            />
        </div>

        <!-- Results -->
        <div class="flex-1 overflow-y-auto">
            <div v-if="isSearching" class="flex items-center justify-center p-8">
                <svg class="h-6 w-6 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            <div v-else-if="searchResults.length > 0">
                <button
                    v-for="user in searchResults"
                    :key="user.id"
                    @click="addUser(user)"
                    class="flex w-full items-center gap-3 border-b border-gray-100 px-4 py-3 text-left transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50"
                >
                    <img
                        v-if="user.avatar"
                        :src="user.avatar"
                        :alt="user.name"
                        class="h-10 w-10 rounded-full object-cover"
                    />
                    <div
                        v-else
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500 text-sm font-medium text-white"
                    >
                        {{ user.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <p class="flex-1 truncate font-medium text-gray-900 dark:text-gray-100">{{ user.name }}</p>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div v-else-if="searchQuery.length >= 2 && !isSearching" class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                Aucun résultat
            </div>

            <div v-else class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                Recherchez des utilisateurs
            </div>
        </div>
    </div>
</template>
