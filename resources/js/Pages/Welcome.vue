<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    latestVinyls: {
        type: Array,
        default: () => [],
    },
    latestThreads: {
        type: Array,
        default: () => [],
    },
    latestPosts: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const isLoading = ref(true);
const activeTab = ref('messages'); // 'messages' or 'threads'

// Format relative time
function formatRelativeTime(date) {
    const now = new Date();
    const then = new Date(date);
    const diffInSeconds = Math.floor((now - then) / 1000);

    if (diffInSeconds < 60) return 'il y a quelques secondes';
    if (diffInSeconds < 3600) return `il y a ${Math.floor(diffInSeconds / 60)} min`;
    if (diffInSeconds < 86400) return `il y a ${Math.floor(diffInSeconds / 3600)}h`;
    if (diffInSeconds < 604800) return `il y a ${Math.floor(diffInSeconds / 86400)}j`;
    return then.toLocaleDateString('fr-FR');
}

// Format number with K/M suffix
function formatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
    return num?.toString() || '0';
}

onMounted(() => {
    isLoading.value = false;

    // Créer les données structurées avec l'URL actuelle
    const currentUrl = window.location.origin;
    const structuredData = {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Vinyls Collection",
        "description": "Plateforme communautaire pour gérer, partager et discuter de votre collection de vinyles",
        "url": currentUrl,
        "potentialAction": {
            "@type": "SearchAction",
            "target": `${currentUrl}/search?q={search_term_string}`,
            "query-input": "required name=search_term_string"
        },
        "sameAs": [],
        "publisher": {
            "@type": "Organization",
            "name": "Vinyls Collection"
        }
    };

    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.textContent = JSON.stringify(structuredData);
    document.head.appendChild(script);
});
</script>

