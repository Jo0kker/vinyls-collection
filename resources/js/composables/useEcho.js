import { ref, onMounted, onUnmounted } from 'vue';

const echoReady = ref(false);
const echoError = ref(null);

export function useEcho() {
    const checkEcho = () => {
        if (window.Echo) {
            echoReady.value = true;
            return true;
        }
        return false;
    };

    onMounted(() => {
        if (!checkEcho()) {
            // Wait for Echo to be initialized
            const interval = setInterval(() => {
                if (checkEcho()) {
                    clearInterval(interval);
                }
            }, 100);

            // Timeout after 5 seconds
            setTimeout(() => {
                clearInterval(interval);
                if (!echoReady.value) {
                    echoError.value = 'WebSocket connection failed';
                }
            }, 5000);
        }
    });

    const subscribeToPrivate = (channel, callback) => {
        if (!window.Echo) {
            console.warn('Echo not initialized');
            return null;
        }

        return window.Echo.private(channel)
            .listen('.message.sent', (e) => callback('message.sent', e))
            .listen('.message.read', (e) => callback('message.read', e))
            .listen('.user.typing', (e) => callback('user.typing', e))
            .listen('.conversation.updated', (e) => callback('conversation.updated', e));
    };

    const subscribeToUser = (userId, callback) => {
        if (!window.Echo) {
            console.warn('Echo not initialized');
            return null;
        }

        return window.Echo.private(`user.${userId}`)
            .listen('.new.message', (e) => callback('new.message', e));
    };

    const leaveChannel = (channel) => {
        if (window.Echo) {
            window.Echo.leave(channel);
        }
    };

    return {
        echoReady,
        echoError,
        subscribeToPrivate,
        subscribeToUser,
        leaveChannel,
    };
}
