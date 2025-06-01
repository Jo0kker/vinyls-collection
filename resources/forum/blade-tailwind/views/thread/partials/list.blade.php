<div class="bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700" :class="{ 'ring-1 ring-blue-500': state.selectedThreads.includes({{ $thread->id }}) }">
    <div class="flex flex-col md:items-start md:flex-row md:justify-between md:gap-4 p-6">
        <div class="md:w-3/6 text-center md:text-left">
            <span class="lead">
                <a href="{{ Forum::route('thread.show', $thread) }}" @if (isset($category))style="color: {{ $category->color_light_mode }};"@endif class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition text-lg">{{ $thread->title }}</a>
            </span>
            <br>
            <span class="text-gray-900 dark:text-white">{{ $thread->authorName }}</span>
            <span class="text-gray-700 dark:text-gray-300"> @include ('forum::partials.timestamp', ['carbon' => $thread->created_at])</span>

            @if (!isset($category))
                <br>
                <a href="{{ Forum::route('category.show', $thread->category) }}" class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition">{{ $thread->category->title }}</a>
            @endif
        </div>

        <div class="md:w-2/6 flex flex-wrap justify-center items-center md:justify-end gap-1">
            @if ($thread->pinned)
                <x-forum::badge type="info">{{ trans('forum::threads.pinned') }}</x-forum::badge>
            @endif
            @if ($thread->locked)
                <x-forum::badge type="warning">{{ trans('forum::threads.locked') }}</x-forum::badge>
            @endif
            @if ($thread->userReadStatus !== null && !$thread->trashed())
                <x-forum::badge type="success">{{ trans($thread->userReadStatus) }}</x-forum::badge>
            @endif
            @if ($thread->trashed())
                <x-forum::badge type="danger">{{ trans('forum::general.deleted') }}</x-forum::badge>
            @endif
            <x-forum::badge :style="(isset($category) && $category->color_light_mode) ? 'background: '.$category->color_light_mode .';' : null">
                {{ trans('forum::general.replies') }}:
                {{ $thread->reply_count }}
            </x-forum::badge>
        </div>

        @if ($thread->lastPost)
            <div class="md:w-1/6 flex justify-center md:flex-col md:items-end">
                <a href="{{ Forum::route('thread.show', $thread->lastPost) }}" class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition">{{ trans('forum::posts.view') }} &raquo;</a>
                <div>
                    <span class="text-gray-900 dark:text-white">{{ $thread->lastPost->authorName }}</span>
                    <span class="text-gray-700 dark:text-gray-300"> @include ('forum::partials.timestamp', ['carbon' => $thread->lastPost->created_at])</span>
                </div>
            </div>
        @endif

        @if (isset($category) && isset($selectableThreadIds) && in_array($thread->id, $selectableThreadIds))
            <div class="" style="flex: 0;">
                <input type="checkbox" name="threads[]" :value="{{ $thread->id }}" v-model="state.selectedThreads">
            </div>
        @endif
    </div>
</div>
