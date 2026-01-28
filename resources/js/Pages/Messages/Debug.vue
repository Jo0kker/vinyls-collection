<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const connectionStatus = ref('disconnected');
const events = ref([]);
const channels = ref([]);

let echoInstance = null;

const addEvent = (channel, event, data) => {
    events.value.unshift({
        id: Date.now(),
        time: new Date().toLocaleTimeString(),
        channel,
        event,
        data: JSON.stringify(data, null, 2),
    });

    // Keep only last 50 events
    if (events.value.length > 50) {
        events.value.pop();
    }
};

const subscribeToChannel = (channelName, isPrivate = true) => {
    if (!window.Echo) {
        addEvent('system', 'error', { message: 'Echo not initialized' });
        return;
    }

    const channel = isPrivate
        ? window.Echo.private(channelName)
        : window.Echo.channel(channelName);

    // Listen to all events
    channel.listenToAll((event, data) => {
        addEvent(channelName, event, data);
    });

    channels.value.push({ name: channelName, private: isPrivate });
    addEvent('system', 'subscribed', { channel: channelName });
};

onMounted(() => {
    if (window.Echo) {
        connectionStatus.value = 'connected';
        addEvent('system', 'connected', { message: 'Echo initialized' });

        // Subscribe to user channel
        if (currentUser.value?.id) {
            subscribeToChannel(`user.${currentUser.value.id}`);
        }

        // Subscribe to presence channel
        window.Echo.join('presence.messaging')
            .here((users) => {
                addEvent('presence.messaging', 'here', { users });
            })
            .joining((user) => {
                addEvent('presence.messaging', 'joining', { user });
            })
            .leaving((user) => {
                addEvent('presence.messaging', 'leaving', { user });
            })
            .error((error) => {
                addEvent('presence.messaging', 'error', { error });
            });

        channels.value.push({ name: 'presence.messaging', private: false });
    } else {
        connectionStatus.value = 'error';
        addEvent('system', 'error', { message: 'Echo not available' });
    }
});

onUnmounted(() => {
    channels.value.forEach((ch) => {
        if (window.Echo) {
            window.Echo.leave(ch.name);
        }
    });
});

const testChannelInput = ref('');
const subscribeToTestChannel = () => {
    if (testChannelInput.value) {
        subscribeToChannel(testChannelInput.value);
        testChannelInput.value = '';
    }
};

const clearEvents = () => {
    events.value = [];
};
</script>

<template>
    <Head title="WebSocket Debug" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                WebSocket Debug Monitor
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Connection Status -->
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span
                                class="h-3 w-3 rounded-full"
                                :class="{
                                    'bg-green-500': connectionStatus === 'connected',
                                    'bg-red-500': connectionStatus === 'error',
                                    'bg-yellow-500': connectionStatus === 'disconnected',
                                }"
                            ></span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                Status: {{ connectionStatus }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-500">
                            User ID: {{ currentUser?.id }}
                        </div>
                    </div>
                </div>

                <!-- Channel Subscription -->
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <h3 class="mb-4 font-semibold text-gray-900 dark:text-gray-100">
                        Subscribe to Channel
                    </h3>
                    <div class="flex gap-2">
                        <input
                            v-model="testChannelInput"
                            type="text"
                            placeholder="conversation.1"
                            class="flex-1 rounded-lg border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            @keyup.enter="subscribeToTestChannel"
                        />
                        <button
                            @click="subscribeToTestChannel"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                        >
                            Subscribe
                        </button>
                    </div>

                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Active Channels:
                        </h4>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="ch in channels"
                                :key="ch.name"
                                class="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                            >
                                {{ ch.private ? 'private-' : '' }}{{ ch.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Events Log -->
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                            Events Log ({{ events.length }})
                        </h3>
                        <button
                            @click="clearEvents"
                            class="rounded-lg border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            Clear
                        </button>
                    </div>

                    <div class="max-h-[500px] space-y-2 overflow-y-auto">
                        <div
                            v-for="event in events"
                            :key="event.id"
                            class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                        >
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-mono text-blue-600 dark:text-blue-400">
                                    {{ event.channel }}
                                </span>
                                <span class="text-gray-500">{{ event.time }}</span>
                            </div>
                            <div class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                                {{ event.event }}
                            </div>
                            <pre class="mt-2 overflow-x-auto rounded bg-gray-100 p-2 text-xs text-gray-800 dark:bg-gray-900 dark:text-gray-200">{{ event.data }}</pre>
                        </div>

                        <div
                            v-if="events.length === 0"
                            class="py-8 text-center text-gray-500"
                        >
                            No events yet. Waiting for broadcasts...
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <h3 class="mb-4 font-semibold text-gray-900 dark:text-gray-100">
                        Test Instructions
                    </h3>
                    <div class="prose prose-sm dark:prose-invert">
                        <ol class="list-decimal space-y-2 pl-4 text-gray-700 dark:text-gray-300">
                            <li>Make sure Reverb is running: <code>sail artisan reverb:start --debug</code></li>
                            <li>Subscribe to a conversation channel (e.g., <code>conversation.1</code>)</li>
                            <li>Test broadcast from terminal: <code>sail artisan broadcast:test 1</code></li>
                            <li>Or send a message from another browser window</li>
                            <li>Watch events appear in the log above</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
