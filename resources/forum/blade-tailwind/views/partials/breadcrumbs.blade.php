<nav aria-label="breadcrumb" class="mb-4">
    <ol class="flex flex-wrap [&_li]:after:content-['/'] [&_li]:after:px-2 [&_li]:after:text-gray-500 dark:[&_li]:after:text-gray-400 [&_li:last-child]:after:content-['']">
        <li class=""><a href="{{ url(config('forum.frontend.router.prefix')) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ trans('forum::general.index') }}</a></li>
        @if (isset($category) && $category)
            @include ('forum::partials.breadcrumb-categories', ['category' => $category])
        @endif
        @if (isset($thread) && $thread)
            <li class=""><a href="{{ Forum::route('thread.show', $thread) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $thread->title }}</a></li>
        @endif
        @if (isset($breadcrumbs_append) && count($breadcrumbs_append) > 0)
            @foreach ($breadcrumbs_append as $breadcrumb)
                <li class="text-gray-900 dark:text-gray-100">{{ $breadcrumb }}</li>
            @endforeach
        @endif
    </ol>
</nav>
