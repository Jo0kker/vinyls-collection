<template>
    <Head>
        <title>{{ thread.title }} - Forum | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="description" :content="thread.first_post?.content_plain ? thread.first_post.content_plain.substring(0, 160) : 'Discussion sur ' + thread.title" />
        <meta name="keywords" :content="thread.title + ', forum vinyles, discussion'" />
        <meta property="og:title" :content="thread.title + ' - Forum | ' + ($page.props.app?.name || 'Vinyls Collection')" />
        <meta property="og:description" :content="thread.first_post?.content_plain ? thread.first_post.content_plain.substring(0, 160) : 'Discussion sur ' + thread.title" />
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="route('forum.thread.show', thread.id)" />
        <meta property="article:author" :content="thread.author?.name" />
        <meta property="article:published_time" :content="thread.created_at" />
        <meta property="article:modified_time" :content="thread.updated_at" />
        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" :content="thread.title" />
        <meta name="twitter:description" :content="thread.first_post?.content_plain ? thread.first_post.content_plain.substring(0, 160) : 'Discussion sur ' + thread.title" />
        <link rel="canonical" :href="route('forum.thread.show', thread.id)" />
    </Head>

    <ForumStructuredData
        type="thread"
        :data="thread"
    />

    <NotificationContainer />

    <!-- Boutons de navigation fixe -->
    <div class="fixed bottom-32 right-1 flex flex-col gap-2 z-40">
        <transition name="fade-slide">
            <button @click="scrollToTop"
                    v-show="showTopButton"
                    class="bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg p-2 shadow-md transition-all duration-200 hover:shadow-lg"
                    title="Retour en haut">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                </svg>
            </button>
        </transition>
        <button @click="scrollToBottom"
                class="bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg p-2 shadow-md transition-all duration-200 hover:shadow-lg"
                title="Aller en bas">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
            </svg>
        </button>
    </div>

    <ForumLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ thread.title }}
                    </h2>


                    <div class="flex flex-wrap gap-2 mt-2">
                        <span v-if="thread.deleted_at"
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                            Supprimé
                        </span>
                        <span v-if="thread.pinned"
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                            Épinglé
                        </span>
                        <span v-if="thread.locked"
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                            Verrouillé
                        </span>
                    </div>
                </div>


                <div v-if="canModerateThread" class="flex flex-col md:flex-row items-stretch md:items-center gap-2">

                    <div class="hidden md:flex items-center gap-2">

                        <template v-if="thread.deleted_at">
                            <button v-if="canRestoreThread"
                                    @click="restoreThread"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Restaurer
                            </button>
                            <button v-if="canForceDeleteThread"
                                    @click="forceDeleteThread"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Supprimer définitivement
                            </button>
                        </template>
                        <template v-else>
                            <button v-if="canDeleteThread"
                                    @click="deleteThread"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Supprimer
                            </button>
                        </template>


                        <div v-if="!thread.deleted_at" class="flex items-center gap-2">

                            <button v-if="thread.locked"
                                    @click="unlockThread"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                </svg>
                                Déverrouiller
                            </button>
                            <button v-else
                                    @click="lockThread"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Verrouiller
                            </button>


                            <button v-if="thread.pinned"
                                    @click="unpinThread"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                                Désépingler
                            </button>
                            <button v-else
                                    @click="pinThread"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                                Épingler
                            </button>


                            <button @click="showRenameModal = true"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Renommer
                            </button>


                            <button @click="showMoveModal = true"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                </svg>
                                Déplacer
                            </button>
                        </div>
                    </div>


                    <div class="md:hidden">

                        <button @click="showMobileMenu = !showMobileMenu"
                                class="w-full flex items-center justify-between px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                            <span>Actions de modération</span>
                            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showMobileMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>


                        <div v-if="showMobileMenu" class="mt-2 grid grid-cols-2 gap-2">

                            <template v-if="thread.deleted_at">
                                <button v-if="canRestoreThread"
                                        @click="restoreThread; showMobileMenu = false"
                                        class="flex items-center justify-center gap-2 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Restaurer</span>
                                </button>
                                <button v-if="canForceDeleteThread"
                                        @click="forceDeleteThread; showMobileMenu = false"
                                        class="flex items-center justify-center gap-2 px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Supprimer</span>
                                </button>
                            </template>
                            <template v-else>
                                <button v-if="canDeleteThread"
                                        @click="deleteThread; showMobileMenu = false"
                                        class="flex items-center justify-center gap-2 px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Supprimer</span>
                                </button>


                                <template v-if="!thread.deleted_at">

                                    <button v-if="thread.locked"
                                            @click="unlockThread; showMobileMenu = false"
                                            class="flex items-center justify-center gap-2 px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="hidden sm:inline">Déverrouiller</span>
                                    </button>
                                    <button v-else
                                            @click="lockThread; showMobileMenu = false"
                                            class="flex items-center justify-center gap-2 px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        <span class="hidden sm:inline">Verrouiller</span>
                                    </button>


                                    <button v-if="thread.pinned"
                                            @click="unpinThread; showMobileMenu = false"
                                            class="flex items-center justify-center gap-2 px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                        </svg>
                                        <span class="hidden sm:inline">Désépingler</span>
                                    </button>
                                    <button v-else
                                            @click="pinThread; showMobileMenu = false"
                                            class="flex items-center justify-center gap-2 px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                        </svg>
                                        <span class="hidden sm:inline">Épingler</span>
                                    </button>


                                    <button @click="showRenameModal = true; showMobileMenu = false"
                                            class="flex items-center justify-center gap-2 px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        <span class="hidden sm:inline">Renommer</span>
                                    </button>


                                    <button @click="showMoveModal = true; showMobileMenu = false"
                                            class="flex items-center justify-center gap-2 px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                        </svg>
                                        <span class="hidden sm:inline">Déplacer</span>
                                    </button>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">

                        <nav class="mb-6 text-sm">
                            <Link :href="route('forum.index')"
                                  class="text-blue-600 dark:text-blue-400 hover:underline">
                                Forum
                            </Link>
                            <span class="mx-2 text-gray-500">/</span>
                            <Link v-if="thread.category?.id"
                                  :href="route('forum.category.show', { category_id: thread.category.id })"
                                  class="text-blue-600 dark:text-blue-400 hover:underline">
                                {{ thread.category.title }}
                            </Link>
                            <span v-else class="text-gray-900 dark:text-gray-100">
                                {{ thread.category?.title || 'Catégorie inconnue' }}
                            </span>
                            <span class="mx-2 text-gray-500">/</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ thread.title }}</span>
                        </nav>


                        <form v-if="canManagePosts && posts.data.length > 1"
                              @submit.prevent="submitBulkAction"
                              class="mb-6">

                            <div v-if="selectablePosts.length > 0" class="text-right mb-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox"
                                           v-model="selectAll"
                                           @change="toggleAllPosts"
                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Sélectionner tout</span>
                                </label>
                            </div>
                        </form>


                        <div class="space-y-6">
                                <div v-for="(post, index) in posts.data" :key="post.id"
                                     :id="`post-${post.sequence}`"
                                     :class="[
                                         'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm',
                                         post.deleted_at ? 'opacity-50' : '',
                                         selectedPosts.includes(post.id) ? 'border-blue-500 dark:border-blue-400' : '',
                                         index === posts.data.length - 1 && posts.current_page === posts.last_page ? 'last-post-marker' : ''
                                     ]">

                                    <div class="hidden md:flex">

                                        <div class="flex-shrink-0 p-4 bg-gray-50 dark:bg-gray-700 rounded-l-lg">
                                            <div class="text-center w-32">

                                                <div class="mb-3">
                                                    <div v-if="!post.author.avatar"
                                                         class="w-16 h-16 rounded-full mx-auto bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl">
                                                        {{ post.author.name.substring(0, 2).toUpperCase() }}
                                                    </div>
                                                    <img v-else
                                                         :src="post.author.avatar"
                                                         :alt="post.author.name"
                                                         class="w-16 h-16 rounded-full mx-auto border-2 border-gray-200 dark:border-gray-600">
                                                </div>


                                                <div class="font-semibold text-gray-900 dark:text-white text-sm mb-1">
                                                    <Link :href="`/collectors/${post.author.id}`"
                                                          class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                                        {{ post.author.name }}
                                                    </Link>
                                                </div>


                                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                    <span class="px-2 py-1 rounded-full text-white font-medium"
                                                          :style="{ backgroundColor: getUserRole(post.author).color }">
                                                        {{ getUserRole(post.author).name }}
                                                    </span>
                                                </div>


                                                <div class="text-xs text-gray-600 dark:text-gray-300 mb-2">
                                                    <div class="flex items-center justify-center gap-1">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                                        </svg>
                                                        <span>{{ post.author.vinyl_count || 0 }} vinyles</span>
                                                    </div>
                                                </div>


                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ formatDate(post.created_at) }}
                                                </div>
                                            </div>
                                        </div>


                                        <div class="flex-1 p-4 flex flex-col min-h-48">

                                            <div class="flex justify-between items-start mb-4">
                                                <div class="flex items-center gap-2">
                                                    <a :href="route('forum.thread.show', { thread_id: thread.id }) + '#post-' + post.sequence"
                                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
                                                        #{{ post.sequence || 'N/A' }}
                                                    </a>
                                                    <span v-if="post.updated_at !== post.created_at"
                                                          class="text-xs text-gray-500 dark:text-gray-400">
                                                        (modifié {{ formatDate(post.updated_at) }})
                                                    </span>
                                                </div>


                                                <div v-if="canManagePosts && post.sequence !== 1 && canDelete(post)" class="flex items-center gap-2">
                                                    <input type="checkbox"
                                                           :value="post.id"
                                                           v-model="selectedPosts"
                                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                                </div>
                                            </div>


                                            <div class="max-w-none flex-grow text-gray-900 dark:text-gray-100">
                                                <div v-if="editingPost?.id === post.id" class="space-y-4">
                                                    <TinyMCEEditor
                                                        v-model="editForm.content"
                                                        :height="200"
                                                        :disabled="editForm.processing"
                                                    />
                                                    <div class="flex gap-2">
                                                        <button @click="submitEdit"
                                                                :disabled="editForm.processing"
                                                                class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-4 py-2 rounded text-sm">
                                                            Sauvegarder
                                                        </button>
                                                        <button @click="cancelEdit"
                                                                :disabled="editForm.processing"
                                                                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm">
                                                            Annuler
                                                        </button>
                                                    </div>
                                                </div>
                                                <div v-else-if="post.deleted_at" class="text-gray-500">
                                                    <div v-html="getProcessedContent(post)"></div>
                                                    <div class="mt-2">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                            Supprimé
                                                        </span>
                                                    </div>
                                                </div>
                                                <div v-else class="prose dark:prose-invert max-w-none" v-html="getProcessedContent(post)"></div>
                                            </div>


                                            <div class="flex items-center gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 mt-auto">
                                                <template v-if="!post.deleted_at">
                                                    <a :href="route('forum.thread.show', { thread_id: thread.id }) + '#post-' + post.sequence"
                                                       class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.102m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                                        </svg>
                                                        Permalien
                                                    </a>

                                                    <button @click="replyTo(post)"
                                                            class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                        </svg>
                                                        Répondre
                                                    </button>

                                                    <button v-if="canEdit(post)"
                                                            @click="editPost(post)"
                                                            class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                        Modifier
                                                    </button>

                                                    <button v-if="canDelete(post)"
                                                            @click="deletePost(post)"
                                                            class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                        Supprimer
                                                    </button>
                                                </template>

                                                <template v-else>
                                                    <button v-if="canRestore(post)"
                                                            @click="restorePost(post)"
                                                            class="text-sm text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                        Restaurer
                                                    </button>

                                                    <button v-if="canForceDelete(post)"
                                                            @click="forceDeletePost(post)"
                                                            class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                        Supprimer définitivement
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="md:hidden p-4">

                                        <div class="flex items-center gap-3 mb-3">
                                            <div v-if="!post.author.avatar"
                                                 class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm">
                                                {{ post.author.name.substring(0, 2).toUpperCase() }}
                                            </div>
                                            <img v-else
                                                 :src="post.author.avatar"
                                                 :alt="post.author.name"
                                                 class="w-10 h-10 rounded-full border-2 border-gray-200 dark:border-gray-600">

                                            <div class="flex-1">
                                                <div class="flex items-center justify-between">
                                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">
                                                        <Link :href="`/collectors/${post.author.id}`"
                                                              class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                                            {{ post.author.name }}
                                                        </Link>
                                                    </div>
                                                    <a v-if="post.sequence"
                                                       :href="route('forum.thread.show', { thread_id: thread.id }) + '#post-' + post.sequence"
                                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs">
                                                        #{{ post.sequence }}
                                                    </a>
                                                </div>
                                                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="px-2 py-0.5 rounded-full text-white font-medium"
                                                          :style="{ backgroundColor: getUserRole(post.author).color }">
                                                        {{ getUserRole(post.author).name }}
                                                    </span>
                                                    <span>{{ post.author.vinyl_count || 0 }} vinyles</span>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="mb-4">
                                            <div v-if="post.deleted_at" class="text-gray-500">
                                                <div class="prose dark:prose-invert prose-sm max-w-none" v-html="getProcessedContent(post)"></div>
                                                <div class="mt-2">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                        Supprimé
                                                    </span>
                                                </div>
                                            </div>
                                            <div v-else class="prose dark:prose-invert prose-sm max-w-none" v-html="getProcessedContent(post)"></div>
                                        </div>


                                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-600 pt-3">
                                            <div>
                                                <span>{{ formatDate(post.created_at) }}</span>
                                                <span v-if="post.updated_at !== post.created_at" class="ml-2">
                                                    (modifié {{ formatDate(post.updated_at) }})
                                                </span>
                                            </div>


                                            <div class="flex items-center gap-3">

                                                <input v-if="canManagePosts && post.sequence !== 1 && canDelete(post)"
                                                       type="checkbox"
                                                       :value="post.id"
                                                       v-model="selectedPosts"
                                                       class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600">


                                                <div class="relative">
                                                    <button @click.stop="togglePostMenu(post.id)"
                                                            class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                                        </svg>
                                                    </button>


                                                    <div v-if="openPostMenus[post.id]"
                                                         @click.stop
                                                         class="absolute right-0 bottom-full mb-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg z-10 min-w-32">
                                                        <div class="py-1">
                                                            <template v-if="!post.deleted_at">
                                                                <button @click="replyTo(post); closePostMenu(post.id)"
                                                                        class="block w-full text-left px-3 py-2 text-xs text-blue-600 dark:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                                    <svg class="w-3 h-3 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                                    </svg>
                                                                    Répondre
                                                                </button>

                                                                <button v-if="canEdit(post)"
                                                                        @click="editPost(post); closePostMenu(post.id)"
                                                                        class="block w-full text-left px-3 py-2 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                                    <svg class="w-3 h-3 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                    </svg>
                                                                    Modifier
                                                                </button>

                                                                <a :href="route('forum.thread.show', { thread_id: thread.id }) + '#post-' + post.sequence"
                                                                   @click="closePostMenu(post.id)"
                                                                   class="block w-full text-left px-3 py-2 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                                    <svg class="w-3 h-3 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.102m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                                                    </svg>
                                                                    Permalien
                                                                </a>

                                                                <button v-if="canDelete(post)"
                                                                        @click="deletePost(post); closePostMenu(post.id)"
                                                                        class="block w-full text-left px-3 py-2 text-xs text-red-600 dark:text-red-400 hover:bg-gray-50 dark:hover:bg-gray-600 border-t border-gray-200 dark:border-gray-600">
                                                                    <svg class="w-3 h-3 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                    </svg>
                                                                    Supprimer
                                                                </button>
                                                            </template>

                                                            <template v-else-if="canViewDeletedPosts">
                                                                <button v-if="canRestore(post)"
                                                                        @click="restorePost(post); closePostMenu(post.id)"
                                                                        class="block w-full text-left px-3 py-2 text-xs text-green-600 dark:text-green-400 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                                    <svg class="w-3 h-3 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                                    </svg>
                                                                    Restaurer
                                                                </button>

                                                                <button v-if="canForceDelete(post)"
                                                                        @click="forceDeletePost(post); closePostMenu(post.id)"
                                                                        class="block w-full text-left px-3 py-2 text-xs text-red-600 dark:text-red-400 hover:bg-gray-50 dark:hover:bg-gray-600 border-t border-gray-200 dark:border-gray-600">
                                                                    <svg class="w-3 h-3 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                    </svg>
                                                                    Supprimer définitivement
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div v-if="canManagePosts && selectedPosts.length > 0"
                             class="fixed bottom-0 right-0 m-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg"
                             style="z-index: 1000; min-width: 300px;">
                            <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-3 text-center">
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    Actions sur la sélection ({{ selectedPosts.length }} posts)
                                </span>
                            </div>
                            <div class="p-4">
                                <div class="mb-3">
                                    <label for="bulk-action" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Action
                                    </label>
                                    <select v-model="bulkAction"
                                            id="bulk-action"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                        <option value="delete">Supprimer</option>
                                        <option value="restore">Restaurer</option>
                                    </select>
                                </div>

                                <div v-if="bulkAction === 'delete'" class="mb-3">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox"
                                               v-model="forceDelete"
                                               class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 dark:focus:ring-red-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Suppression définitive</span>
                                    </label>
                                </div>

                                <div class="text-right">
                                    <button @click="submitBulkAction"
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium">
                                        Appliquer
                                    </button>
                                </div>
                            </div>
                        </div>


                        <div v-if="posts && posts.links && posts.links.length > 3" class="mt-6">
                            <nav class="flex items-center justify-center">

                                <div class="flex sm:hidden items-center gap-2">
                                    <Link v-if="posts.prev_page_url"
                                          :href="posts.prev_page_url"
                                          class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                        ← Précédent
                                    </Link>
                                    <span class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                        Page {{ posts.current_page }} / {{ posts.last_page }}
                                    </span>
                                    <Link v-if="posts.next_page_url"
                                          :href="posts.next_page_url"
                                          class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                        Suivant →
                                    </Link>
                                </div>


                                <div class="hidden sm:flex rounded-md shadow-sm -space-x-px">
                                    <Link v-if="posts.prev_page_url"
                                          :href="posts.prev_page_url"
                                          class="relative inline-flex items-center px-3 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                        ←
                                    </Link>

                                    <template v-for="(link, index) in posts.links" :key="index">
                                        <Link v-if="link.url && !link.label.includes('Previous') && !link.label.includes('Next')"
                                              :href="link.url"
                                              :class="[
                                                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                  link.active
                                                      ? 'bg-blue-600 border-blue-600 text-white'
                                                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'
                                              ]"
                                              v-html="link.label">
                                        </Link>
                                        <span v-else-if="!link.url && !link.label.includes('Previous') && !link.label.includes('Next')"
                                              class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-50 text-sm font-medium text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400"
                                              v-html="link.label">
                                        </span>
                                    </template>

                                    <Link v-if="posts.next_page_url"
                                          :href="posts.next_page_url"
                                          class="relative inline-flex items-center px-3 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                        →
                                    </Link>
                                </div>
                            </nav>
                        </div>


                        <div v-if="canReply" id="reply-form" class="mt-8 border-t dark:border-gray-700 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    Répondre
                                </h3>
                                <button @click="showReplyModal = true"
                                        type="button"
                                        class="flex items-center gap-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                                    </svg>
                                    Mode étendu
                                </button>
                            </div>
                            <form @submit.prevent="submitReply" class="space-y-4">
                                <TinyMCEEditor
                                    v-model="replyForm.content"
                                    :height="isMobile ? 150 : 200"
                                    :disabled="replyForm.processing"
                                />
                                <button type="submit"
                                        :disabled="replyForm.processing"
                                        class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-6 py-3 md:py-2 rounded-lg font-medium">
                                    Répondre
                                </button>
                            </form>
                        </div>


                        <div v-else-if="$page.props.auth.user && !canReply" class="mt-8 border-t dark:border-gray-700 pt-6">
                            <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-yellow-800 dark:text-yellow-200">
                                        <span v-if="thread.locked">Ce thread est verrouillé. Seuls les modérateurs et administrateurs peuvent répondre.</span>
                                        <span v-else-if="thread.deleted_at">Ce thread a été supprimé. Aucune réponse n'est possible.</span>
                                        <span v-else>Vous ne pouvez pas répondre à ce thread.</span>
                                    </span>
                                </div>
                            </div>
                        </div>


                        <div v-else-if="!$page.props.auth.user" class="mt-8 border-t dark:border-gray-700 pt-6">
                            <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-blue-800 dark:text-blue-200">
                                        <Link :href="route('login')" class="underline hover:text-blue-600 dark:hover:text-blue-300">
                                            Connectez-vous
                                        </Link>
                                        pour répondre à ce thread.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        <div v-if="showRenameModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showRenameModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800" @click.stop>
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Renommer le thread
                    </h3>
                    <form @submit.prevent="submitRename">
                        <div class="mb-4">
                            <label for="rename-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nouveau titre
                            </label>
                            <input v-model="renameForm.title"
                                   id="rename-title"
                                   type="text"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button"
                                    @click="showRenameModal = false"
                                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md text-sm">
                                Annuler
                            </button>
                            <button type="submit"
                                    :disabled="renameForm.processing"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-md text-sm">
                                Renommer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <div v-if="showMoveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showMoveModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800" @click.stop>
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Déplacer le thread
                    </h3>
                    <form @submit.prevent="submitMove">
                        <div class="mb-4">
                            <label for="move-category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Destination
                            </label>
                            <select v-model="moveForm.category_id"
                                    id="move-category"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Sélectionner une catégorie</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.title }}
                                </option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button"
                                    @click="showMoveModal = false"
                                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md text-sm">
                                Annuler
                            </button>
                            <button type="submit"
                                    :disabled="moveForm.processing"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-md text-sm">
                                Déplacer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation pour la suppression -->
        <ConfirmationModal
            :show="showDeleteModal"
            :title="deleteModalConfig.title"
            :message="deleteModalConfig.message"
            :type="deleteModalConfig.type"
            confirm-text="Supprimer"
            cancel-text="Annuler"
            @confirm="confirmDelete"
            @close="showDeleteModal = false"
        />

        <ReplyModal
            :show="showReplyModal"
            v-model:content="replyForm.content"
            :disabled="replyForm.processing"
            @close="showReplyModal = false"
            @submit="submitReply"
        />
    </ForumLayout>
