<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ForumSubNav from '@/Components/ForumSubNav.vue';
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <slot name="header" />
        </template>

        <ForumSubNav />

        <slot />
    </AuthenticatedLayout>
</template>
