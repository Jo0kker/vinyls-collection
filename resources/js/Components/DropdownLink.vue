<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="dropdown-link block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-100 focus:outline-none"
    >
        <slot />
    </Link>
</template>

<style>
.dropdown-link:hover,
.dropdown-link:focus {
    background-color: rgb(243 244 246) !important; /* gray-100 */
    color: rgb(17 24 39) !important; /* gray-900 */
}

html.dark .dropdown-link:hover,
html.dark .dropdown-link:focus {
    background-color: rgb(75 85 99) !important; /* gray-600 */
    color: rgb(255 255 255) !important; /* white */
}
</style>
