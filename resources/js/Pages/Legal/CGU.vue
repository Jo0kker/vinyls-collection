<template>
    <Head>
        <title>Conditions Générales d'Utilisation | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 sm:p-8 text-gray-900 dark:text-gray-100">
                        <h1 class="text-3xl font-bold mb-8">Conditions Générales d'Utilisation</h1>

                        <div class="prose dark:prose-invert max-w-none space-y-8">
                            <section>
                                <h2 class="text-xl font-semibold mb-4">1. Objet</h2>
                                <p>
                                    Les présentes Conditions Générales d'Utilisation (CGU) ont pour objet de définir
                                    les modalités d'accès et d'utilisation du site <strong>{{ $page.props.app?.name || 'Vinyls Collection' }}</strong>.
                                </p>
                                <p>
                                    L'utilisation du site implique l'acceptation pleine et entière des présentes CGU.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">2. Description du service</h2>
                                <p>
                                    {{ $page.props.app?.name || 'Vinyls Collection' }} est une plateforme communautaire permettant aux collectionneurs de vinyles de :
                                </p>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li>Gérer leur collection personnelle de vinyles</li>
                                    <li>Importer leurs collections depuis Discogs</li>
                                    <li>Partager leur collection avec la communauté</li>
                                    <li>Échanger avec d'autres collectionneurs via le forum et la messagerie</li>
                                    <li>Découvrir les collections d'autres utilisateurs</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">3. Inscription et compte utilisateur</h2>
                                <p>
                                    L'accès à certaines fonctionnalités nécessite la création d'un compte utilisateur.
                                    L'utilisateur s'engage à :
                                </p>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li>Fournir des informations exactes lors de l'inscription</li>
                                    <li>Maintenir la confidentialité de ses identifiants de connexion</li>
                                    <li>Ne pas créer plusieurs comptes</li>
                                    <li>Signaler immédiatement toute utilisation non autorisée de son compte</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">4. Comportement des utilisateurs</h2>
                                <p>Les utilisateurs s'engagent à :</p>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li>Respecter les autres utilisateurs et adopter un comportement courtois</li>
                                    <li>Ne pas publier de contenu illégal, offensant, diffamatoire ou inapproprié</li>
                                    <li>Ne pas utiliser le site à des fins commerciales non autorisées</li>
                                    <li>Ne pas tenter de nuire au fonctionnement du site</li>
                                    <li>Respecter les droits de propriété intellectuelle</li>
                                </ul>
                                <p class="mt-4">
                                    Tout manquement à ces règles pourra entraîner la suspension ou la suppression du compte
                                    sans préavis.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">5. Contenu utilisateur</h2>
                                <p>
                                    Les utilisateurs sont responsables des contenus qu'ils publient sur le site
                                    (messages, commentaires, descriptions, etc.).
                                </p>
                                <p>
                                    En publiant du contenu, l'utilisateur accorde au site une licence non exclusive
                                    d'utilisation de ce contenu dans le cadre du fonctionnement du service.
                                </p>
                                <p>
                                    L'éditeur se réserve le droit de supprimer tout contenu jugé inapproprié.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">6. Données personnelles</h2>
                                <p>
                                    Le traitement des données personnelles est détaillé dans notre
                                    <a :href="route('legal.privacy')" class="text-blue-600 dark:text-blue-400 hover:underline">
                                        Politique de Confidentialité
                                    </a>.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">7. Propriété intellectuelle</h2>
                                <p>
                                    Le site et son contenu (hors contenu utilisateur) sont protégés par le droit
                                    de la propriété intellectuelle.
                                </p>
                                <p>
                                    Les images et informations relatives aux vinyles proviennent en partie de
                                    sources tierces (notamment Discogs) et restent la propriété de leurs détenteurs respectifs.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">8. Limitation de responsabilité</h2>
                                <p>
                                    Le site est fourni "en l'état". L'éditeur ne garantit pas l'absence d'erreurs
                                    ou d'interruptions de service.
                                </p>
                                <p>
                                    L'éditeur ne pourra être tenu responsable des dommages directs ou indirects
                                    résultant de l'utilisation du site.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">9. Modification des CGU</h2>
                                <p>
                                    L'éditeur se réserve le droit de modifier les présentes CGU à tout moment.
                                    Les utilisateurs seront informés des modifications importantes.
                                    La poursuite de l'utilisation du site vaut acceptation des nouvelles conditions.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">10. Droit applicable</h2>
                                <p>
                                    Les présentes CGU sont soumises au droit français. En cas de litige,
                                    les tribunaux français seront seuls compétents.
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
