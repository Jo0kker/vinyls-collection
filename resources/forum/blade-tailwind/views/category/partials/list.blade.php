<div class="my-4">
    <div class="bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow rounded-md relative">
        <div class="flex flex-col md:items-start md:flex-row md:justify-between md:gap-4 p-6">
            <div class="md:w-3/6 text-center md:text-left">
                <h5 class="text-lg">
                    <a href="{{ Forum::route('category.show', $category) }}" class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400" style="color: {{ $category->color_light_mode }};">{{ $category->title }}</a>
                </h5>
                <p class="text-gray-700 dark:text-gray-300">{{ $category->description }}</p>
            </div>
            <div class="md:w-1/6 flex flex-col items-center gap-1 mt-2 md:mt-0">
                @if ($category->accepts_threads)
                    <x-forum::badge style="background: {{ $category->color_light_mode }};">
                        {{ trans_choice('forum::threads.thread', 2) }}: {{ $category->thread_count }}
                    </x-forum::badge>
                    <x-forum::badge style="background: {{ $category->color_light_mode }};">
                        {{ trans_choice('forum::posts.post', 2) }}: {{ $category->post_count }}
                    </x-forum::badge>
                @endif
            </div>
            <div class="md:w-2/6 text-gray-700 dark:text-gray-300 text-center md:text-right mt-2 md:mt-0">
                @if ($category->accepts_threads)
                    @if ($category->newestThread)
                        <div>
                            <a href="{{ Forum::route('thread.show', $category->newestThread) }}" class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 mr-1">{{ $category->newestThread->title }}</a>
                            @include ('forum::partials.timestamp', ['carbon' => $category->newestThread->created_at])
                        </div>
                    @endif
                    @if ($category->latestActiveThread && $category->latestActiveThread->post_count > 1)
                        <div>
                            <a href="{{ Forum::route('thread.show', $category->latestActiveThread->lastPost) }}" class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 mr-1">Re: {{ $category->latestActiveThread->title }}</a>
                            @include ('forum::partials.timestamp', ['carbon' => $category->latestActiveThread->lastPost->created_at])
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @if ($category->children->count() > 0)
        <div class="subcategories">
            @foreach ($category->children as $subcategory)
                <div class="bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 -mt-1 shadow rounded-b-md">
                    <div class="flex flex-col md:items-start md:flex-row md:justify-between md:gap-4 p-6">
                        <div class="md:w-3/6 text-center md:text-left">
                            <a href="{{ Forum::route('category.show', $subcategory) }}" class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400" style="color: {{ $subcategory->color_light_mode }};">{{ $subcategory->title }}</a>
                            <div class="text-gray-700 dark:text-gray-300">{{ $subcategory->description }}</div>
                        </div>
                        <div class="md:w-1/6 flex flex-col items-center gap-1 mt-2 md:mt-0">
                            <x-forum::badge style="background: {{ $subcategory->color_light_mode }};">
                                {{ trans_choice('forum::threads.thread', 2) }}: {{ $subcategory->thread_count }}
                            </x-forum::badge>
                            <x-forum::badge style="background: {{ $subcategory->color_light_mode }};">
                                {{ trans_choice('forum::posts.post', 2) }}: {{ $subcategory->post_count }}
                            </x-forum::badge>
                        </div>
                        <div class="md:w-2/6 text-gray-700 dark:text-gray-300 md:items-end text-center md:text-right mt-2 md:mt-0">
                            @if ($subcategory->newestThread)
                                <div>
                                    <a href="{{ Forum::route('thread.show', $subcategory->newestThread) }}" class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 mr-1">{{ $subcategory->newestThread->title }}</a>
                                    @include ('forum::partials.timestamp', ['carbon' => $subcategory->newestThread->created_at])
                                </div>
                            @endif
                            @if ($subcategory->latestActiveThread && $subcategory->latestActiveThread->post_count > 1)
                                <div>
                                    <a href="{{ Forum::route('thread.show', $subcategory->latestActiveThread->lastPost) }}" class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 mr-1">Re: {{ $subcategory->latestActiveThread->title }}</a>
                                    @include ('forum::partials.timestamp', ['carbon' => $subcategory->latestActiveThread->lastPost->created_at])
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
