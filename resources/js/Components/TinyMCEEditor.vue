<template>
    <textarea
        :id="editorId"
        v-model="localValue"
        :disabled="disabled"
    ></textarea>
</template>

<script setup>
/* Import TinyMCE */
import tinymce from 'tinymce';

/* Import TinyMCE theme, icons and model - MUST use .min.js files */
import 'tinymce/icons/default/icons.min.js';
import 'tinymce/themes/silver/theme.min.js';
import 'tinymce/models/dom/model.min.js';

/* Import skins CSS */
import 'tinymce/skins/ui/oxide-dark/skin.css';
import 'tinymce/skins/content/dark/content.css';

// Import des plugins - MUST use plugin.min.js files
import 'tinymce/plugins/advlist/plugin.min.js';
import 'tinymce/plugins/autolink/plugin.min.js';
import 'tinymce/plugins/lists/plugin.min.js';
import 'tinymce/plugins/link/plugin.min.js';
import 'tinymce/plugins/image/plugin.min.js';
import 'tinymce/plugins/charmap/plugin.min.js';
import 'tinymce/plugins/preview/plugin.min.js';
import 'tinymce/plugins/anchor/plugin.min.js';
import 'tinymce/plugins/searchreplace/plugin.min.js';
import 'tinymce/plugins/visualblocks/plugin.min.js';
import 'tinymce/plugins/code/plugin.min.js';
import 'tinymce/plugins/fullscreen/plugin.min.js';
import 'tinymce/plugins/insertdatetime/plugin.min.js';
import 'tinymce/plugins/media/plugin.min.js';
import 'tinymce/plugins/table/plugin.min.js';
// import 'tinymce/plugins/help/plugin.min.js'; // Retiré car pose des problèmes de chemins
import 'tinymce/plugins/wordcount/plugin.min.js';

import { ref, onMounted, onUnmounted, watch } from 'vue';

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
    }
});

const emit = defineEmits(['update:modelValue']);

const editorId = `tinymce-${Math.random().toString(36).substr(2, 9)}`;
const localValue = ref(props.modelValue);
let editorInstance = null;

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
    if (newValue !== localValue.value) {
        localValue.value = newValue;
        if (editorInstance) {
            editorInstance.setContent(newValue || '');
        }
    }
});

onMounted(() => {
    tinymce.init({
        selector: `#${editorId}`,
        height: props.height,
        menubar: false,
        skin: false,
        content_css: false,
        promotion: false,
        branding: false,
        license_key: 'gpl',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'link image media | removeformat',
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
            editorInstance = editor;

            editor.on('init', function () {
                editor.getContainer().style.transition = "border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out";
                editor.getContainer().style.visibility = "visible"; // Forcer l'affichage
                editor.setContent(props.modelValue || '');
            });

            editor.on('input change', function () {
                const content = editor.getContent();
                localValue.value = content;
                emit('update:modelValue', content);
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
    });
});

onUnmounted(() => {
    if (editorInstance) {
        tinymce.remove(`#${editorId}`);
        editorInstance = null;
    }
});
</script>
