<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import VinylIcon from '@/Components/VinylIcon.vue';
import { Link } from '@inertiajs/vue3';
</script>

<style scoped>
@keyframes vinyl-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.5); }
    50% { box-shadow: 0 0 40px rgba(59, 130, 246, 0.8); }
}

@keyframes wave {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.vinyl-record {
    width: 120px;
    height: 120px;
    background: linear-gradient(45deg, #1a1a1a 30%, #333 31%, #333 50%, #1a1a1a 51%);
    border-radius: 50%;
    position: relative;
    animation: vinyl-spin 8s linear infinite;
}

.vinyl-record::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    background: #333;
    border-radius: 50%;
}

.vinyl-record::after {
    content: '';
    position: absolute;
    top: 15px;
    left: 15px;
    right: 15px;
    bottom: 15px;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 50%;
}

.floating-vinyl {
    animation: float 3s ease-in-out infinite;
}

.wave-effect {
    position: relative;
    overflow: hidden;
}

.wave-effect::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    animation: wave 3s ease-in-out infinite;
}

.glowing-border {
    animation: pulse-glow 2s ease-in-out infinite;
}

.left-section {
    background: linear-gradient(135deg, #eff6ff 0%, #e0e7ff 100%);
}

.dark .left-section {
    background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
}
</style>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex relative overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <!-- Floating Vinyl Records -->
            <div class="absolute top-10 left-10 floating-vinyl opacity-10 dark:opacity-20">
                <div class="vinyl-record"></div>
            </div>
            <div class="absolute top-1/3 right-20 floating-vinyl opacity-10 dark:opacity-15" style="animation-delay: -1s;">
                <div class="vinyl-record" style="animation-direction: reverse; animation-duration: 12s;"></div>
            </div>
            <div class="absolute bottom-20 left-1/4 floating-vinyl opacity-10 dark:opacity-20" style="animation-delay: -2s;">
                <div class="vinyl-record" style="animation-duration: 10s;"></div>
            </div>
            
            <!-- Animated Gradient Orbs -->
            <div class="absolute top-1/4 left-1/3 w-72 h-72 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-10 dark:opacity-20 animate-pulse"></div>
            <div class="absolute bottom-1/4 right-1/3 w-96 h-96 bg-gradient-to-r from-purple-400 to-pink-500 rounded-full mix-blend-multiply filter blur-xl opacity-10 dark:opacity-20 animate-pulse" style="animation-delay: 1s;"></div>
        </div>

        <!-- Left Section - Interactive Animation -->
        <div class="hidden lg:flex lg:w-1/2 relative items-center justify-center p-12 z-10 left-section">
            <div class="text-center">
                <!-- Animated Vinyl -->
                <div class="mb-8 relative">
                    <div class="floating-vinyl mb-6">
                        <div class="vinyl-record mx-auto glowing-border"></div>
                    </div>
                </div>
                
                <h1 class="text-5xl font-bold mb-6 text-gray-900 dark:text-white">
                    Vinyls Collection
                </h1>
                <p class="text-xl text-gray-700 dark:text-gray-300 leading-relaxed mb-8">
                    Découvrez, partagez et gérez votre collection de vinyles. 
                    Rejoignez une communauté passionnée de collectionneurs et mélomanes.
                </p>
                
                <!-- Animated Stats -->
                <div class="flex justify-center space-x-6">
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-xl p-6 wave-effect border border-gray-200 dark:border-gray-600 shadow-lg">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">266k</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Vinyles</div>
                    </div>
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-xl p-6 wave-effect border border-gray-200 dark:border-gray-600 shadow-lg" style="animation-delay: 1s;">
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">4.2k</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Utilisateurs</div>
                    </div>
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-xl p-6 wave-effect border border-gray-200 dark:border-gray-600 shadow-lg" style="animation-delay: 2s;">
                        <div class="text-3xl font-bold text-pink-600 dark:text-pink-400">9.9k</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Collections</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Section - Droite -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 relative z-10">
            <div class="w-full max-w-md">
                <!-- Logo et titre -->
                <div class="text-center mb-8">
                    <Link href="/" class="flex items-center justify-center gap-3 mb-4 hover:opacity-80 transition-opacity">
                        <VinylIcon class="text-blue-600 dark:text-blue-400" />
                        <h1 class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            Vinyls Collection
                        </h1>
                    </Link>
                    <h2 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $page.component === 'Auth/Login' ? 'Connexion' : 
                           $page.component === 'Auth/Register' ? 'Inscription' :
                           $page.component === 'Auth/ForgotPassword' ? 'Mot de passe oublié' :
                           $page.component === 'Auth/ResetPassword' ? 'Réinitialiser le mot de passe' :
                           $page.component === 'Auth/VerifyEmail' ? 'Vérification email' :
                           'Authentification' }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $page.component === 'Auth/Login' ? 'Connectez-vous à votre compte' : 
                           $page.component === 'Auth/Register' ? 'Créez votre compte pour commencer' :
                           $page.component === 'Auth/ForgotPassword' ? 'Entrez votre email pour recevoir un lien' :
                           $page.component === 'Auth/ResetPassword' ? 'Choisissez un nouveau mot de passe' :
                           $page.component === 'Auth/VerifyEmail' ? 'Vérifiez votre adresse email' :
                           'Accédez à votre espace personnel' }}
                    </p>
                </div>

                <!-- Form Card -->
                <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-8 border border-gray-200 dark:border-gray-700">
                    <slot />
                </div>

                <!-- Footer Links -->
                <div class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
                    <template v-if="$page.component === 'Auth/Login'">
                        Pas encore de compte ? 
                        <Link :href="route('register')" class="text-blue-600 hover:text-blue-500 dark:text-blue-400 font-medium">
                            Inscrivez-vous
                        </Link>
                    </template>
                    <template v-else-if="$page.component === 'Auth/Register'">
                        Déjà un compte ? 
                        <Link :href="route('login')" class="text-blue-600 hover:text-blue-500 dark:text-blue-400 font-medium">
                            Connectez-vous
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
