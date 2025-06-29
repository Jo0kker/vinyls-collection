@php
    $user = $post->author;
    $vinylCount = $user ? $user->vinylCollections()->count() : 0;
    $userRole = $user && $user->roles->first() ? $user->roles->first()->name : 'Membre';
@endphp

<div @if (!$post->trashed())id="post-{{ $post->sequence }}"@endif
    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 mb-6 rounded-lg shadow-sm {{ $post->trashed() || $thread->trashed() ? 'opacity-50' : '' }}"
    :class="{ 'border-blue-500 dark:border-blue-400': state.selectedPosts.includes({{ $post->id }}) }">
    
    <div class="flex">
        <!-- Avatar et informations utilisateur -->
        <div class="flex-shrink-0 p-4 bg-gray-50 dark:bg-gray-700 rounded-l-lg">
            <div class="text-center w-32">
                <!-- Avatar -->
                <div class="mb-3">
                    @if($user && $user->avatar)
                        <img src="{{ $user->avatar }}" alt="{{ $post->authorName }}" 
                             class="w-16 h-16 rounded-full mx-auto border-2 border-gray-200 dark:border-gray-600">
                    @else
                        <div class="w-16 h-16 rounded-full mx-auto bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl">
                            {{ strtoupper(substr($post->authorName, 0, 2)) }}
                        </div>
                    @endif
                </div>
                
                <!-- Nom d'utilisateur -->
                <div class="font-semibold text-gray-900 dark:text-white text-sm mb-1">
                    {{ $post->authorName }}
                </div>
                
                <!-- Rôle -->
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full">
                        {{ $userRole }}
                    </span>
                </div>
                
                <!-- Nombre de vinyles -->
                <div class="text-xs text-gray-600 dark:text-gray-300 mb-2">
                    <div class="flex items-center justify-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                        </svg>
                        <span>{{ $vinylCount }} vinyles</span>
                    </div>
                </div>
                
                <!-- Date du post -->
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    @include ('forum::partials.timestamp', ['carbon' => $post->created_at])
                </div>
            </div>
        </div>
        
        <!-- Contenu du post -->
        <div class="flex-1 p-4 flex flex-col min-h-48">
            <!-- Header avec numéro de post et actions -->
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-2">
                    @if (!isset($single) || !$single)
                        <a href="{{ Forum::route('thread.show', $post) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
                            #{{ $post->sequence }}
                        </a>
                        @if ($post->hasBeenUpdated())
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                ({{ trans('forum::general.last_updated') }} @include ('forum::partials.timestamp', ['carbon' => $post->updated_at]))
                            </span>
                        @endif
                    @endif
                </div>
                
                @if (!isset($single) || !$single)
                    <div class="flex items-center gap-2">
                        @if ($post->sequence != 1)
                            @can ('deletePosts', $post->thread)
                                @can ('delete', $post)
                                    <input type="checkbox" name="posts[]" :value="{{ $post->id }}" v-model="state.selectedPosts" 
                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                @endcan
                            @endcan
                        @endif
                    </div>
                @endif
            </div>
            
            <!-- Citation si c'est une réponse -->
            @if ($post->parent !== null)
                <div class="mb-4">
                    @include ('forum::post.partials.quote', ['post' => $post->parent])
                </div>
            @endif
            
            <!-- Contenu du post - prend l'espace disponible -->
            <div class="prose dark:prose-invert max-w-none flex-grow">
                @if ($post->trashed())
                    @can ('viewTrashedPosts')
                        <div class="text-gray-500">
                            @if(strip_tags($post->content) !== $post->content)
                                {{-- Le contenu contient du HTML, on l'affiche directement --}}
                                {!! nl2br($post->content) !!}
                            @else
                                {{-- Pas de HTML, on utilise le rendu par défaut --}}
                                {!! Forum::render($post->content) !!}
                            @endif
                        </div>
                    @endcan
                    <div class="mt-2">
                        <x-forum::badge type="danger">{{ trans('forum::general.deleted') }}</x-forum::badge>
                    </div>
                @else
                    @if(strip_tags($post->content) !== $post->content)
                        {{-- Le contenu contient du HTML, on l'affiche directement --}}
                        {!! nl2br($post->content) !!}
                    @else
                        {{-- Pas de HTML, on utilise le rendu par défaut --}}
                        {!! Forum::render($post->content) !!}
                    @endif
                @endif
            </div>
            
            <!-- Actions du post - toujours en bas -->
            @if (!isset($single) || !$single)
                <div class="flex items-center gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 mt-auto">
                    @if (!$post->trashed())
                        <a href="{{ Forum::route('post.show', $post) }}" 
                           class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.102m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                            {{ trans('forum::general.permalink') }}
                        </a>
                        
                        @can ('reply', $post->thread)
                            <a href="{{ Forum::route('post.create', $post) }}" 
                               class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                </svg>
                                {{ trans('forum::general.reply') }}
                            </a>
                        @endcan
                        
                        @can ('edit', $post)
                            <a href="{{ Forum::route('post.edit', $post) }}" 
                               class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                {{ trans('forum::general.edit') }}
                            </a>
                        @endcan
                        
                        @if ($post->sequence != 1)
                            @can ('deletePosts', $post->thread)
                                @can ('delete', $post)
                                    <a href="{{ Forum::route('post.confirm-delete', $post) }}" 
                                       class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        {{ trans('forum::general.delete') }}
                                    </a>
                                @endcan
                            @endcan
                        @endif
                    @else
                        @can ('restorePosts', $post->thread)
                            @can ('restore', $post)
                                <a href="{{ Forum::route('post.confirm-restore', $post) }}" 
                                   class="text-sm text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    {{ trans('forum::general.restore') }}
                                </a>
                            @endcan
                        @endcan
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
