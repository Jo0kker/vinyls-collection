import '../css/app.css';
import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

document.addEventListener('DOMContentLoaded', () => {
    createInertiaApp({
        title: (title) => `${title} - ${appName}`,
        resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
        setup({ el, App, props, plugin }) {
            const app = createApp({ render: () => h(App, props) });
            app.use(plugin);
            app.use(ZiggyVue);
            app.mount(el);
        },
        progress: {
            color: '#2563eb',
            showSpinner: true,
        },
        finish: (event) => {
            // Hide loading overlay when page is loaded
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.3s ease';
                setTimeout(() => overlay.remove(), 300);
            }
        },
    });
});
