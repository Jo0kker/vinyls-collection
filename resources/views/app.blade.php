<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Vinyls Collection') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/forum/blade-tailwind/css/forum.css'])

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        <script>
            window.initialPage = @json($page);
        </script>

        <style>
            [v-cloak] {
                display: none;
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 min-h-screen">
        <!-- Loading overlay to prevent flash -->
        <div id="loading-overlay" class="fixed inset-0 bg-gray-100 dark:bg-gray-900 z-50 flex items-center justify-center">
            <div class="text-center">
                <svg class="w-12 h-12 text-blue-600 dark:text-blue-400 animate-spin mx-auto mb-4" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                </svg>
                <p class="text-gray-600 dark:text-gray-400">Chargement...</p>
            </div>
        </div>
        
        <div id="app" data-page="{{ json_encode($page) }}" v-cloak>
            @inertia
        </div>
        
        <script>
            // Hide loading overlay when Inertia app is ready
            document.addEventListener('DOMContentLoaded', function() {
                // Wait for Inertia to be ready
                const checkInertia = setInterval(function() {
                    if (window.Inertia || document.querySelector('#app [data-page]')) {
                        clearInterval(checkInertia);
                        const overlay = document.getElementById('loading-overlay');
                        if (overlay) {
                            overlay.style.opacity = '0';
                            overlay.style.transition = 'opacity 0.3s ease';
                            setTimeout(() => overlay.remove(), 300);
                        }
                    }
                }, 50);
                
                // Fallback: remove overlay after 2 seconds max
                setTimeout(function() {
                    const overlay = document.getElementById('loading-overlay');
                    if (overlay) {
                        overlay.remove();
                    }
                }, 2000);
            });
        </script>
    </body>
</html>
