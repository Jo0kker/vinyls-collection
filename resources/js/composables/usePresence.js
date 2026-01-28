import { ref } from 'vue';

// Global singleton state
const onlineUsers = ref(new Map());
let presenceChannel = null;
let isInitialized = false;

export function usePresence() {
    const initPresence = () => {
        if (isInitialized || !window.Echo) {
            return;
        }

        isInitialized = true;

        presenceChannel = window.Echo.join('presence.online')
            .here((users) => {
                onlineUsers.value = new Map(users.map(u => [u.id, u]));
            })
            .joining((user) => {
                onlineUsers.value.set(user.id, user);
            })
            .leaving((user) => {
                onlineUsers.value.delete(user.id);
            })
            .error((error) => {
                console.error('Presence channel error:', error);
            });
    };

    const leavePresence = () => {
        if (window.Echo && presenceChannel) {
            window.Echo.leave('presence.online');
            presenceChannel = null;
            isInitialized = false;
            onlineUsers.value.clear();
        }
    };

    const isOnline = (userId) => {
        return onlineUsers.value.has(userId);
    };

    const getOnlineUsers = () => {
        return Array.from(onlineUsers.value.values());
    };

    return {
        onlineUsers,
        isOnline,
        getOnlineUsers,
        initPresence,
        leavePresence,
    };
}
