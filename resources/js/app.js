import '../css/app.css';
import './bootstrap';
import 'vue3-toastify/dist/index.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
// Ziggy removed for now, since vendor/ does not exist in the build stage
// import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin);

        // If you want Ziggy later, we can wire it with the npm package instead of vendor/
        // vueApp.use(ZiggyVue, Ziggy);

        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
