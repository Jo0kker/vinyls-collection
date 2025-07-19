<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Vinyls Collection') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css'])

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        <script>
            window.initialPage = @json($page);

            // Force dark mode always
            document.documentElement.classList.add('dark');
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.theme = 'dark';
        </script>

        <style>
            [v-cloak] {
                display: none;
            }

            /* Force dark mode globally */
            html, html * {
                color-scheme: dark !important;
            }

            html {
                background-color: #111827 !important;
                color: #f9fafb !important;
            }

            body {
                background-color: #111827 !important;
                color: #f9fafb !important;
            }

            /* Force all backgrounds to dark */
            .bg-white {
                background-color: #1f2937 !important;
            }

            .bg-gray-50 {
                background-color: #374151 !important;
            }

            .bg-gray-100 {
                background-color: #111827 !important;
            }

            /* Force all text to light */
            .text-gray-900 {
                color: #f9fafb !important;
            }

            .text-gray-800 {
                color: #e5e7eb !important;
            }

            .text-gray-700 {
                color: #d1d5db !important;
            }

            /* Navigation specific */
            .v-navbar {
                background-color: #1f2937 !important;
                color: #f9fafb !important;
            }

            /* Force button styles */
            button {
                color: #e5e7eb !important;
            }

            .bg-gray-300 {
                background-color: #4b5563 !important;
                color: #e5e7eb !important;
            }

            .text-gray-700 {
                color: #e5e7eb !important;
            }

            /* Modal backgrounds */
            .bg-gray-600 {
                background-color: rgba(17, 24, 39, 0.8) !important;
            }

            /* Form inputs */
            input, textarea, select {
                background-color: #374151 !important;
                border-color: #4b5563 !important;
                color: #f9fafb !important;
            }

            /* Hover states */
            .hover\:bg-gray-400:hover {
                background-color: #6b7280 !important;
                color: #f9fafb !important;
            }

            /* Actions rapides - Force colored backgrounds to dark variants */
            .bg-blue-50 {
                background-color: rgba(59, 130, 246, 0.1) !important;
            }

            .bg-purple-50 {
                background-color: rgba(139, 92, 246, 0.1) !important;
            }

            .bg-green-50 {
                background-color: rgba(34, 197, 94, 0.1) !important;
            }

            /* Hover states for colored backgrounds */
            .hover\:bg-blue-100:hover {
                background-color: rgba(59, 130, 246, 0.2) !important;
            }

            .hover\:bg-purple-100:hover {
                background-color: rgba(139, 92, 246, 0.2) !important;
            }

            .hover\:bg-green-100:hover {
                background-color: rgba(34, 197, 94, 0.2) !important;
            }

            /* Modal vinyl cards hover fix */
            .hover\:bg-gray-50:hover {
                background-color: #374151 !important;
                color: #f9fafb !important;
            }

            .dark\:hover\:bg-gray-700:hover {
                background-color: #374151 !important;
                color: #f9fafb !important;
            }

            /* Modal content fix */
            .border {
                border-color: #4b5563 !important;
            }

            .dark\:border-gray-600 {
                border-color: #4b5563 !important;
            }

            /* Navbar hover fix */
            .hover\:text-gray-800:hover {
                color: #d1d5db !important;
            }

            .dark\:hover\:text-gray-200:hover {
                color: #d1d5db !important;
            }

            .hover\:text-gray-700:hover {
                color: #d1d5db !important;
            }

            .dark\:hover\:text-gray-300:hover {
                color: #d1d5db !important;
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
        <!--Start of Tawk.to Script-->
        <script type="text/javascript">
            var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
            (function(){
                var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
                s1.async=true;
                s1.src='https://embed.tawk.to/624bef4a2abe5b455fc4d655/1fvs9tf84';
                s1.charset='UTF-8';
                s1.setAttribute('crossorigin','*');
                s0.parentNode.insertBefore(s1,s0);
            })();
        </script>
        <!--End of Tawk.to Script-->
    </body>
</html>
