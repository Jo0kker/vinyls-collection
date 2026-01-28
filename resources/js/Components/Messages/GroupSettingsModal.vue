<script setup>
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    conversation: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

const page = usePage();
const currentUser = page.props.auth?.user;

const groupName = ref(props.conversation.name);
const isUpdating = ref(false);

const isAdmin = props.conversation.participants?.find(
    (p) => p.id === currentUser?.id
)?.is_admin;

watch(() => props.conversation.name, (newName) => {
    groupName.value = newName;
});

const updateGroupName = () => {
    if (!groupName.value.trim() || groupName.value === props.conversation.name) return;

    isUpdating.value = true;
    router.patch(
        route('messages.group.update', props.conversation.id),
        { name: groupName.value },
        {
            preserveScroll: true,
            onFinish: () => {
                isUpdating.value = false;
            },
        }
    );
};

const removeParticipant = (participantId) => {
    if (!confirm('Êtes-vous sûr de vouloir retirer ce participant ?')) return;

    router.delete(
        route('messages.participants.remove', [props.conversation.id, participantId]),
        { preserveScroll: true }
    );
};

const leaveGroup = () => {
    if (!confirm('Êtes-vous sûr de vouloir quitter ce groupe ?')) return;

    router.post(route('messages.leave', props.conversation.id));
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')" max-width="md">
        <div class="p-6">
            <h2 class="mb-6 text-lg font-semibold text-gray-900 dark:text-gray-100">
                Paramètres du groupe
            </h2>

            <!-- Group name -->
            <div class="mb-6">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nom du groupe
                </label>
                <div class="flex gap-2">
                    <input
                        v-model="groupName"
                        type="text"
                        :disabled="!isAdmin"
                        class="flex-1 rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:disabled:bg-gray-800"
                    />
                    <button
                        v-if="isAdmin"
                        @click="updateGroupName"
                        :disabled="isUpdating || !groupName.trim() || groupName === conversation.name"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ isUpdating ? '...' : 'Modifier' }}
                    </button>
                </div>
            </div>

            <!-- Participants -->
            <div class="mb-6">
                <h3 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Participants ({{ conversation.participants?.length || 0 }})
                </h3>
                <div class="max-h-60 space-y-2 overflow-y-auto">
                    <div
                        v-for="participant in conversation.participants"
                        :key="participant.id"
                        class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-700"
                    >
                        <div class="flex items-center gap-3">
                            <img
                                v-if="participant.avatar"
                                :src="participant.avatar"
                                class="h-10 w-10 rounded-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-400 text-white"
                            >
                                {{ participant.name?.charAt(0)?.toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ participant.name }}
                                    <span v-if="participant.id === currentUser?.id" class="text-sm text-gray-500">
                                        (vous)
                                    </span>
                                </p>
                                <p v-if="participant.is_admin" class="text-xs text-blue-600 dark:text-blue-400">
                                    Administrateur
                                </p>
                            </div>
                        </div>

                        <button
                            v-if="isAdmin && participant.id !== currentUser?.id"
                            @click="removeParticipant(participant.id)"
                            class="rounded-lg p-2 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                            title="Retirer du groupe"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Leave group -->
            <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <button
                    @click="leaveGroup"
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-50 px-4 py-3 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" />
                    </svg>
                    Quitter le groupe
                </button>
            </div>

            <!-- Close button -->
            <div class="mt-4 flex justify-end">
                <button
                    @click="$emit('close')"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    Fermer
                </button>
            </div>
        </div>
    </Modal>
</template>
