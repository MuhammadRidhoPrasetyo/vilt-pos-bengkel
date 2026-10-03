import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import UApp from '@nuxt/ui/components/App.vue';
import ui from '@nuxt/ui/vue-plugin';
import { createApp, h } from 'vue';

const savedAppearance = localStorage.getItem('nuxt-ui-appearance') || 'light';

document.documentElement.classList.toggle('dark', savedAppearance === 'dark');
document.documentElement.classList.toggle('light', savedAppearance !== 'dark');

const appName = import.meta.env.VITE_APP_NAME || 'POS Bengkel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue');

        return pages[`./Pages/${name}.vue`]();
    },
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({
            render() {
                return h(UApp, null, {
                    default: () => h(App, props),
                });
            },
        });

        vueApp.config.globalProperties.$can = function (permission) {
            const user = this.$page?.props?.auth?.user;
            if (!user) return false;
            const roles = user.roles || [];
            if (roles.includes('owner') || roles.includes('admin') || roles.includes('super-admin')) {
                return true;
            }
            const perms = user.permissions || [];
            if (Array.isArray(permission)) {
                return permission.some((p) => perms.includes(p));
            }
            return perms.includes(permission);
        };

        vueApp.config.globalProperties.$canAny = function (perms = []) {
            const user = this.$page?.props?.auth?.user;
            if (!user) return false;
            const roles = user.roles || [];
            if (roles.includes('owner') || roles.includes('admin') || roles.includes('super-admin')) {
                return true;
            }
            const userPerms = user.permissions || [];
            return perms.some((p) => userPerms.includes(p));
        };

        vueApp.use(plugin).use(ui).mount(el);
    },
});
