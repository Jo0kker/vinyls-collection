<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const searchQuery = ref('');
const searchResults = ref([]);
const selectedUsers = ref([]);
const isSearching = ref(false);
const conversationType = ref('direct');
const groupName = ref('');

const canCreate = computed(() => {
    if (conversationType.value === 'direct') {
        return selectedUsers.value.length === 1;
    }
    return selectedUsers.value.length >= 1 && groupName.value.trim();
});

// Search users
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
            const response = await axios.get('/api/users/search', {
                params: { q: query },
            });
            searchResults.value = response.data.filter(
                (user) => !selectedUsers.value.some((u) => u.id === user.id)
            );
        } catch (error) {
            console.error('Search failed:', error);
            searchResults.value = [];
        } finally {
            isSearching.value = false;
        }
    }, 300);
});

const selectUser = (user) => {
    if (conversationType.value === 'direct') {
        selectedUsers.value = [user];
    } else {
        if (!selectedUsers.value.some((u) => u.id === user.id)) {
            selectedUsers.value.push(user);
        }
    }
    searchQuery.value = '';
    searchResults.value = [];
};

const removeUser = (userId) => {
    selectedUsers.value = selectedUsers.value.filter((u) => u.id !== userId);
};

const createConversation = () => {
    if (!canCreate.value) return;

    router.post(route('messages.store'), {
        type: conversationType.value,
        participant_ids: selectedUsers.value.map((u) => u.id),
        name: conversationType.value === 'group' ? groupName.value : null,
    });

    closeModal();
};

const closeModal = () => {
    searchQuery.value = '';
    searchResults.value = [];
    selectedUsers.value = [];
    conversationType.value = 'direct';
    groupName.value = '';
    emit('close');
};

watch(() => props.show, (show) => {
    if (!show) {
        closeModal();
    }
});
</script>

<template>
    <Modal :show="show" @close="closeModal" max-width="md">
        <div class="p-6">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                Nouvelle conversation
            </h2>

            <!-- Conversation type -->
            <div class="mb-4">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Type de conversation
                </label>
                <div class="flex gap-4">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input
                            type="radio"
                            v-model="conversationType"
                            value="direct"
                            class="text-blue-600 focus:ring-blue-500"
                        />
                        <span class="text-sm text-gray-700 dark:text-gray-300">Message direct</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input
                            type="radio"
                            v-model="conversationType"
                            value="group"
                            class="text-blue-600 focus:ring-blue-500"
                        />
                        <span class="text-sm text-gray-700 dark:text-gray-300">Groupe</span>
                    </label>
                </div>
            </div>

            <!-- Group name -->
            <div v-if="conversationType === 'group'" class="mb-4">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nom du groupe
                </label>
                <input
                    v-model="groupName"
                    type="text"
                    placeholder="Ex: Collectionneurs de jazz"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                />
            </div>

            <!-- Selected users -->
            <div v-if="selectedUsers.length > 0" class="mb-4">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ conversationType === 'direct' ? 'Destinataire' : 'Participants' }}
                </label>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="user in selectedUsers"
                        :key="user.id"
                        class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                    >
                        {{ user.name }}
                        <button
                            @click="removeUser(user.id)"
                            class="ml-1 hover:text-blue-600 dark:hover:text-blue-400"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </span>
                </div>
            </div>

            <!-- User search -->
            <div class="mb-4">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Rechercher un utilisateur
                </label>
                <div class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Nom ou email..."
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />

                    <!-- Search results dropdown -->
                    <div
                        v-if="searchResults.length > 0 || isSearching"
                        class="absolute left-0 right-0 top-full z-10 mt-1 max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700"
                    >
                        <div v-if="isSearching" class="p-3 text-center text-sm text-gray-500">
                            Recherche...
                        </div>
                        <button
                            v-else
                            v-for="user in searchResults"
                            :key="user.id"
                            @click="selectUser(user)"
                            class="flex w-full items-center gap-3 px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600"
                        >
                            <img
                                v-if="user.avatar"
                                :src="user.avatar"
                                class="h-8 w-8 rounded-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-400 text-sm text-white"
                            >
                                {{ user.name?.charAt(0)?.toUpperCase() }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ user.name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ user.email }}
                                </p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <button
                    @click="closeModal"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    Annuler
                </button>
                <button
                    @click="createConversation"
                    :disabled="!canCreate"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Créer
                </button>
            </div>
        </div>
    </Modal>
</template>
