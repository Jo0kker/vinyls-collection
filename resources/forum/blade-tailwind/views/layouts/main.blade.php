<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        @if (isset($thread_title))
            {{ $thread_title }} —
        @endif
        @if (isset($category))
            {{ $category->title }} —
        @endif
        {{ trans('forum::general.home_title') }}
    </title>

    <!-- Google Fonts Bebas Neue -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">

    @vite(['resources/forum/blade-tailwind/css/forum.css', 'resources/forum/blade-tailwind/js/forum.js'])
</head>
<body class="forum min-h-screen bg-background text-white font-sans">
    <nav class="v-navbar bg-accent shadow py-4">
        <div class="container mx-auto px-4 md:flex md:items-center md:gap-4">
            <div class="flex justify-between items-center">
                <a class="text-3xl font-bold tracking-widest text-vinyl drop-shadow-lg" href="{{ url(config('forum.frontend.router.prefix')) }}">Vinyls Collection</a>
                <button class="navbar-toggler block md:hidden border border-vinyl rounded-md px-2 py-1 text-vinyl" type="button" :class="{ collapsed: isCollapsed }" @click="isCollapsed = !isCollapsed">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="navbar-toggler-icon w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
            <div class="grow justify-between navbar-collapse" :class="{ 'flex flex-col': !isCollapsed, 'hidden md:flex': isCollapsed }">
                <ul class="flex flex-col md:flex-row gap-3 mb-4 md:mb-0">
                    <li>
                        <a class="text-vinyl hover:text-white transition" href="{{ url(config('forum.frontend.router.prefix')) }}">{{ trans('forum::general.index') }}</a>
                    </li>
                    <li>
                        <a class="text-vinyl hover:text-white transition" href="{{ route('forum.recent') }}">{{ trans('forum::threads.recent') }}</a>
                    </li>
                    @auth
                        <li>
                            <a class="text-vinyl hover:text-white transition" href="{{ route('forum.unread') }}">{{ trans('forum::threads.unread_updated') }}</a>
                        </li>
                    @endauth
                    @can ('moveCategories')
                        <li>
                            <a class="text-vinyl hover:text-white transition" href="{{ route('forum.category.manage') }}">{{ trans('forum::general.manage') }}</a>
                        </li>
                    @endcan
                </ul>
                <ul class="navbar-nav flex gap-4 flex-col md:flex-row">
                    @if (Auth::check())
                        <li class="nav-item dropdown relative">
                            <a class="dropdown-toggle text-vinyl flex items-center gap-1" href="#" id="navbarDropdownMenuLink" @click="isUserDropdownCollapsed = !isUserDropdownCollapsed">
                                {{ $username }}

                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </a>
                            <div class="border border-vinyl absolute left-0 bg-secondary rounded-md w-44 divide-y divide-vinyl shadow-lg" :class="{ hidden: isUserDropdownCollapsed }" aria-labelledby="navbarDropdownMenuLink">
                            <a class="block px-4 py-2 text-vinyl hover:bg-accent hover:text-white transition" href="{{ route('dashboard') }}">
                                    <i class="fas fa-home"></i>
                                    {{ trans('forum::general.dashboard') }}
                                </a>   
                            <a class="block px-4 py-2 text-vinyl hover:bg-accent hover:text-white transition" href="{{ route('profile.edit') }}">
                                    <i class="fas fa-user"></i>
                                    {{ trans('forum::general.profile') }}
                                </a>    
                                <a class="block px-4 py-2 text-vinyl hover:bg-accent hover:text-white transition" href="{{ url('/logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt"></i>
                                    {{ trans('forum::general.logout') }}
                                </a>
                                <form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link text-vinyl hover:text-white transition" href="{{ url('/login') }}">{{ trans('forum::general.login') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-vinyl hover:text-white transition" href="{{ url('/register') }}">{{ trans('forum::general.register') }}</a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <div id="main" class="container mx-auto p-6 bg-main text-header rounded-xl shadow-lg mt-8">
        @include ('forum::partials.breadcrumbs')
        @include ('forum::partials.alerts')

        @yield('content')
    </div>

    @yield('footer')

    <script>
        window.defaultCategoryColor = "{{ config('forum.frontend.default_category_color') }}";
    </script>
</body>
</html>