<template>
    <Head>
        <title>Vinyls Collection - Gérez votre collection de vinyles</title>
        <meta name="description" content="Plateforme communautaire pour gérer, partager et discuter de votre collection de vinyles. Forum actif et outils de gestion complets." />
        <meta name="keywords" content="collection vinyles, forum vinyles, gestion collection, vinyles communauté, discogs, collectionneurs" />
        <meta property="og:title" content="Vinyls Collection - Gérez votre collection de vinyles" />
        <meta property="og:description" content="Rejoignez la communauté des collectionneurs de vinyles. Gérez votre collection, échangez conseils et découvrez de nouveaux albums." />
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="$page.props.app?.url || ''" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Vinyls Collection - Communauté de collectionneurs" />
        <meta name="twitter:description" content="La plateforme de référence pour les collectionneurs de vinyles" />
        <link rel="canonical" :href="$page.props.app?.url || ''" />
    </Head>

    <div v-if="isLoading" class="fixed inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900">
        <div class="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-blue-500"></div>
    </div>

    <AuthenticatedLayout v-else>
        <!-- Bannière info -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 border-b border-green-600">
            <div class="max-w-7xl mx-auto py-3 px-3 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between flex-wrap">
                    <div class="w-0 flex-1 flex items-center">
                        <span class="flex p-2 rounded-lg bg-green-800">
                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <p class="ml-3 font-medium text-white truncate">
                            <span class="md:hidden">
                                Nouveau site ! Récupérez votre ancien compte.
                            </span>
                            <span class="hidden md:inline">
                                Bienvenue sur le nouveau site Vinyls Collection ! Pour récupérer votre ancien compte,
                                <Link href="/forgot-password" class="underline font-semibold hover:text-green-100">
                                    faites une demande de réinitialisation de mot de passe
                                </Link>.
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <!-- Hero Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-blue-800 to-blue-900">
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.4&quot;%3E%3Ccircle cx=&quot;30&quot; cy=&quot;30&quot; r=&quot;20&quot;/%3E%3Ccircle cx=&quot;30&quot; cy=&quot;30&quot; r=&quot;10&quot;/%3E%3Ccircle cx=&quot;30&quot; cy=&quot;30&quot; r=&quot;3&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E'); background-size: 60px 60px;"></div>
                </div>
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
                    <div class="text-center">
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight">
                            Votre passion pour les <span class="text-blue-200">vinyles</span>
                        </h1>
                        <p class="mt-4 max-w-2xl mx-auto text-lg sm:text-xl text-blue-100">
                            Gérez votre collection, échangez avec d'autres passionnés et découvrez de nouveaux trésors musicaux.
                        </p>
                        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                            <Link href="/explore" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-blue-700 bg-white hover:bg-blue-50 shadow-lg transition">
                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Explorer les vinyles
                            </Link>
                            <Link href="/forum" class="inline-flex items-center justify-center px-6 py-3 border-2 border-white text-base font-medium rounded-lg text-white hover:bg-white/10 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                </svg>
                                Rejoindre le forum
                            </Link>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="mt-12 grid grid-cols-2 lg:grid-cols-4 gap-4 max-w-4xl mx-auto">
                        <div class="bg-white/10 backdrop-blur rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-white">{{ formatNumber(stats.totalVinyls) }}</div>
                            <div class="text-sm text-blue-200">Vinyles</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-white">{{ formatNumber(stats.totalUsers) }}</div>
                            <div class="text-sm text-blue-200">Collectionneurs</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-white">{{ formatNumber(stats.totalCollections) }}</div>
                            <div class="text-sm text-blue-200">Collections</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-white">{{ formatNumber(stats.totalThreads) }}</div>
                            <div class="text-sm text-blue-200">Discussions</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid lg:grid-cols-3 gap-8">

                    <!-- Derniers vinyles ajoutés -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Derniers vinyles ajoutés
                            </h2>
                            <Link href="/explore" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                Voir tout &rarr;
                            </Link>
                        </div>

                        <div v-if="latestVinyls.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            <Link
                                v-for="vinyl in latestVinyls"
                                :key="vinyl.id"
                                :href="route('vinyl.show', vinyl.id)"
                                class="group bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition overflow-hidden"
                            >
                                <div class="aspect-square bg-gray-200 dark:bg-gray-700 relative overflow-hidden">
                                    <img
                                        v-if="vinyl.cover_image"
                                        :src="vinyl.cover_image"
                                        :alt="vinyl.title"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                        loading="lazy"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="p-3">
                                    <h3 class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ vinyl.title }}</h3>
                                    <p class="text-gray-500 dark:text-gray-400 text-xs truncate">{{ vinyl.artist }}</p>
                                    <div class="mt-2 flex items-center text-xs text-gray-400 dark:text-gray-500">
                                        <span>par {{ vinyl.added_by.name }}</span>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <div v-else class="bg-white dark:bg-gray-800 rounded-lg p-8 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                            </svg>
                            <p class="mt-4 text-gray-500 dark:text-gray-400">Aucun vinyle pour le moment</p>
                        </div>
                    </div>

                    <!-- Sidebar: Activité du forum avec onglets -->
                    <div>
                        <!-- Header avec onglets -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                                <button
                                    @click="activeTab = 'messages'"
                                    :class="[
                                        'px-3 py-1.5 text-sm font-medium rounded-md transition',
                                        activeTab === 'messages'
                                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                    ]"
                                >
                                    Dernières réponses
                                </button>
                                <button
                                    @click="activeTab = 'threads'"
                                    :class="[
                                        'px-3 py-1.5 text-sm font-medium rounded-md transition',
                                        activeTab === 'threads'
                                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                    ]"
                                >
                                    Nouveaux sujets
                                </button>
                            </div>
                            <Link href="/forum" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                Forum &rarr;
                            </Link>
                        </div>

                        <!-- Contenu: Derniers messages -->
                        <div v-show="activeTab === 'messages'" class="max-h-[500px] overflow-y-auto">
                            <div v-if="latestPosts.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm divide-y divide-gray-100 dark:divide-gray-700">
                                <a
                                    v-for="post in latestPosts"
                                    :key="post.id"
                                    :href="route('forum.thread.show', { thread_id: post.thread.id }) + '#post-' + post.sequence"
                                    class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                >
                                    <div class="flex items-start gap-3">
                                        <div class="w-1 h-12 rounded-full flex-shrink-0 bg-blue-500"></div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ post.author.name }}</span>
                                                <span>&middot;</span>
                                                <span>{{ formatRelativeTime(post.created_at) }}</span>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
                                                {{ post.content }}
                                            </p>
                                            <div class="mt-1 text-xs text-blue-600 dark:text-blue-400 truncate">
                                                {{ post.thread.title }}
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div v-else class="bg-white dark:bg-gray-800 rounded-lg p-6 text-center">
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Aucun message récent</p>
                            </div>
                        </div>

                        <!-- Contenu: Nouvelles discussions -->
                        <div v-show="activeTab === 'threads'" class="max-h-[400px] overflow-y-auto">
                            <div v-if="latestThreads.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm divide-y divide-gray-100 dark:divide-gray-700">
                                <Link
                                    v-for="thread in latestThreads"
                                    :key="thread.id"
                                    :href="route('forum.thread.show', { thread_id: thread.id })"
                                    class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                >
                                    <div class="flex items-start gap-3">
                                        <div
                                            v-if="thread.category"
                                            class="w-1 h-10 rounded-full flex-shrink-0"
                                            :style="{ backgroundColor: thread.category.color_light_mode || '#3b82f6' }"
                                        ></div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-medium text-gray-900 dark:text-white text-sm truncate">
                                                {{ thread.title }}
                                            </h3>
                                            <div class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ thread.author.name }}</span>
                                                <span>&middot;</span>
                                                <span>{{ formatRelativeTime(thread.created_at) }}</span>
                                                <span v-if="thread.reply_count > 0">&middot;</span>
                                                <span v-if="thread.reply_count > 0" class="flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                    {{ thread.reply_count }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            </div>

                            <div v-else class="bg-white dark:bg-gray-800 rounded-lg p-6 text-center">
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Aucune discussion</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTA Section -->
                <div v-if="!$page.props.auth.user" class="mt-16">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl shadow-xl overflow-hidden">
                        <div class="px-6 py-12 sm:px-12 lg:flex lg:items-center lg:justify-between">
                            <div>
                                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">
                                    Prêt à rejoindre la communauté ?
                                </h2>
                                <p class="mt-2 text-lg text-blue-100">
                                    Créez votre compte gratuitement et commencez à gérer votre collection.
                                </p>
                            </div>
                            <div class="mt-8 lg:mt-0 lg:ml-8 flex flex-shrink-0">
                                <Link
                                    href="/register"
                                    class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-blue-600 bg-white hover:bg-blue-50 shadow-lg transition"
                                >
                                    Créer un compte
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                    <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                        &copy; {{ new Date().getFullYear() }} Vinyls Collection. Tous droits réservés.
                    </p>
                </div>
            </footer>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