</template>

<script setup>
import ForumLayout from '@/Layouts/ForumLayout.vue';
import ForumStructuredData from '@/Components/Forum/ForumStructuredData.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import TinyMCEEditor from '@/Components/TinyMCEEditor.vue';
import NotificationContainer from '@/Components/NotificationContainer.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import ReplyModal from '@/Components/Forum/ReplyModal.vue';
import { useNotifications } from '@/composables/useNotifications.js';
import { useForumContent } from '@/composables/useForumContent.js';

const $page = usePage();
const { warning } = useNotifications();
const { processForumContent } = useForumContent();

const props = defineProps({
    thread: Object,
    posts: Object,
    categories: Array
});


const replyForm = useForm({
    content: ''
});

// Reactive data for bulk actions
const selectedPosts = ref([]);
const selectAll = ref(false);
const bulkAction = ref('delete');
const forceDelete = ref(false);

// Responsive detection
const screenWidth = ref(768);

onMounted(() => {
    if (typeof window !== 'undefined') {
        screenWidth.value = window.innerWidth;
        const handleResize = () => {
            screenWidth.value = window.innerWidth;
        };
        window.addEventListener('resize', handleResize);

        // Fermer tous les menus quand on clique ailleurs
        const closeMenus = () => {
            Object.keys(openPostMenus.value).forEach(id => {
                openPostMenus.value[id] = false;
            });
        };
        document.addEventListener('click', closeMenus);

        // Gestion du scroll pour les boutons de navigation
        const handleScroll = () => {
            showTopButton.value = window.pageYOffset > 200;
        };
        window.addEventListener('scroll', handleScroll);

        // Vérifier si on doit scroll vers le dernier post
        const urlParams = new URLSearchParams(window.location.search);
        const shouldScrollToLast = window.location.hash === '#last-post' ||
                                  urlParams.get('scroll') === 'last' ||
                                  $page.props._inertiaFormData?.scroll === 'last';

        if (shouldScrollToLast) {
            // Attendre que le DOM soit complètement chargé
            const scrollToBottom = () => {
                window.scrollTo({
                    top: document.documentElement.scrollHeight,
                    behavior: 'smooth'
                });
            };

            // Essayer plusieurs fois pour être sûr que le contenu est chargé
            setTimeout(scrollToBottom, 100);
            setTimeout(scrollToBottom, 500);
            setTimeout(scrollToBottom, 1000);
        }
        // Scroll vers un post spécifique si l'ancre est présente
        else if (window.location.hash && window.location.hash !== '#last-post') {
            const targetId = window.location.hash.substring(1);
            const element = document.getElementById(targetId);
            if (element) {
                setTimeout(() => {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }
        }

        return () => {
            window.removeEventListener('resize', handleResize);
            document.removeEventListener('click', closeMenus);
        };
    }
});

// Thread moderation modals
const showRenameModal = ref(false);
const showMoveModal = ref(false);
const showMobileMenu = ref(false);
const showReplyModal = ref(false);

// Post menus state
const openPostMenus = ref({});
const renameForm = useForm({
    title: props.thread.title
});
const moveForm = useForm({
    category_id: ''
});

// Scroll buttons state
const showTopButton = ref(false);

// Computed properties
const isMobile = computed(() => screenWidth.value < 768);

const selectablePosts = computed(() => {
    return props.posts.data.filter(post => post.sequence !== 1 && canDelete(post));
});

const canManagePosts = computed(() => {
    const user = $page.props.auth.user;
    return user && user.roles?.some(role => ['admin', 'moderator'].includes(role.name));
});

const canViewDeletedPosts = computed(() => {
    const user = $page.props.auth.user;
    return user && user.roles?.some(role => ['admin', 'moderator'].includes(role.name));
});

const canModerateThread = computed(() => {
    const user = $page.props.auth.user;
    return user && user.roles?.some(role => ['admin', 'moderator'].includes(role.name));
});

const canDeleteThread = computed(() => {
    const user = $page.props.auth.user;
    if (!user) return false;

    // Author can delete their own thread or moderators/admins can delete any thread
    return (props.thread.author.id === user.id) ||
           user.roles?.some(role => ['admin', 'moderator'].includes(role.name));
});

const canRestoreThread = computed(() => {
    const user = $page.props.auth.user;
    return user && user.roles?.some(role => ['admin', 'moderator'].includes(role.name));
});

const canForceDeleteThread = computed(() => {
    const user = $page.props.auth.user;
    return user && user.roles?.some(role => role.name === 'admin');
});

const canReply = computed(() => {
    const user = $page.props.auth.user;
    if (!user) return false;

    // Thread supprimé = pas de réponse
    if (props.thread.deleted_at) return false;

    // Thread verrouillé = pas de réponse (sauf pour les mods/admins)
    if (props.thread.locked) {
        return user.roles?.some(role => ['admin', 'moderator'].includes(role.name));
    }

    // Sinon, tout utilisateur connecté peut répondre
    return true;
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function canEdit(post) {
    const user = $page.props.auth.user;
    if (!user) return false;

    // L'auteur peut toujours modifier son post
    if (post.author.id === user.id) return true;

    // Les modérateurs et admins peuvent modifier tous les posts
    return user.roles?.some(role => ['admin', 'moderator'].includes(role.name));
}

function canDelete(post) {
    const user = $page.props.auth.user;
    if (!user) return false;

    // L'auteur peut supprimer son post
    if (post.author.id === user.id) return true;

    // Les modérateurs et admins peuvent supprimer tous les posts
    return user.roles?.some(role => ['admin', 'moderator'].includes(role.name));
}

function canRestore(post) {
    const user = $page.props.auth.user;
    if (!user) return false;

    // Seuls les modérateurs et admins peuvent restaurer
    return user.roles?.some(role => ['admin', 'moderator'].includes(role.name));
}

function canForceDelete(post) {
    const user = $page.props.auth.user;
    if (!user) return false;

    // Seuls les admins peuvent supprimer définitivement
    return user.roles?.some(role => role.name === 'admin');
}

function getUserRole(author) {
    if (author.roles && author.roles.length > 0) {
        const role = author.roles[0];
        return {
            name: role.name.charAt(0).toUpperCase() + role.name.slice(1),
            color: role.color || '#3b82f6'
        };
    }
    return { name: 'Membre', color: '#3b82f6' };
}

// Process post content for legacy messages
function getProcessedContent(post) {
    return processForumContent(post.content, post.created_at);
}

function toggleAllPosts() {
    if (selectAll.value) {
        selectedPosts.value = selectablePosts.value.map(post => post.id);
    } else {
        selectedPosts.value = [];
    }
}

function scrollToReply() {
    const replyElement = document.querySelector('#reply-form');
    if (replyElement) {
        replyElement.scrollIntoView({ behavior: 'smooth' });
    }
}

function replyTo(post) {
    const author = post.author?.name || 'Utilisateur supprimé';
    const processedContent = processForumContent(post.content, post.created_at);

    const quote = `<blockquote class="border-l-4 border-blue-500 pl-4 py-2 my-4 bg-gray-50 dark:bg-gray-800 rounded-r-lg">
        <div class="text-sm text-blue-600 dark:text-blue-400 font-semibold mb-2">Citation de ${author} :</div>
        <div class="text-gray-700 dark:text-gray-300">${processedContent}</div>
    </blockquote><br>`;

    replyForm.content = quote;
    scrollToReply();
}

function submitReply() {
    replyForm.post(route('forum.post.store', { thread_id: props.thread.id }), {
        onSuccess: () => {
            // Arrêter toutes les vidéos dans l'éditeur avant de reset
            const editorIframes = document.querySelectorAll('#reply-form iframe');
            editorIframes.forEach(iframe => {
                iframe.src = 'about:blank';
            });

            replyForm.reset();
            showReplyModal.value = false;
        }
    });
}

function submitBulkAction() {
    if (selectedPosts.value.length === 0) {
        warning('Aucun post sélectionné', 'Veuillez sélectionner au moins un post.');
        return;
    }

    const action = bulkAction.value;
    const confirmMessage = action === 'delete'
        ? (forceDelete.value ? 'Êtes-vous sûr de vouloir supprimer définitivement ces messages ?' : 'Êtes-vous sûr de vouloir supprimer ces messages ?')
        : 'Êtes-vous sûr de vouloir restaurer ces messages ?';

    if (confirm(confirmMessage)) {
        const formData = {
            posts: selectedPosts.value,
            action: action
        };

        if (action === 'delete' && forceDelete.value) {
            formData.force = true;
        }

        // Send the bulk action request
        router.post(route('forum.post.bulk'), formData, {
            onSuccess: () => {
                selectedPosts.value = [];
                selectAll.value = false;
            }
        });
    }
}

const editingPost = ref(null);
const editForm = useForm({
    content: ''
});

function editPost(post) {
    editingPost.value = post;
    editForm.content = post.content;
}

function cancelEdit() {
    editingPost.value = null;
    editForm.reset();
}

function submitEdit() {
    editForm.put(route('forum.post.update', { post_id: editingPost.value.id }), {
        onSuccess: () => {
            editingPost.value = null;
            editForm.reset();
        }
    });
}

// Modal de confirmation
const showDeleteModal = ref(false);
const deleteModalConfig = ref({
    title: '',
    message: '',
    type: 'warning',
    postToDelete: null
});

function deletePost(post) {
    // Vérifier si c'est le premier post (qui supprimera tout le thread)
    const isFirstPost = post.sequence === 1;

    if (isFirstPost) {
        deleteModalConfig.value = {
            title: 'Supprimer la discussion complète',
            message: 'Vous êtes sur le point de supprimer le message principal de cette discussion.\n\nCela supprimera TOUTE LA DISCUSSION, y compris toutes les réponses.\n\nCette action est irréversible.',
            type: 'danger',
            postToDelete: post
        };
    } else {
        deleteModalConfig.value = {
            title: 'Supprimer le message',
            message: 'Êtes-vous sûr de vouloir supprimer ce message ?',
            type: 'warning',
            postToDelete: post
        };
    }

    showDeleteModal.value = true;
}

function confirmDelete() {
    if (deleteModalConfig.value.postToDelete) {
        router.delete(route('forum.post.destroy', { post_id: deleteModalConfig.value.postToDelete.id }));
    }
    showDeleteModal.value = false;
}

function restorePost(post) {
    if (confirm('Êtes-vous sûr de vouloir restaurer ce message ?')) {
        router.post(route('forum.post.restore', { post_id: post.id }));
    }
}

function forceDeletePost(post) {
    if (confirm('Êtes-vous sûr de vouloir supprimer définitivement ce message ? Cette action est irréversible.')) {
        router.delete(route('forum.post.force-destroy', { post_id: post.id }));
    }
}

// Navigation functions
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function scrollToBottom() {
    window.scrollTo({ top: document.documentElement.scrollHeight, behavior: 'smooth' });
}

// Thread moderation functions
function lockThread() {
    if (confirm('Êtes-vous sûr de vouloir verrouiller ce thread ?')) {
        router.post(route('forum.thread.lock', { thread_id: props.thread.id }));
    }
}

function unlockThread() {
    if (confirm('Êtes-vous sûr de vouloir déverrouiller ce thread ?')) {
        router.post(route('forum.thread.unlock', { thread_id: props.thread.id }));
    }
}

function pinThread() {
    if (confirm('Êtes-vous sûr de vouloir épingler ce thread ?')) {
        router.post(route('forum.thread.pin', { thread_id: props.thread.id }));
    }
}

function unpinThread() {
    if (confirm('Êtes-vous sûr de vouloir désépingler ce thread ?')) {
        router.post(route('forum.thread.unpin', { thread_id: props.thread.id }));
    }
}

function deleteThread() {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce thread ?')) {
        router.delete(route('forum.thread.destroy', { thread_id: props.thread.id }));
    }
}

function restoreThread() {
    if (confirm('Êtes-vous sûr de vouloir restaurer ce thread ?')) {
        router.post(route('forum.thread.restore', { thread_id: props.thread.id }));
    }
}

function forceDeleteThread() {
    if (confirm('Êtes-vous sûr de vouloir supprimer définitivement ce thread ? Cette action est irréversible.')) {
        router.delete(route('forum.thread.force-destroy', { thread_id: props.thread.id }));
    }
}

function submitRename() {
    renameForm.post(route('forum.thread.rename', { thread_id: props.thread.id }), {
        onSuccess: () => {
            showRenameModal.value = false;
        }
    });
}

function submitMove() {
    moveForm.post(route('forum.thread.move', { thread_id: props.thread.id }), {
        onSuccess: () => {
            showMoveModal.value = false;
        }
    });
}

// Post menu functions
function togglePostMenu(postId) {
    // Fermer tous les autres menus
    Object.keys(openPostMenus.value).forEach(id => {
        if (id !== postId.toString()) {
            openPostMenus.value[id] = false;
        }
    });

    // Toggle le menu actuel
    openPostMenus.value[postId] = !openPostMenus.value[postId];
}

function closePostMenu(postId) {
    openPostMenus.value[postId] = false;
}

</script>

<style>
/* Force les styles de liste pour le contenu du forum */
.prose ul {
    list-style-type: disc !important;
    padding-left: 1.5rem !important;
    margin: 1rem 0 !important;
}

.prose li {
    margin: 0.5rem 0 !important;
    display: list-item !important;
}

.prose ol {
    list-style-type: decimal !important;
    padding-left: 1.5rem !important;
    margin: 1rem 0 !important;
}

/* Styles pour les embeds YouTube */
.youtube-embed {
    max-width: 100%;
    margin: 1rem 0;
}

.youtube-embed iframe {
    border-radius: 0.5rem;
}

/* Responsive YouTube embeds */
@media (max-width: 768px) {
    .youtube-embed {
        margin: 0.75rem 0;
    }
    
    .youtube-embed .relative {
        padding-bottom: 56.25% !important;
    }
}

/* Animation pour les boutons */
.fade-slide-enter-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from {
    transform: translateY(20px);
    opacity: 0;
}
</style>

