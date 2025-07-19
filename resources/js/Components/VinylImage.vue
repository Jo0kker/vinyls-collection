<template>
    <div :class="containerClass">
        <img v-if="!imageError && src" 
             :src="src" 
             :alt="alt"
             :class="imageClass"
             @error="handleImageError"
             @load="handleImageLoad">
        
        <!-- Image par défaut avec design vinyle -->
        <div v-else :class="placeholderClass">
            <svg :class="iconClass" fill="currentColor" viewBox="0 0 24 24">
                <!-- Vinyle design -->
                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="1" opacity="0.6"/>
                <circle cx="12" cy="12" r="7" fill="none" stroke="currentColor" stroke-width="0.5" opacity="0.4"/>
                <circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="0.5" opacity="0.4"/>
                <circle cx="12" cy="12" r="1.5" fill="currentColor"/>
                
                <!-- Lignes de sillons -->
                <circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="0.3" opacity="0.3"/>
                <circle cx="12" cy="12" r="6" fill="none" stroke="currentColor" stroke-width="0.3" opacity="0.3"/>
                <circle cx="12" cy="12" r="5" fill="none" stroke="currentColor" stroke-width="0.3" opacity="0.3"/>
                
                <!-- Reflet -->
                <path d="M8 8 L16 16" stroke="currentColor" stroke-width="0.5" opacity="0.2"/>
            </svg>
            
            <!-- Texte optionnel -->
            <div v-if="showPlaceholderText" class="mt-1 text-xs text-center opacity-60">
                {{ placeholderText }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    src: {
        type: String,
        default: null
    },
    alt: {
        type: String,
        default: 'Pochette de vinyle'
    },
    size: {
        type: String,
        default: 'md', // xs, sm, md, lg, xl
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    showPlaceholderText: {
        type: Boolean,
        default: false
    },
    placeholderText: {
        type: String,
        default: 'Pas d\'image'
    }
});

const imageError = ref(false);

const handleImageError = () => {
    imageError.value = true;
};

const handleImageLoad = () => {
    imageError.value = false;
};

// Classes réactives basées sur la taille
const sizeClasses = {
    xs: {
        container: 'w-8 h-8',
        image: 'w-8 h-8',
        placeholder: 'w-8 h-8',
        icon: 'w-6 h-6'
    },
    sm: {
        container: 'w-12 h-12',
        image: 'w-12 h-12',
        placeholder: 'w-12 h-12',
        icon: 'w-8 h-8'
    },
    md: {
        container: 'w-16 h-16',
        image: 'w-16 h-16',
        placeholder: 'w-16 h-16',
        icon: 'w-12 h-12'
    },
    lg: {
        container: 'w-24 h-24',
        image: 'w-24 h-24',
        placeholder: 'w-24 h-24',
        icon: 'w-16 h-16'
    },
    xl: {
        container: 'w-32 h-32',
        image: 'w-32 h-32',
        placeholder: 'w-32 h-32',
        icon: 'w-20 h-20'
    }
};

const containerClass = `${sizeClasses[props.size].container} rounded-lg overflow-hidden flex-shrink-0`;
const imageClass = `${sizeClasses[props.size].image} object-cover`;
const placeholderClass = `${sizeClasses[props.size].placeholder} bg-gradient-to-br from-purple-100 to-blue-100 dark:from-purple-900 dark:to-blue-900 rounded-lg flex flex-col items-center justify-center text-purple-600 dark:text-purple-400`;
const iconClass = `${sizeClasses[props.size].icon}`;
</script>