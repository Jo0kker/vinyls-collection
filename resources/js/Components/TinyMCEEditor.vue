<template>
    <textarea
        ref="editorElement"
        :id="`tinymce-${editorId}`"
        v-model="internalValue"
    ></textarea>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';

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

const editorElement = ref(null);
const editorId = Math.random().toString(36).substring(7);
const internalValue = ref(props.modelValue || '');

let tinymceInstance = null;

// Charger TinyMCE depuis CDN
const loadTinyMCE = () => {
    return new Promise((resolve) => {
        if (window.tinymce) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js';
        script.onload = () => resolve();
        document.head.appendChild(script);
    });
};

onMounted(async () => {
    await loadTinyMCE();

    window.tinymce.init({
        target: editorElement.value,
        height: props.height,
        menubar: false,
        skin: 'oxide-dark',
        content_css: 'dark',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'link image media | removeformat',
        setup: function (editor) {
            tinymceInstance = editor;

            editor.on('change keyup', () => {
                const content = editor.getContent();
                internalValue.value = content;
                emit('update:modelValue', content);
            });

            // Bloquer Dailymotion
            editor.on('BeforeSetContent', function(e) {
                if (e.content && e.content.includes('dailymotion.com')) {
                    e.preventDefault();
                    editor.windowManager.alert('❌ Dailymotion non supporté\n\nCette plateforme vidéo n\'est pas prise en charge en raison de problèmes d\'autoplay.\n\nVeuillez utiliser YouTube à la place.');
                    return false;
                }
            });
        }
    });
});

onUnmounted(() => {
    if (tinymceInstance) {
        tinymceInstance.destroy();
        tinymceInstance = null;
    }
});

watch(() => props.modelValue, (newValue) => {
    if (tinymceInstance && newValue !== internalValue.value) {
        tinymceInstance.setContent(newValue || '');
        internalValue.value = newValue;
    }
});
</script>

<style scoped>
/* Pas de CSS spécifique, TinyMCE depuis CDN gère tout */
</style>
