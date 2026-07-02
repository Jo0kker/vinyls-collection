<template>
    <Head>
        <title>Nouvelle discussion - Forum | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="description" content="Créez une nouvelle discussion sur le forum des collectionneurs de vinyles" />
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <ForumLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Nouvelle discussion
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        
                        <nav class="mb-6 text-sm">
                            <Link :href="route('forum.index')" 
                                  class="text-blue-600 dark:text-blue-400 hover:underline">
                                Forum
                            </Link>
                            <span class="mx-2 text-gray-500">/</span>
                            <Link v-if="category?.id" 
                                  :href="route('forum.category.show', { category_id: category.id })" 
                                  class="text-blue-600 dark:text-blue-400 hover:underline">
                                {{ category.title }}
                            </Link>
                            <span v-else class="text-gray-900 dark:text-gray-100">
                                {{ category?.title || 'Catégorie inconnue' }}
                            </span>
                            <span class="mx-2 text-gray-500">/</span>
                            <span class="text-gray-900 dark:text-gray-100">Nouvelle discussion</span>
                        </nav>

                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Titre de la discussion
                                </label>
                                <input v-model="form.title" 
                                       type="text" 
                                       id="title"
                                       class="w-full border dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                       :class="{ 'border-red-500': form.errors.title }"
                                       placeholder="Entrez le titre de votre discussion" 
                                       required>
                                <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            
                            <div>
                                <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Contenu
                                </label>
                                <textarea v-model="form.content" 
                                          id="content"
                                          rows="10" 
                                          class="w-full border dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          :class="{ 'border-red-500': form.errors.content }"
                                          placeholder="Écrivez votre message..." 
                                          required></textarea>
                                <div v-if="form.errors.content" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.content }}
                                </div>
                            </div>

                            <div v-if="form.errors.recaptcha_token" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.recaptcha_token }}
                            </div>

                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Ce formulaire est protégé par Google reCAPTCHA.
                            </p>

                            
                            <div class="flex items-center justify-end space-x-4">
                                <Link v-if="category?.id" 
                                      :href="route('forum.category.show', { category_id: category.id })" 
                                      class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                    Annuler
                                </Link>
                                <button v-else 
                                        type="button" 
                                        @click="history.back()"
                                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                    Annuler
                                </button>
                                <button type="submit" 
                                        :disabled="form.processing"
                                        class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-6 py-2 rounded-lg">
                                    <span v-if="form.processing">Création...</span>
                                    <span v-else>Créer la discussion</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </ForumLayout>
</template>

<script setup>
import ForumLayout from '@/Layouts/ForumLayout.vue';
import { useRecaptcha } from '@/composables/useRecaptcha';
import { Head, Link, useForm } from '@inertiajs/vue3';

const { executeRecaptcha } = useRecaptcha();

const props = defineProps({
    category: Object
});

const form = useForm({
    title: '',
    content: '',
    recaptcha_token: '',
});

async function submit() {
    form.recaptcha_token = (await executeRecaptcha('forum_thread_create')) ?? '';

    form.post(route('forum.thread.store', { category_id: props.category.id }), {
        onFinish: () => form.reset('recaptcha_token'),
    });
}
</script>