<template>
    <div class="fixed top-4 right-4 z-50 space-y-2">
        <TransitionGroup name="notification" tag="div">
            <Notification
                v-for="notification in notifications"
                :key="notification.id"
                :type="notification.type"
                :title="notification.title"
                :message="notification.message"
                :duration="notification.duration"
                @close="removeNotification(notification.id)"
            />
        </TransitionGroup>
    </div>
</template>

<script setup>
import { useNotifications } from '@/composables/useNotifications.js';
import Notification from './Notification.vue';

const { notifications, removeNotification } = useNotifications();
</script>

<style scoped>
.notification-enter-active, .notification-leave-active {
    transition: all 0.3s ease;
}

.notification-enter-from, .notification-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.notification-move {
    transition: all 0.3s ease;
}
</style>