import { ref, reactive } from 'vue';

const notifications = ref([]);
let notificationId = 0;

export function useNotifications() {
    const addNotification = (type, title, message = '', duration = 5000) => {
        const id = ++notificationId;
        notifications.value.push({
            id,
            type,
            title,
            message,
            duration
        });
        
        return id;
    };

    const removeNotification = (id) => {
        const index = notifications.value.findIndex(n => n.id === id);
        if (index > -1) {
            notifications.value.splice(index, 1);
        }
    };

    const success = (title, message = '', duration = 5000) => {
        return addNotification('success', title, message, duration);
    };

    const error = (title, message = '', duration = 5000) => {
        return addNotification('error', title, message, duration);
    };

    const warning = (title, message = '', duration = 5000) => {
        return addNotification('warning', title, message, duration);
    };

    const info = (title, message = '', duration = 5000) => {
        return addNotification('info', title, message, duration);
    };

    const clear = () => {
        notifications.value = [];
    };

    return {
        notifications,
        addNotification,
        removeNotification,
        success,
        error,
        warning,
        info,
        clear
    };
}