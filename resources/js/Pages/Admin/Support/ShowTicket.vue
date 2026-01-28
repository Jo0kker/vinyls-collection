<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    admins: {
        type: Array,
        default: () => [],
    },
});

const replyForm = useForm({
    message: '',
    is_internal_note: false,
    send_email: true,
});

const statusForm = useForm({
    status: props.ticket.status,
});

const assignForm = useForm({
    assigned_to: props.ticket.assigned_to,
});

const showAssignModal = ref(false);

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const statusBadge = (status) => {
    const badges = {
        open: { label: 'Ouvert', class: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' },
        pending: { label: 'En attente', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' },
        resolved: { label: 'Resolu', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' },
        closed: { label: 'Ferme', class: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' },
    };
    return badges[status] || badges.open;
};

const submitReply = () => {
    replyForm.post(route('admin.support.tickets.reply', props.ticket.id), {
        onSuccess: () => {
            replyForm.reset();
        },
    });
};

const updateStatus = (status) => {
    statusForm.status = status;
    statusForm.patch(route('admin.support.tickets.status', props.ticket.id));
};

const assignTicket = () => {
    assignForm.patch(route('admin.support.tickets.assign', props.ticket.id), {
        onSuccess: () => {
            showAssignModal.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Ticket ${ticket.reference}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('admin.support.index')"
                    class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Ticket {{ ticket.reference }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <!-- Ticket Info -->
                <div class="mb-8 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ ticket.subject }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                De : {{ ticket.email }}
                                <span v-if="ticket.user" class="ml-2 text-blue-600 dark:text-blue-400">
                                    (compte : {{ ticket.user.name }})
                                </span>
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Cree le {{ formatDate(ticket.created_at) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span :class="['rounded-full px-3 py-1 text-sm font-medium', statusBadge(ticket.status).class]">
                                {{ statusBadge(ticket.status).label }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                        <p class="whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ ticket.message }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            @click="showAssignModal = true"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                        >
                            {{ ticket.assigned_admin ? `Assigne a ${ticket.assigned_admin.name}` : 'Assigner' }}
                        </button>
                        <button
                            v-if="ticket.status !== 'resolved'"
                            @click="updateStatus('resolved')"
                            :disabled="statusForm.processing"
                            class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            Marquer resolu
                        </button>
                        <button
                            v-if="ticket.status !== 'closed'"
                            @click="updateStatus('closed')"
                            :disabled="statusForm.processing"
                            class="rounded-lg bg-gray-600 px-3 py-1.5 text-sm text-white hover:bg-gray-700 disabled:opacity-50"
                        >
                            Fermer
                        </button>
                        <button
                            v-if="ticket.status === 'closed' || ticket.status === 'resolved'"
                            @click="updateStatus('open')"
                            :disabled="statusForm.processing"
                            class="rounded-lg bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-700 disabled:opacity-50"
                        >
                            Reouvrir
                        </button>
                    </div>
                </div>

                <!-- Replies -->
                <div class="mb-8 space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Historique ({{ ticket.replies.length }} reponse(s))
                    </h4>

                    <div
                        v-for="reply in ticket.replies"
                        :key="reply.id"
                        :class="[
                            'rounded-lg p-4',
                            reply.is_internal_note
                                ? 'border-2 border-dashed border-yellow-400 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-900/20'
                                : 'bg-white shadow dark:bg-gray-800'
                        ]"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ reply.admin?.name || 'Admin' }}
                                </span>
                                <span v-if="reply.is_internal_note" class="rounded bg-yellow-200 px-2 py-0.5 text-xs text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200">
                                    Note interne
                                </span>
                                <span v-if="reply.email_sent" class="rounded bg-green-200 px-2 py-0.5 text-xs text-green-800 dark:bg-green-800 dark:text-green-200">
                                    Email envoye
                                </span>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ formatDate(reply.created_at) }}
                            </span>
                        </div>
                        <p class="mt-2 whitespace-pre-wrap text-gray-700 dark:text-gray-300">
                            {{ reply.message }}
                        </p>
                    </div>
                </div>

                <!-- Reply Form -->
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h4 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Repondre
                    </h4>

                    <form @submit.prevent="submitReply" class="space-y-4">
                        <div>
                            <textarea
                                v-model="replyForm.message"
                                rows="4"
                                required
                                placeholder="Votre reponse..."
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            ></textarea>
                            <p v-if="replyForm.errors.message" class="mt-1 text-sm text-red-600">
                                {{ replyForm.errors.message }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-4">
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="replyForm.is_internal_note"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Note interne (non visible par l'utilisateur)</span>
                            </label>

                            <label v-if="!replyForm.is_internal_note" class="flex items-center gap-2">
                                <input
                                    v-model="replyForm.send_email"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Envoyer par email</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            :disabled="replyForm.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ replyForm.processing ? 'Envoi...' : 'Envoyer la reponse' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Assign Modal -->
        <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Assigner le ticket
                </h3>

                <select
                    v-model="assignForm.assigned_to"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                >
                    <option :value="null">Non assigne</option>
                    <option v-for="admin in admins" :key="admin.id" :value="admin.id">
                        {{ admin.name }}
                    </option>
                </select>

                <div class="mt-4 flex justify-end gap-2">
                    <button
                        @click="showAssignModal = false"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                    >
                        Annuler
                    </button>
                    <button
                        @click="assignTicket"
                        :disabled="assignForm.processing"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        Assigner
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
