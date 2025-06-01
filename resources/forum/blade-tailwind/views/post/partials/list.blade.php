<div @if (!$post->trashed())id="post-{{ $post->sequence }}"@endif
    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 mb-4 rounded-lg shadow-sm {{ $post->trashed() || $thread->trashed() ? 'opacity-50' : '' }}"
    :class="{ 'border-emerald-500': state.selectedPosts.includes({{ $post->id }}) }">
    <div class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 px-6 py-4 flex justify-between flex-row-reverse rounded-t-lg">
        @if (!isset($single) || !$single)
            <span class="float-end">
                <a href="{{ Forum::route('thread.show', $post) }}" class="text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition">#{{ $post->sequence }}</a>
                @if ($post->sequence != 1)
                    @can ('deletePosts', $post->thread)
                        @can ('delete', $post)
                            <input type="checkbox" name="posts[]" :value="{{ $post->id }}" v-model="state.selectedPosts" class="ml-2" />
                        @endcan
                    @endcan
                @endif
            </span>
        @endif

        <div>
            <span class="font-medium text-gray-900 dark:text-white">{{ $post->authorName }}</span>
            <span class="text-gray-500 dark:text-gray-400">
                @include ('forum::partials.timestamp', ['carbon' => $post->created_at])
                @if ($post->hasBeenUpdated())
                    ({{ trans('forum::general.last_updated') }} @include ('forum::partials.timestamp', ['carbon' => $post->updated_at]))
                @endif
            </span>
        </div>
    </div>
    <div class="p-6 text-gray-800 dark:text-gray-200">
        @if ($post->parent !== null)
            @include ('forum::post.partials.quote', ['post' => $post->parent])
        @endif

        @if ($post->trashed())
            @can ('viewTrashedPosts')
                {!! Forum::render($post->content) !!}
                <br>
            @endcan
            <x-forum::badge type="danger">{{ trans('forum::general.deleted') }}</x-forum::badge>
        @else
            {!! Forum::render($post->content) !!}
        @endif

        @if (!isset($single) || !$single)
            <div class="flex items-center gap-4 justify-end mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                @if (!$post->trashed())
                    <a href="{{ Forum::route('post.show', $post) }}" class="text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition underline">{{ trans('forum::general.permalink') }}</a>
                    @if ($post->sequence != 1)
                        @can ('deletePosts', $post->thread)
                            @can ('delete', $post)
                                <a href="{{ Forum::route('post.confirm-delete', $post) }}" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition">{{ trans('forum::general.delete') }}</a>
                            @endcan
                        @endcan
                    @endif
                    @can ('edit', $post)
                        <a href="{{ Forum::route('post.edit', $post) }}" class="text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition">{{ trans('forum::general.edit') }}</a>
                    @endcan
                    @can ('reply', $post->thread)
                        <a href="{{ Forum::route('post.create', $post) }}" class="text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition">{{ trans('forum::general.reply') }}</a>
                    @endcan
                @else
                    @can ('restorePosts', $post->thread)
                        @can ('restore', $post)
                            <a href="{{ Forum::route('post.confirm-restore', $post) }}" class="text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition">{{ trans('forum::general.restore') }}</a>
                        @endcan
                    @endcan
                @endif
            </div>
        @endif
    </div>
</div>
