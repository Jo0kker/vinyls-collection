<template>
    <Head>
        <title>Mentions Légales | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 sm:p-8 text-gray-900 dark:text-gray-100">
                        <h1 class="text-3xl font-bold mb-8">Mentions Légales</h1>

                        <div class="prose dark:prose-invert max-w-none space-y-8">
                            <section>
                                <h2 class="text-xl font-semibold mb-4">1. Éditeur du site</h2>
                                <p>
                                    Le site <strong>{{ $page.props.app?.name || 'Vinyls Collection' }}</strong> est édité par :
                                </p>
                                <ul class="list-none space-y-1 mt-2">
                                    <li><strong>Éditeur :</strong> Codible (micro-entreprise)</li>
                                    <li><strong>Responsable :</strong> Benjamin Roma-Sacconney</li>
                                    <li><strong>Email :</strong> contact@codible.net</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">2. Hébergement</h2>
                                <p>Le site est hébergé par :</p>
                                <ul class="list-none space-y-1 mt-2">
                                    <li><strong>Hébergeur :</strong> Hetzner Online GmbH</li>
                                    <li><strong>Adresse :</strong> Industriestr. 25, 91710 Gunzenhausen, Allemagne</li>
                                    <li><strong>Site web :</strong> <a href="https://www.hetzner.com/" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">www.hetzner.com</a></li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">3. Propriété intellectuelle</h2>
                                <p>
                                    L'ensemble des contenus présents sur le site (textes, images, graphismes, logo, icônes, etc.)
                                    est protégé par les lois en vigueur sur la propriété intellectuelle.
                                </p>
                                <p>
                                    Les images des pochettes de vinyles et les informations associées sont la propriété de leurs
                                    détenteurs respectifs. Elles sont utilisées à des fins de référencement et de collection personnelle.
                                </p>
                                <p>
                                    Les données provenant de Discogs sont utilisées conformément à leurs conditions d'utilisation.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">4. Responsabilité</h2>
                                <p>
                                    L'éditeur s'efforce de fournir des informations aussi précises que possible.
                                    Toutefois, il ne pourra être tenu responsable des omissions, inexactitudes ou
                                    carences dans la mise à jour.
                                </p>
                                <p>
                                    L'utilisateur est seul responsable de l'utilisation qu'il fait des informations
                                    et contenus présents sur ce site.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">5. Cookies</h2>
                                <p>
                                    Ce site utilise uniquement des cookies techniques nécessaires à son bon fonctionnement
                                    (session utilisateur, authentification). Ces cookies sont exemptés de consentement
                                    conformément à la réglementation en vigueur.
                                </p>
                                <p>
                                    Aucun cookie publicitaire ou de tracking n'est utilisé.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">6. Contact</h2>
                                <p>
                                    Pour toute question concernant ces mentions légales ou le site en général,
                                    vous pouvez nous contacter à l'adresse : <a href="mailto:contact@codible.net" class="text-blue-600 dark:text-blue-400 hover:underline">contact@codible.net</a>
                                </p>
                            </section>

                            <p class="text-sm text-gray-500 dark:text-gray-400 pt-4 border-t border-gray-200 dark:border-gray-700">
                                Dernière mise à jour : {{ new Date().toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>
