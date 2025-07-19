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
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | ' +
        'bold italic backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | help',
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
    }
};
</script>
