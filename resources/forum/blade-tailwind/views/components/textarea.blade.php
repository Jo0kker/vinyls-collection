@php
    $editorId = 'editor_' . Str::random(8);
    $hasEditor = $attributes->get('data-editor', 'true') !== 'false';
@endphp

@if($hasEditor)
    <div class="relative">
        <textarea 
            id="{{ $editorId }}"
            {{ $attributes->merge(['class' => 'px-3 py-1 border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md border shadow-sm']) }}
        >{{ $slot }}</textarea>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof tinymce !== 'undefined') {
                tinymce.init({
                    selector: '#{{ $editorId }}',
                    height: 300,
                    menubar: false,
                    skin: document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide',
                    content_css: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
                    plugins: [
                        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                        'insertdatetime', 'media', 'table', 'help', 'wordcount'
                    ],
                    toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link image | code preview fullscreen',
                    branding: false,
                    setup: function(editor) {
                        // Sync avec le thème dark/light
                        const observer = new MutationObserver(function(mutations) {
                            mutations.forEach(function(mutation) {
                                if (mutation.attributeName === 'class') {
                                    const isDark = document.documentElement.classList.contains('dark');
                                    editor.dom.setStyle(editor.getBody(), 'background-color', isDark ? '#1f2937' : '#ffffff');
                                    editor.dom.setStyle(editor.getBody(), 'color', isDark ? '#e5e7eb' : '#374151');
                                }
                            });
                        });
                        observer.observe(document.documentElement, { attributes: true });
                    }
                });
            }
        });
    </script>
    @endpush
@else
    <textarea {{ $attributes->merge(['class' => 'px-3 py-1 border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md border shadow-sm']) }}>{{ $slot }}</textarea>
@endif
