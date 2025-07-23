<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AvatarUploadModal from '@/Components/AvatarUploadModal.vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
    name: user.value.name,
    email: user.value.email,
});

// État du modal d'avatar
const showAvatarModal = ref(false);

// Fonctions
function openAvatarModal() {
    showAvatarModal.value = true;
}

function closeAvatarModal() {
    showAvatarModal.value = false;
}

function onAvatarUploaded() {
    // Le reload est déjà fait dans le modal AvatarUploadModal
    // Pas besoin de double reload
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Update your account's profile information and email address.
            </p>
        </header>

        <!-- Section Avatar -->
        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                Avatar
            </h3>
            <div class="flex items-center space-x-6">
                <!-- Avatar actuel -->
                <div class="flex-shrink-0">
                    <div class="w-20 h-20 rounded-full overflow-hidden bg-gradient-to-br from-purple-500 to-blue-600 flex items-center justify-center">
                        <img v-if="user.avatar" 
                             :src="user.avatar" 
                             :alt="user.name"
                             class="w-full h-full object-cover" />
                        <span v-else class="text-white font-bold text-xl">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                </div>
                
                <!-- Boutons de gestion -->
                <div class="flex flex-col gap-2">
                    <button 
                        type="button"
                        @click="openAvatarModal"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        {{ user.avatar ? 'Changer l\'avatar' : 'Ajouter un avatar' }}
                    </button>
                    
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        JPG, PNG ou JPEG. Max 2MB.
                    </p>
                </div>
            </div>
        </div>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800 dark:text-gray-200">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600 dark:text-green-400"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>

        <!-- Modal d'upload d'avatar -->
        <AvatarUploadModal 
            :show="showAvatarModal"
            @close="closeAvatarModal"
            @uploaded="onAvatarUploaded"
        />
    </section>
</template>
