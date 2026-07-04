import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import UApp from '@nuxt/ui/components/App.vue';
import ui from '@nuxt/ui/vue-plugin';
import { createApp, h } from 'vue';

const savedAppearance = localStorage.getItem('nuxt-ui-appearance') || 'light';

document.documentElement.classList.toggle('dark', savedAppearance === 'dark');
document.documentElement.classList.toggle('light', savedAppearance !== 'dark');

createInertiaApp({
    resolve: (name) => {
        const pages = {
            ...import.meta.glob('./Pages/**/*.vue', { eager: true }),
            ...import.meta.glob('./pages/**/*.vue', { eager: true }),
        };

        return pages[`./Pages/${name}.vue`] || pages[`./pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({
            render() {
                return h(UApp, null, {
                    default: () => h(App, props),
                });
            },
        });

        vueApp.use(plugin).use(ui).mount(el);
    },
});
