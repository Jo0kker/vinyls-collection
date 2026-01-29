<template>
    <Head>
        <title>Politique de Confidentialité | {{ $page.props.app?.name || 'Vinyls Collection' }}</title>
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 sm:p-8 text-gray-900 dark:text-gray-100">
                        <h1 class="text-3xl font-bold mb-8">Politique de Confidentialité</h1>

                        <div class="prose dark:prose-invert max-w-none space-y-8">
                            <section>
                                <h2 class="text-xl font-semibold mb-4">1. Introduction</h2>
                                <p>
                                    La présente politique de confidentialité décrit comment <strong>{{ $page.props.app?.name || 'Vinyls Collection' }}</strong>
                                    collecte, utilise et protège vos données personnelles conformément au Règlement Général
                                    sur la Protection des Données (RGPD).
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">2. Responsable du traitement</h2>
                                <p>
                                    Le responsable du traitement des données est :
                                </p>
                                <ul class="list-none space-y-1 mt-2">
                                    <li><strong>Éditeur :</strong> Codible (micro-entreprise)</li>
                                    <li><strong>Responsable :</strong> Benjamin Roma-Sacconney</li>
                                    <li><strong>Email :</strong> <a href="mailto:contact@codible.net" class="text-blue-600 dark:text-blue-400 hover:underline">contact@codible.net</a></li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">3. Données collectées</h2>
                                <p>Nous collectons les données suivantes :</p>

                                <h3 class="text-lg font-medium mt-4 mb-2">Données de compte</h3>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li>Adresse email (obligatoire)</li>
                                    <li>Nom d'utilisateur (obligatoire)</li>
                                    <li>Mot de passe (stocké de manière chiffrée)</li>
                                    <li>Photo de profil (optionnel)</li>
                                    <li>Biographie (optionnel)</li>
                                    <li>Localisation (optionnel)</li>
                                    <li>Liens vers réseaux sociaux (optionnel)</li>
                                </ul>

                                <h3 class="text-lg font-medium mt-4 mb-2">Données de collection</h3>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li>Informations sur vos vinyles (titre, artiste, label, etc.)</li>
                                    <li>Collections créées et leur visibilité</li>
                                    <li>Données importées depuis Discogs (si vous utilisez cette fonctionnalité)</li>
                                </ul>

                                <h3 class="text-lg font-medium mt-4 mb-2">Données d'utilisation</h3>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li>Messages envoyés via la messagerie privée</li>
                                    <li>Publications sur le forum</li>
                                    <li>Date de création du compte et dernière connexion</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">4. Finalités du traitement</h2>
                                <p>Vos données sont utilisées pour :</p>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li>Créer et gérer votre compte utilisateur</li>
                                    <li>Permettre la gestion de votre collection de vinyles</li>
                                    <li>Permettre les interactions avec d'autres utilisateurs (forum, messagerie)</li>
                                    <li>Afficher votre profil public (si vous l'avez activé)</li>
                                    <li>Vous envoyer des notifications liées au service (nouveaux messages, réponses forum)</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">5. Base légale du traitement</h2>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li><strong>Exécution du contrat :</strong> traitement nécessaire à la fourniture du service</li>
                                    <li><strong>Consentement :</strong> pour les fonctionnalités optionnelles (profil public, notifications)</li>
                                    <li><strong>Intérêt légitime :</strong> amélioration du service et sécurité</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">6. Durée de conservation</h2>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li><strong>Données de compte :</strong> conservées tant que le compte est actif</li>
                                    <li><strong>Données de collection :</strong> conservées tant que le compte est actif</li>
                                    <li><strong>Messages et publications :</strong> conservés tant que le compte est actif</li>
                                </ul>
                                <p class="mt-2">
                                    En cas de suppression de compte, vos données sont supprimées dans un délai de 30 jours,
                                    sauf obligation légale de conservation.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">7. Partage des données</h2>
                                <p>Vos données ne sont pas vendues à des tiers.</p>
                                <p>Les données peuvent être partagées avec :</p>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li><strong>Discogs :</strong> si vous utilisez la fonctionnalité d'import (via leur API)</li>
                                    <li><strong>Hébergeur :</strong> pour le stockage technique des données</li>
                                    <li><strong>Autres utilisateurs :</strong> uniquement les informations que vous choisissez de rendre publiques</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">8. Cookies</h2>
                                <p>
                                    Le site utilise uniquement des cookies techniques nécessaires au fonctionnement :
                                </p>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li><strong>Cookie de session :</strong> maintient votre connexion</li>
                                    <li><strong>Token CSRF :</strong> sécurité contre les attaques</li>
                                </ul>
                                <p class="mt-2">
                                    Aucun cookie publicitaire ou de tracking n'est utilisé.
                                    Ces cookies techniques sont exemptés de consentement.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">9. Vos droits</h2>
                                <p>Conformément au RGPD, vous disposez des droits suivants :</p>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li><strong>Droit d'accès :</strong> obtenir une copie de vos données</li>
                                    <li><strong>Droit de rectification :</strong> corriger vos données inexactes</li>
                                    <li><strong>Droit à l'effacement :</strong> demander la suppression de vos données</li>
                                    <li><strong>Droit à la portabilité :</strong> recevoir vos données dans un format structuré</li>
                                    <li><strong>Droit d'opposition :</strong> vous opposer à certains traitements</li>
                                    <li><strong>Droit de retirer votre consentement :</strong> à tout moment</li>
                                </ul>
                                <p class="mt-4">
                                    Pour exercer ces droits, contactez-nous à : <a href="mailto:contact@codible.net" class="text-blue-600 dark:text-blue-400 hover:underline">contact@codible.net</a>
                                </p>
                                <p class="mt-2">
                                    Vous pouvez également déposer une réclamation auprès de la CNIL
                                    (<a href="https://www.cnil.fr" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">www.cnil.fr</a>).
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">10. Sécurité</h2>
                                <p>
                                    Nous mettons en place des mesures techniques et organisationnelles pour protéger vos données :
                                </p>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li>Chiffrement des mots de passe</li>
                                    <li>Connexion sécurisée (HTTPS)</li>
                                    <li>Accès restreint aux données</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">11. Modifications</h2>
                                <p>
                                    Cette politique peut être mise à jour. En cas de modification substantielle,
                                    nous vous en informerons par email ou via le site.
                                </p>
                            </section>

                            <section>
                                <h2 class="text-xl font-semibold mb-4">12. Contact</h2>
                                <p>
                                    Pour toute question relative à cette politique ou à vos données personnelles :
                                    <a href="mailto:contact@codible.net" class="text-blue-600 dark:text-blue-400 hover:underline">contact@codible.net</a>
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
