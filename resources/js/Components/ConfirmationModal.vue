<template>
    <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
            <div class="flex min-h-screen items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

                <!-- Modal -->
                <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div v-if="show" class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div :class="[
                                    'mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10',
                                    type === 'danger' ? 'bg-red-100 dark:bg-red-900' : 'bg-yellow-100 dark:bg-yellow-900'
                                ]">
                                    <svg v-if="type === 'danger'" 
                                         class="h-6 w-6 text-red-600 dark:text-red-400" 
                                         fill="none" 
                                         viewBox="0 0 24 24" 
                                         stroke-width="1.5" 
                                         stroke="currentColor">
                                        <path stroke-linecap="round" 
                                              stroke-linejoin="round" 
                                              d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                    <svg v-else 
                                         class="h-6 w-6 text-yellow-600 dark:text-yellow-400" 
                                         fill="none" 
                                         viewBox="0 0 24 24" 
                                         stroke-width="1.5" 
                                         stroke="currentColor">
                                        <path stroke-linecap="round" 
                                              stroke-linejoin="round" 
                                              d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">
                                        {{ title }}
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400 whitespace-pre-line">
                                            {{ message }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button
                                type="button"
                                @click="$emit('confirm')"
                                :class="[
                                    'inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm sm:ml-3 sm:w-auto',
                                    type === 'danger' 
                                        ? 'bg-red-600 hover:bg-red-500 dark:bg-red-700 dark:hover:bg-red-600' 
                                        : 'bg-blue-600 hover:bg-blue-500 dark:bg-blue-700 dark:hover:bg-blue-600'
                                ]"
                            >
                                {{ confirmText }}
                            </button>
                            <button
                                type="button"
                                @click="$emit('close')"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto"
                            >
                                {{ cancelText }}
                            </button>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </transition>
</template>

<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: 'Confirmation'
    },
    message: {
        type: String,
        required: true
    },
    confirmText: {
        type: String,
        default: 'Confirmer'
    },
    cancelText: {
        type: String,
        default: 'Annuler'
    },
    type: {
        type: String,
        default: 'warning',
        validator: (value) => ['warning', 'danger'].includes(value)
    }
});

defineEmits(['close', 'confirm']);
</script>