<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
    tickets: {
        type: Object,
        required: true,
    },
});

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

const priorityBadge = (priority) => {
    const badges = {
        low: { label: 'Basse', class: 'text-gray-600 dark:text-gray-400' },
        normal: { label: 'Normale', class: 'text-blue-600 dark:text-blue-400' },
        high: { label: 'Haute', class: 'text-orange-600 dark:text-orange-400' },
        urgent: { label: 'Urgente', class: 'text-red-600 dark:text-red-400' },
    };
    return badges[priority] || badges.normal;
};

const totalUnread = computed(() => {
    return props.conversations.reduce((sum, c) => sum + (c.unread_count || 0), 0);
});
</script>

<template>
    <Head title="Admin - Support" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Gestion du Support
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Conversations</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ conversations.length }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Messages non lus</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ totalUnread }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Tickets</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ tickets.total }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Tickets ouverts</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ tickets.data.filter(t => t.status === 'open').length }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Conversations (logged-in users) -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Conversations Support
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Utilisateurs connectes
                            </p>
                        </div>

                        <div v-if="conversations.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                            Aucune conversation de support
                        </div>

                        <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                            <Link
                                v-for="conversation in conversations"
                                :key="conversation.id"
                                :href="route('support.show', conversation.id)"
                                class="block px-6 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <img
                                                v-if="conversation.creator?.avatar"
                                                :src="conversation.creator.avatar"
                                                :alt="conversation.creator.name"
                                                class="h-10 w-10 rounded-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500 text-white"
                                            >
                                                {{ conversation.creator?.name?.charAt(0)?.toUpperCase() || '?' }}
                                            </div>
                                            <span
                                                v-if="conversation.unread_count > 0"
                                                class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white"
                                            >
                                                {{ conversation.unread_count }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ conversation.creator?.name || 'Utilisateur' }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ conversation.creator?.email }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span :class="['rounded-full px-2 py-1 text-xs font-medium', statusBadge(conversation.support_status).class]">
                                            {{ statusBadge(conversation.support_status).label }}
                                        </span>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatDate(conversation.updated_at) }}
                                        </p>
                                    </div>
                                </div>
                                <p v-if="conversation.latest_message" class="mt-2 truncate text-sm text-gray-600 dark:text-gray-400">
                                    {{ conversation.latest_message.content }}
                                </p>
                            </Link>
                        </div>
                    </div>

                    <!-- Tickets (non-logged-in users) -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Tickets Support
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Formulaire de contact
                            </p>
                        </div>

                        <div v-if="tickets.data.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                            Aucun ticket de support
                        </div>

                        <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                            <Link
                                v-for="ticket in tickets.data"
                                :key="ticket.id"
                                :href="route('admin.support.tickets.show', ticket.id)"
                                class="block px-6 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-sm text-gray-500 dark:text-gray-400">
                                                {{ ticket.reference }}
                                            </span>
                                            <span :class="['rounded-full px-2 py-1 text-xs font-medium', statusBadge(ticket.status).class]">
                                                {{ statusBadge(ticket.status).label }}
                                            </span>
                                        </div>
                                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                                            {{ ticket.subject }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ ticket.email }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span :class="['text-xs font-medium', priorityBadge(ticket.priority).class]">
                                            {{ priorityBadge(ticket.priority).label }}
                                        </span>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatDate(ticket.created_at) }}
                                        </p>
                                        <p v-if="ticket.replies_count > 0" class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ ticket.replies_count }} reponse(s)
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <!-- Pagination -->
                        <div v-if="tickets.last_page > 1" class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div class="flex justify-center gap-2">
                                <Link
                                    v-if="tickets.prev_page_url"
                                    :href="tickets.prev_page_url"
                                    class="rounded-lg border border-gray-300 px-3 py-1 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                                >
                                    Precedent
                                </Link>
                                <span class="px-3 py-1 text-sm text-gray-600 dark:text-gray-400">
                                    Page {{ tickets.current_page }} / {{ tickets.last_page }}
                                </span>
                                <Link
                                    v-if="tickets.next_page_url"
                                    :href="tickets.next_page_url"
                                    class="rounded-lg border border-gray-300 px-3 py-1 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                                >
                                    Suivant
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
