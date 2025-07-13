<div class="bg-gray-100 dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-600 mb-2">
    <div class="p-6">
        <div class="flex justify-between flex-row-reverse mb-2">
            <span>
                <a href="{{ Forum::route('thread.show', $post) }}" class="text-blue-600 dark:text-blue-400">#{{ $post->sequence }}</a>
            </span>
            <div>
                <strong class="text-gray-900 dark:text-gray-100">{{ $post->authorName }}</strong> <span class="text-gray-600 dark:text-gray-300">{{ $post->posted }}</span>
            </div>
        </div>
        {!! \Illuminate\Support\Str::limit(Forum::render($post->content)) !!}
    </div>
</div>
