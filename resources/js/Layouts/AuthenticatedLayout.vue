<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ChatWidget from '@/Components/Chat/ChatWidget.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useGlobalMessaging } from '@/composables/useGlobalMessaging';
import { usePresence } from '@/composables/usePresence';

const showingNavigationDropdown = ref(false);

const page = usePage();
const { unreadCount, initializeEcho } = useGlobalMessaging();
const { initPresence, leavePresence } = usePresence();

// Use real-time unread count from useGlobalMessaging
const unreadMessagesCount = computed(() => unreadCount.value);

const isAdmin = computed(() => {
    const roles = page.props.auth?.user?.roles || [];
    return roles.some(role => role.name === 'admin');
});

// Initialize Echo and Presence when component mounts if user is authenticated
onMounted(() => {
    if (page.props.auth?.user?.id) {
        initializeEcho(page.props.auth.user.id);
        // Small delay to ensure Echo is ready
        setTimeout(() => {
            initPresence();
        }, 500);
    }
});

onUnmounted(() => {
    leavePresence();
});
</script>

<template>
    <div>
        <div class="min-h-screen flex flex-col bg-gray-100 dark:bg-gray-900">
            <nav
                class="v-navbar shadow py-4 bg-white dark:bg-gray-800"
            >

                <div class="container mx-auto px-4 md:flex md:items-center md:gap-4">
                    <div class="flex justify-between items-center">

                        <Link href="/" class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            <ApplicationLogo />
                        </Link>


                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="navbar-toggler block md:hidden border rounded-md px-2 py-1"
                            type="button"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="navbar-toggler-icon w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>
                    </div>

                    <div
                        :class="{ 'flex flex-col': showingNavigationDropdown, 'hidden': !showingNavigationDropdown }"
                        class="grow justify-between navbar-collapse md:flex"
                    >

                        <ul class="flex flex-col md:flex-row md:items-stretch gap-3 mt-4 md:mt-0 mb-4 md:mb-0 md:flex-1">
                            <li v-if="$page.props.auth.user">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                    class="h-full"
                                >
                                    Tableau de bord
                                </NavLink>
                            </li>
                            <li>
                                <NavLink
                                    :href="route('forum.index')"
                                    :active="route().current('forum.*')"
                                    class="h-full"
                                >
                                    Forum
                                </NavLink>
                            </li>
                            <li v-if="$page.props.auth.user">
                                <NavLink
                                    :href="route('collections.index')"
                                    :active="route().current('collections.*')"
                                    class="h-full"
                                >
                                    Mes Collections
                                </NavLink>
                            </li>
                            <li v-if="$page.props.auth.user">
                                <NavLink
                                    :href="route('messages.index')"
                                    :active="route().current('messages.*')"
                                    class="h-full relative"
                                >
                                    Messages
                                    <span
                                        v-if="unreadMessagesCount > 0"
                                        class="absolute -top-1 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-medium text-white"
                                    >
                                        {{ unreadMessagesCount > 99 ? '99+' : unreadMessagesCount }}
                                    </span>
                                </NavLink>
                            </li>
                            <li>
                                <NavLink
                                    :href="route('collectors.index')"
                                    :active="route().current('collectors.*')"
                                    class="h-full"
                                >
                                    Collectionneurs
                                </NavLink>
                            </li>
                            <li>
                                <NavLink
                                    :href="route('catalog.index')"
                                    :active="route().current('catalog.*')"
                                    class="h-full"
                                >
                                    Vinyles
                                </NavLink>
                            </li>
                        </ul>


                        <!-- Boutons connexion/inscription pour utilisateurs non connectés -->
                        <div v-if="!$page.props.auth.user" class="hidden md:flex items-center gap-3">
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                            >
                                Connexion
                            </Link>
                            <Link
                                :href="route('register')"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium transition-colors"
                            >
                                Inscription
                            </Link>
                        </div>

                        <!-- Menu utilisateur connecté -->
                        <div v-if="$page.props.auth.user" class="hidden md:flex items-center gap-4">
                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-md border border-transparent bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium leading-4 text-gray-500 dark:text-gray-400 transition duration-150 ease-in-out hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none"
                                        >
                                            {{ $page.props.auth.user.name }}

                                            <svg
                                                class="-me-0.5 ms-2 h-4 w-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">
                                            Profil
                                        </DropdownLink>
                                        <DropdownLink :href="route('discogs.import')">
                                            Import Discogs
                                        </DropdownLink>
                                        <template v-if="isAdmin">
                                            <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                            <DropdownLink :href="route('admin.support.index')" class="text-orange-600 dark:text-orange-400">
                                                Admin Support
                                            </DropdownLink>
                                            <DropdownLink :href="route('admin.users.index')" class="text-orange-600 dark:text-orange-400">
                                                Admin Utilisateurs
                                            </DropdownLink>
                                            <DropdownLink :href="route('forum.category.manage')" class="text-orange-600 dark:text-orange-400">
                                                Admin Forum
                                            </DropdownLink>
                                        </template>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Déconnexion
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>


                        <div
                            v-if="$page.props.auth.user"
                            :class="{ 'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown }"
                            class="md:hidden mt-4 pt-4 border-t border-gray-200 dark:border-gray-600"
                        >
                            <div class="px-4 pb-2">
                                <div class="text-base font-medium text-gray-800 dark:text-gray-200">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="text-sm font-medium text-gray-500">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>
                            <div class="space-y-1">
                                <ResponsiveNavLink :href="route('messages.index')" class="flex items-center justify-between">
                                    <span>Messages</span>
                                    <span
                                        v-if="unreadMessagesCount > 0"
                                        class="flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-medium text-white"
                                    >
                                        {{ unreadMessagesCount > 99 ? '99+' : unreadMessagesCount }}
                                    </span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('profile.edit')">
                                    Profil
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('discogs.import')">
                                    Import Discogs
                                </ResponsiveNavLink>
                                <template v-if="isAdmin">
                                    <div class="border-t border-gray-200 dark:border-gray-600 my-2"></div>
                                    <ResponsiveNavLink :href="route('admin.support.index')" class="text-orange-600 dark:text-orange-400">
                                        Admin Support
                                    </ResponsiveNavLink>
                                    <ResponsiveNavLink :href="route('forum.category.manage')" class="text-orange-600 dark:text-orange-400">
                                        Admin Forum
                                    </ResponsiveNavLink>
                                </template>
                                <ResponsiveNavLink
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                >
                                    Déconnexion
                                </ResponsiveNavLink>
                            </div>
                        </div>

                        <!-- Menu mobile pour utilisateurs non connectés -->
                        <div
                            v-if="!$page.props.auth.user"
                            :class="{ 'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown }"
                            class="md:hidden mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 space-y-2"
                        >
                            <Link
                                :href="route('login')"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
                            >
                                Connexion
                            </Link>
                            <Link
                                :href="route('register')"
                                class="block w-full text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium transition-colors"
                            >
                                Inscription
                            </Link>
                        </div>
                    </div>
                </div>

            </nav>


            <header
                class="bg-white shadow dark:bg-gray-800"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>


            <main class="flex-1">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            &copy; {{ new Date().getFullYear() }} {{ $page.props.app?.name || 'Vinyls Collection' }}
                        </div>
                        <nav class="flex flex-wrap justify-center gap-4 text-sm">
                            <Link :href="route('legal.mentions')" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                                Mentions légales
                            </Link>
                            <Link :href="route('legal.cgu')" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                                CGU
                            </Link>
                            <Link :href="route('legal.privacy')" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                                Confidentialité
                            </Link>
                            <Link :href="route('contact.create')" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                                Contact
                            </Link>
                        </nav>
                    </div>
                </div>
            </footer>
        </div>

        <!-- Global Chat Widget -->
        <ChatWidget />
    </div>
</template>
