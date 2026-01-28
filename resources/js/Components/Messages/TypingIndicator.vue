<script setup>
import { computed } from 'vue';

const props = defineProps({
    typingUsers: {
        type: Array,
        default: () => [],
    },
});

const typingText = computed(() => {
    if (props.typingUsers.length === 0) return '';
    if (props.typingUsers.length === 1) {
        return `${props.typingUsers[0].userName} écrit...`;
    }
    if (props.typingUsers.length === 2) {
        return `${props.typingUsers[0].userName} et ${props.typingUsers[1].userName} écrivent...`;
    }
    return `${props.typingUsers.length} personnes écrivent...`;
});
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
    >
        <div
            v-if="typingUsers.length > 0"
            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-500 dark:text-gray-400"
        >
            <div class="flex space-x-1">
                <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.3s]"></span>
                <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.15s]"></span>
                <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400"></span>
            </div>
            <span>{{ typingText }}</span>
        </div>
    </Transition>
</template>
