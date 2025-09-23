<template>
    <Editor
        :api-key="apiKey"
        :model-value="modelValue"
        @update:model-value="$emit('update:modelValue', $event)"
        :init="editorConfig"
        :disabled="disabled"
    />
</template>

<script setup>
import Editor from '@tinymce/tinymce-vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    height: {
        type: Number,
        default: 300
    },
    apiKey: {
        type: String,
        default: 'xb69kjxdofd203o6ryw4oauvnhxdwjxyyexx17oqmww6s5dl' // Utilise une version gratuite
    }
});

const emit = defineEmits(['update:modelValue']);

const editorConfig = {
    height: props.height,
    menubar: false,
    skin: 'oxide-dark',
    content_css: 'dark',
    language: 'fr_FR',
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    image_advtab: true,
    image_caption: true,
    image_description: false,
    media_live_embeds: true,
    toolbar: 'undo redo | blocks | ' +
        'bold italic backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'link image media | removeformat | help',
    content_style: `
        body { 
            font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; 
            font-size: 14px;
            background-color: #1f2937;
            color: #f9fafb;
        }
        body.mce-content-body {
            background-color: #1f2937;
            color: #f9fafb;
        }
    `,
    setup: function (editor) {
        editor.on('init', function () {
            editor.getContainer().style.transition = "border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out";
        });

        // Bloquer l'insertion de vidéos Dailymotion
        editor.on('BeforeSetContent', function(e) {
            if (e.content && e.content.includes('dailymotion.com')) {
                e.preventDefault();
                e.stopPropagation();
                
                editor.windowManager.alert('❌ Dailymotion non supporté\n\nCette plateforme vidéo n\'est pas prise en charge en raison de problèmes d\'autoplay.\n\nVeuillez utiliser YouTube à la place.');
                return false;
            }
        });

        // Nettoyage global des iframes Dailymotion
        const cleanupInterval = setInterval(() => {
            const iframes = document.querySelectorAll('iframe[src*="dailymotion.com"]');
            iframes.forEach(iframe => iframe.remove());
        }, 1000);

        editor.on('remove', function() {
            clearInterval(cleanupInterval);
        });
    }
};
</script>
