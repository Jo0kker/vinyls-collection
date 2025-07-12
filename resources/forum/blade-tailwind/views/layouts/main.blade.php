<!DOCTYPE html>
<html lang="fr" class="dark" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @if (isset($thread_title))
            {{ $thread_title }} —
        @endif
        @if (isset($category))
            {{ $category->title }} —
        @endif
        {{ trans('forum::general.home_title') }}
    </title>
    
    @php
        $page_title = '';
        if (isset($thread_title)) {
            $page_title .= $thread_title . ' — ';
        }
        if (isset($category)) {
            $page_title .= $category->title . ' — ';
        }
        $page_title .= trans('forum::general.home_title');
        
        $page_description = isset($meta_description) ? $meta_description : trans('forum::general.forum_description');
    @endphp
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="{{ $page_description }}">
    <meta name="keywords" content="forum, vinyles, collection, musique, discussion, communauté">
    <meta name="author" content="Vinyls Collection">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="{{ $page_title }}">
    <meta property="og:description" content="{{ $page_description }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Vinyls Collection">
    <meta property="og:image" content="{{ asset('images/forum-og.jpg') }}">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $page_title }}">
    <meta name="twitter:description" content="{{ $page_description }}">
    <meta name="twitter:image" content="{{ asset('images/forum-og.jpg') }}">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">

    @vite(['resources/forum/blade-tailwind/css/forum.css', 'resources/forum/blade-tailwind/js/forum.js'])
    
    <style>
        /* Ensure consistent background to prevent flash */
        body {
            background-color: rgb(243 244 246);
            min-height: 100vh;
        }
        
        .dark body {
            background-color: rgb(17 24 39);
        }
        
        /* Smooth transitions */
        body, .container {
            transition: background-color 0.3s ease;
        }
    </style>

    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/w5h4t1htcxijwdh3k2fmiu1ocx7uk21rtw96p2jy2w0dnaha/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body class="forum">
    <nav class="v-navbar shadow py-4">
        <div class="container mx-auto px-4 md:flex md:items-center md:gap-4">
            <div class="flex justify-between items-center">
                <a class="text-2xl font-bold text-blue-600 dark:text-blue-400 flex items-center gap-2" href="/">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                        <circle cx="12" cy="12" r="3" fill="currentColor"/>
                    </svg>
                    Vinyls Collection
                </a>
                <button class="navbar-toggler block md:hidden border rounded-md px-2 py-1" type="button" :class="{ collapsed: isCollapsed }" @click="isCollapsed = !isCollapsed">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="navbar-toggler-icon w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
            <div class="grow justify-between navbar-collapse" :class="{ 'flex flex-col': !isCollapsed, 'hidden md:flex': isCollapsed }">
                <ul class="flex flex-col md:flex-row gap-3 mb-4 md:mb-0">
                    <li>
                        <a class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200" href="{{ url(config('forum.frontend.router.prefix')) }}">{{ trans('forum::general.index') }}</a>
                    </li>
                    <li>
                        <a class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200" href="{{ route('forum.recent') }}">{{ trans('forum::threads.recent') }}</a>
                    </li>
                    @auth
                        <li>
                            <a class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200" href="{{ route('forum.unread') }}">{{ trans('forum::threads.unread_updated') }}</a>
                        </li>
                    @endauth
                    @can ('moveCategories')
                        <li>
                            <a class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200" href="{{ route('forum.category.manage') }}">{{ trans('forum::general.manage') }}</a>
                        </li>
                    @endcan
                </ul>
                <ul class="navbar-nav flex gap-4 flex-col md:flex-row">
                    @if (Auth::check())
                        <li class="nav-item dropdown relative">
                            <a class="dropdown-toggle text-gray-500 dark:text-gray-400 flex items-center gap-1" href="#" id="navbarDropdownMenuLink" @click="isUserDropdownCollapsed = !isUserDropdownCollapsed">
                                {{ $username }}
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </a>
                            <div class="dropdown-menu" :class="{ hidden: isUserDropdownCollapsed }" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="{{ route('dashboard') }}">
                                    {{ trans('forum::general.dashboard') }}
                                </a>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    {{ trans('forum::general.profile') }}
                                </a>
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ trans('forum::general.logout') }}
                                    </a>
                                </form>
                            </div>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Log in</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Register</a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <div id="main" class="container mx-auto p-4">
        @include ('forum::partials.breadcrumbs')
        @include ('forum::partials.alerts')

        <div class="dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                @yield('content')
            </div>
        </div>
    </div>

    @yield('footer')

    <script>
        window.defaultCategoryColor = '{{ config('forum.frontend.default_category_color') }}';
    </script>

    @stack('scripts')
</body>
</html>
