<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav
                class="v-navbar shadow py-4 bg-white dark:bg-gray-800"
            >
                <!-- Primary Navigation Menu -->
                <div class="container mx-auto px-4 md:flex md:items-center md:gap-4">
                    <div class="flex justify-between items-center">
                        <!-- Logo -->
                        <Link href="/" class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            <ApplicationLogo />
                        </Link>
                        
                        <!-- Mobile menu button -->
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
                        <!-- Navigation Links -->
                        <ul class="flex flex-col md:flex-row gap-3 mb-4 md:mb-0">
                            <li v-if="$page.props.auth.user">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Tableau de bord
                                </NavLink>
                            </li>
                            <li>
                                <NavLink
                                    :href="route('forum.index')"
                                    :active="route().current('forum.*')"
                                >
                                    Forum
                                </NavLink>
                            </li>
                            <li v-if="$page.props.auth.user">
                                <NavLink
                                    :href="route('collections.index')"
                                    :active="route().current('collections.*')"
                                >
                                    Mes Collections
                                </NavLink>
                            </li>
                            <li>
                                <NavLink
                                    :href="route('collectors.index')"
                                    :active="route().current('collectors.*')"
                                >
                                    Collectionneurs
                                </NavLink>
                            </li>
                            <li v-if="!$page.props.auth.user">
                                <Link
                                    :href="route('login')"
                                    class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700 focus:outline-none focus:text-gray-700 dark:focus:text-gray-300 focus:border-gray-300 dark:focus:border-gray-700 transition duration-150 ease-in-out"
                                >
                                    Connexion
                                </Link>
                            </li>
                            <li v-if="!$page.props.auth.user">
                                <Link
                                    :href="route('register')"
                                    class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium transition-colors"
                                >
                                    Inscription
                                </Link>
                            </li>
                        </ul>

                        <!-- User menu - desktop -->
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

                        <!-- User menu - mobile -->
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
                                <ResponsiveNavLink :href="route('profile.edit')">
                                    Profil
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                >
                                    Déconnexion
                                </ResponsiveNavLink>
                            </div>
                        </div>
                    </div>
                </div>

            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow dark:bg-gray-800"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
