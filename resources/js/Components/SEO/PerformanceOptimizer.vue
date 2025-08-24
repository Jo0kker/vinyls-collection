<template>
    <Head>
        <!-- Preconnect to external domains -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="preconnect" href="https://embed.tawk.to">
        
        <!-- Resource hints for better performance -->
        <link rel="dns-prefetch" href="//fonts.bunny.net">
        <link rel="dns-prefetch" href="//embed.tawk.to">
        
        <!-- Favicon optimizations -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="apple-touch-icon" sizes="180x180" href="/favicon.ico">
        
        <!-- Critical rendering optimization -->
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="format-detection" content="telephone=no">
        
        <!-- Performance hints -->
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
    </Head>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

// Optimisations au chargement de la page
onMounted(() => {
    // Lazy loading des images qui ne sont pas critiques
    if ('loading' in HTMLImageElement.prototype) {
        const images = document.querySelectorAll('img[data-src]');
        images.forEach(img => {
            img.src = img.dataset.src;
        });
    }
    
    // Web Vitals tracking si disponible
    if (typeof gtag !== 'undefined') {
        // Core Web Vitals tracking code could go here
    }
});
</script>