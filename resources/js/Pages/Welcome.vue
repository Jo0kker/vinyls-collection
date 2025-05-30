<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

function handleImageError() {
    document.getElementById('screenshot-container')?.classList.add('!hidden');
    document.getElementById('docs-card')?.classList.add('!row-span-1');
    document.getElementById('docs-card-content')?.classList.add('!flex-row');
    document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>
    <Head>
        <title>Vinyls Collection - Gérez et partagez votre collection de vinyles</title>
        <meta name="description" content="Vinyls Collection est la plateforme communautaire pour gérer, partager et discuter autour de votre collection de vinyles. Rejoignez le forum, échangez avec d'autres passionnés et enrichissez votre discothèque !" />
    </Head>
    <div class="min-h-screen bg-gray-100 flex flex-col justify-between">
        <header class="py-12 text-center">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4 text-blue-700">Bienvenue sur Vinyls Collection</h1>
            <p class="text-lg md:text-xl max-w-2xl mx-auto text-gray-600">
                Gérez, partagez et discutez autour de votre collection de vinyles. Rejoignez une communauté de passionnés, échangez vos trouvailles et découvrez de nouveaux trésors musicaux !
            </p>
            <div class="mt-8 flex justify-center gap-4">
                <a href="/forum" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md px-8 py-4 shadow transition text-lg">Accéder au forum</a>
                <Link v-if="$page.props.auth.user" href="/dashboard" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-md px-8 py-4 shadow transition text-lg">Mon espace</Link>
                <Link v-else href="/register" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-md px-8 py-4 shadow transition text-lg">Créer un compte</Link>
            </div>
        </header>

        <main class="flex-1 flex flex-col items-center justify-center px-4">
            <section class="max-w-3xl w-full bg-white rounded-xl shadow p-8 mb-8">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">À propos de Vinyls Collection</h2>
                <p class="mb-4 text-gray-700">
                    Vinyls Collection est une plateforme dédiée aux amateurs et collectionneurs de vinyles. Notre objectif est de vous offrir un espace pour <strong>gérer votre discothèque</strong>, <strong>échanger avec d'autres passionnés</strong> et <strong>partager vos découvertes musicales</strong>.
                </p>
                <p class="text-gray-700">
                    Grâce à notre <strong>forum</strong>, vous pouvez poser vos questions, organiser des échanges, discuter des dernières sorties ou encore demander des conseils sur l'entretien de vos vinyles. Rejoignez-nous et faites vivre la passion du vinyle !
                </p>
            </section>

            <section class="max-w-3xl w-full bg-white rounded-xl shadow p-8 mb-8">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">Fonctionnalités principales</h2>
                <ul class="list-disc pl-6 space-y-2 text-gray-700">
                    <li>Ajoutez et gérez facilement votre collection de vinyles</li>
                    <li>Partagez vos coups de cœur et vos trouvailles</li>
                    <li>Discutez et échangez sur le forum avec la communauté</li>
                    <li>Recherchez des vinyles par artiste, genre ou année</li>
                    <li>Recevez des conseils sur l'entretien et la conservation</li>
                </ul>
            </section>

            <section class="max-w-3xl w-full bg-white rounded-xl shadow p-8 mb-8 text-center">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">Rejoignez la communauté !</h2>
                <p class="mb-4 text-gray-700">Inscrivez-vous gratuitement pour profiter de toutes les fonctionnalités et participer aux discussions.</p>
                <div class="flex justify-center gap-4">
                    <Link v-if="!$page.props.auth.user" href="/register" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md px-8 py-4 shadow transition text-lg">Créer un compte</Link>
                    <Link v-if="!$page.props.auth.user" href="/login" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-md px-8 py-4 shadow transition text-lg">Se connecter</Link>
                </div>
            </section>
        </main>

        <footer class="py-8 text-center text-gray-500 text-sm">
            &copy; {{ new Date().getFullYear() }} Vinyls Collection — Propulsé par Laravel & Vue.js
        </footer>
    </div>
</template>
