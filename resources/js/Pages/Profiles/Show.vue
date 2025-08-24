<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import VinylImage from '@/Components/VinylImage.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    collections: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        required: true
    }
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <Head>
        <title>{{ user.name }} - Collectionneur | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="description" :content="`Découvrez la collection de vinyles de ${user.name}. ${stats.total_vinyls} vinyles dans ${stats.total_collections} collections.`" />
        <meta name="keywords" :content="`${user.name}, collection vinyles, collectionneur`" />
        <meta property="og:title" :content="`${user.name} - Collectionneur de vinyles`" />
        <meta property="og:description" :content="`Découvrez la collection de ${user.name} sur Vinyls Collection`" />
        <meta property="og:type" content="profile" />
        <link rel="canonical" :href="route('profiles.show', user.id)" />
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link href="/collectors" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                        ← Retour aux collectionneurs
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Profil de {{ user.name }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                            
                            <div class="w-24 h-24 bg-gradient-to-br from-purple-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-3xl">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                            
                            <div class="flex-1">
                                
                                <div class="mb-4">
                                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                        {{ user.name }}
                                    </h1>
                                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                        <span v-if="user.location" class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            {{ user.location }}
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Membre depuis {{ stats.member_since }}
                                        </span>
                                    </div>
                                </div>

                                
                                <p v-if="user.bio" class="text-gray-700 dark:text-gray-300 mb-4 leading-relaxed">
                                    {{ user.bio }}
                                </p>

                                
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="text-2xl font-bold text-purple-600">{{ stats.total_collections }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Collection{{ stats.total_collections > 1 ? 's' : '' }}</div>
                                    </div>
                                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="text-2xl font-bold text-blue-600">{{ stats.total_vinyls }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Vinyle{{ stats.total_vinyls > 1 ? 's' : '' }}</div>
                                    </div>
                                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg md:col-span-1 col-span-2">
                                        <div class="text-2xl font-bold text-green-600">{{ collections.length }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Publique{{ collections.length > 1 ? 's' : '' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                            Collections publiques
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                                ({{ collections.length }} collection{{ collections.length > 1 ? 's' : '' }})
                            </span>
                        </h3>

                        <div v-if="collections.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucune collection publique</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ user.name }} n'a pas encore de collections publiques.
                            </p>
                        </div>

                        <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                            <div v-for="collection in collections" :key="collection.id"
                                 class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                                
                                
                                <div class="mb-3">
                                    <Link :href="`/collectors/${user.id}/collections/${collection.id}`"
                                          class="text-base font-semibold text-gray-900 dark:text-white hover:text-purple-600 dark:hover:text-purple-400 line-clamp-1">
                                        {{ collection.collection_nom }}
                                    </Link>
                                    <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ collection.collection_vinyls_count }} vinyle{{ collection.collection_vinyls_count > 1 ? 's' : '' }}</span>
                                        <span>{{ formatDate(collection.collection_date_modif) }}</span>
                                    </div>
                                </div>

                                
                                <p v-if="collection.collection_commentaires" 
                                   class="text-gray-600 dark:text-gray-300 text-xs mb-3 line-clamp-1">
                                    {{ collection.collection_commentaires }}
                                </p>

                                
                                <div v-if="collection.collection_vinyls && collection.collection_vinyls.length > 0" 
                                     class="mb-3">
                                    <div class="flex items-start gap-2">
                                        
                                        <div class="relative w-12 h-12 flex-shrink-0">
                                            <div v-for="(collectionVinyl, index) in collection.collection_vinyls.slice(0, 3)" 
                                                 :key="collectionVinyl.id"
                                                 class="absolute w-10 h-10 rounded shadow-sm border border-white dark:border-gray-600"
                                                 :style="{ 
                                                     left: `${index * 4}px`, 
                                                     top: `${index * 2}px`,
                                                     zIndex: 3 - index
                                                 }">
                                                <img v-if="collectionVinyl.vinyl?.pochette"
                                                     :src="collectionVinyl.vinyl.pochette"
                                                     :alt="collectionVinyl.vinyl?.vinyl_nom || 'Pochette'"
                                                     class="w-full h-full object-cover rounded"
                                                     @error="$event.target.style.display = 'none'; $event.target.nextElementSibling.style.display = 'flex'"
                                                />
                                                <div :style="{ display: collectionVinyl.vinyl?.pochette ? 'none' : 'flex' }" 
                                                     class="w-full h-full bg-gray-300 dark:bg-gray-600 rounded items-center justify-center">
                                                    <svg class="w-3 h-3 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                                        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                                        <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Derniers ajouts :</div>
                                            <div class="space-y-0.5">
                                                <div v-for="collectionVinyl in collection.collection_vinyls.slice(0, 2)" 
                                                     :key="'text-' + collectionVinyl.id"
                                                     class="text-xs">
                                                    <div class="font-medium text-gray-900 dark:text-white truncate">
                                                        {{ collectionVinyl.vinyl?.vinyl_nom || 'Titre inconnu' }}
                                                    </div>
                                                    <div class="text-gray-500 dark:text-gray-400 truncate">
                                                        {{ collectionVinyl.vinyl?.artiste || 'Artiste inconnu' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                
                                <Link :href="`/collectors/${user.id}/collections/${collection.id}`"
                                      class="inline-flex items-center px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded transition-colors">
                                    Voir la collection
                                    <svg class="ml-1 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>