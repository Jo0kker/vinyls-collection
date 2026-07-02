<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: 'all' }),
    },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const banReasons = ref({});
const deletingUserId = ref(null);
const deleteForumMessages = ref(false);

let searchTimeout = null;

watch([search, status], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.users.index'), {
            search: search.value,
            status: status.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 250);
});

const banUser = (user) => {
    const form = useForm({
        banned_reason: banReasons.value[user.id] || '',
    });

    form.patch(route('admin.users.ban', user.id), {
        preserveScroll: true,
        onSuccess: () => {
            banReasons.value[user.id] = '';
        },
    });
};

const unbanUser = (user) => {
    router.patch(route('admin.users.unban', user.id), {}, {
        preserveScroll: true,
    });
};

const askDelete = (user) => {
    deletingUserId.value = user.id;
    deleteForumMessages.value = false;
};

const cancelDelete = () => {
    deletingUserId.value = null;
    deleteForumMessages.value = false;
};

const deleteUser = (user) => {
    const form = useForm({
        delete_forum_messages: deleteForumMessages.value,
    });

    form.delete(route('admin.users.destroy', user.id), {
        preserveScroll: true,
        onSuccess: cancelDelete,
    });
};

const roleNames = (user) => (user.roles || []).map((role) => role.name).join(', ') || 'Aucun rôle';
</script>

<template>
    <Head title="Admin utilisateurs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Administration des utilisateurs
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                    <div class="grid gap-4 md:grid-cols-[1fr_200px]">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                                Rechercher
                            </label>
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Nom ou email"
                                class="w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                                Statut
                            </label>
                            <select
                                v-model="status"
                                class="w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            >
                                <option value="all">Tous</option>
                                <option value="active">Actifs</option>
                                <option value="banned">Suspendus</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/60">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Utilisateur</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Rôles</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Forum</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Statut</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="user in users.data" :key="user.id" class="align-top">
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ user.name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                                        <div class="mt-1 text-xs text-gray-400">ID #{{ user.id }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-200">
                                        {{ roleNames(user) }}
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-200">
                                        <div>{{ user.forum_threads_count }} discussion(s)</div>
                                        <div>{{ user.forum_posts_count }} message(s)</div>
                                        <div>{{ user.collections_count }} collection(s)</div>
                                    </td>
                                    <td class="px-4 py-4 text-sm">
                                        <span
                                            v-if="user.banned_at"
                                            class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-200"
                                        >
                                            Suspendu
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-200"
                                        >
                                            Actif
                                        </span>
                                        <div v-if="user.banned_reason" class="mt-2 max-w-xs text-xs text-gray-500 dark:text-gray-400">
                                            {{ user.banned_reason }}
                                        </div>
                                    </td>
                                    <td class="space-y-3 px-4 py-4 text-sm">
                                        <div v-if="!user.banned_at" class="space-y-2">
                                            <textarea
                                                v-model="banReasons[user.id]"
                                                rows="2"
                                                placeholder="Motif de suspension (optionnel)"
                                                class="w-full min-w-64 rounded-md border-gray-300 bg-white text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                            ></textarea>
                                            <button
                                                type="button"
                                                class="rounded-md bg-orange-600 px-3 py-1.5 text-white hover:bg-orange-700"
                                                @click="banUser(user)"
                                            >
                                                Suspendre
                                            </button>
                                        </div>
                                        <button
                                            v-else
                                            type="button"
                                            class="rounded-md bg-green-600 px-3 py-1.5 text-white hover:bg-green-700"
                                            @click="unbanUser(user)"
                                        >
                                            Réactiver
                                        </button>

                                        <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
                                            <button
                                                v-if="deletingUserId !== user.id"
                                                type="button"
                                                class="rounded-md bg-red-600 px-3 py-1.5 text-white hover:bg-red-700"
                                                @click="askDelete(user)"
                                            >
                                                Supprimer
                                            </button>

                                            <div v-else class="rounded-md border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-950/30">
                                                <p class="font-medium text-red-800 dark:text-red-200">
                                                    Confirmer la suppression ?
                                                </p>
                                                <label class="mt-2 flex items-start gap-2 text-sm text-red-700 dark:text-red-200">
                                                    <input v-model="deleteForumMessages" type="checkbox" class="mt-1 rounded border-red-300" />
                                                    <span>Supprimer aussi définitivement ses discussions et messages forum.</span>
                                                </label>
                                                <p v-if="!deleteForumMessages" class="mt-2 text-xs text-red-700 dark:text-red-200">
                                                    Sans cette option, les messages forum sont conservés sous “Utilisateur supprimé”.
                                                </p>
                                                <div class="mt-3 flex gap-2">
                                                    <button type="button" class="rounded-md bg-red-700 px-3 py-1.5 text-white hover:bg-red-800" @click="deleteUser(user)">
                                                        Confirmer
                                                    </button>
                                                    <button type="button" class="rounded-md bg-gray-200 px-3 py-1.5 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100" @click="cancelDelete">
                                                        Annuler
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Aucun utilisateur trouvé.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="users.links?.length" class="flex flex-wrap gap-2 border-t border-gray-200 p-4 dark:border-gray-700">
                        <Link
                            v-for="link in users.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            preserve-scroll
                            preserve-state
                            class="rounded-md px-3 py-1 text-sm"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-100',
                                !link.url ? 'pointer-events-none opacity-50' : 'hover:bg-indigo-100 dark:hover:bg-gray-600',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
