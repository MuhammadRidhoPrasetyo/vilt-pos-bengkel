<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { useAppConfig } from '#imports';
import { useToast } from '@nuxt/ui/composables';
import { computed, ref, watch } from 'vue';

defineProps({
    title: {
        type: String,
        default: 'Home',
    },
    panelId: {
        type: String,
        default: 'default',
    },
});

const page = usePage();
const appConfig = useAppConfig();
const toast = useToast();
const open = ref(false);
const notificationsOpen = ref(false);
const primaryColor = ref('green');
const neutralColor = ref('zinc');
const appearance = ref('light');

const applyTheme = () => {
    appConfig.ui.colors.primary = primaryColor.value;
    appConfig.ui.colors.neutral = neutralColor.value;

    document.documentElement.classList.toggle('dark', appearance.value === 'dark');
    document.documentElement.classList.toggle('light', appearance.value !== 'dark');

    localStorage.setItem('nuxt-ui-primary', primaryColor.value);
    localStorage.setItem('nuxt-ui-neutral', neutralColor.value);
    localStorage.setItem('nuxt-ui-appearance', appearance.value);
};

if (typeof localStorage !== 'undefined') {
    primaryColor.value = localStorage.getItem('nuxt-ui-primary') || 'green';
    neutralColor.value = localStorage.getItem('nuxt-ui-neutral') || 'zinc';
    appearance.value = localStorage.getItem('nuxt-ui-appearance') || 'light';

    applyTheme();
}

const setPrimaryColor = (color) => {
    primaryColor.value = color;
    applyTheme();
};

const setNeutralColor = (color) => {
    neutralColor.value = color;
    applyTheme();
};

const setAppearance = (value) => {
    appearance.value = value;
    applyTheme();
};

const teams = ref([
    {
        label: 'POS Bengkel',
        avatar: {
            icon: 'i-lucide-badge-dollar-sign',
        },
    },
    {
        label: 'Access Management',
        avatar: {
            icon: 'i-lucide-shield-check',
        },
    },
]);

const selectedTeam = ref(teams.value[0]);

const teamItems = computed(() => [
    teams.value.map((team) => ({
        ...team,
        onSelect() {
            selectedTeam.value = team;
        },
    })),
]);

const user = computed(() => ({
    name: page.props.auth?.user?.name || 'User',
    avatar: {
        alt: page.props.auth?.user?.name || 'User',
    },
}));

const userItems = computed(() => [
    [
        {
            type: 'label',
            label: user.value.name,
            avatar: user.value.avatar,
        },
    ],
    [
        {
            label: 'Profile',
            icon: 'i-lucide-user',
        },
        {
            label: 'Settings',
            icon: 'i-lucide-settings',
        },
    ],
    [
        {
            label: 'Theme',
            icon: 'i-lucide-palette',
            children: [
                {
                    label: 'Primary',
                    slot: 'chip',
                    chip: primaryColor.value,
                    children: ['red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'].map((color) => ({
                        label: color,
                        chip: color,
                        slot: 'chip',
                        checked: color === primaryColor.value,
                        type: 'checkbox',
                        onSelect: (event) => {
                            event.preventDefault();
                            setPrimaryColor(color);
                        },
                    })),
                },
                {
                    label: 'Neutral',
                    slot: 'chip',
                    chip: neutralColor.value === 'neutral' ? 'old-neutral' : neutralColor.value,
                    children: ['slate', 'gray', 'zinc', 'neutral', 'stone'].map((color) => ({
                        label: color,
                        chip: color === 'neutral' ? 'old-neutral' : color,
                        slot: 'chip',
                        checked: color === neutralColor.value,
                        type: 'checkbox',
                        onSelect: (event) => {
                            event.preventDefault();
                            setNeutralColor(color);
                        },
                    })),
                },
            ],
        },
        {
            label: 'Appearance',
            icon: 'i-lucide-sun-moon',
            children: [
                {
                    label: 'Light',
                    icon: 'i-lucide-sun',
                    type: 'checkbox',
                    checked: appearance.value === 'light',
                    onSelect: (event) => {
                        event.preventDefault();
                        setAppearance('light');
                    },
                },
                {
                    label: 'Dark',
                    icon: 'i-lucide-moon',
                    type: 'checkbox',
                    checked: appearance.value === 'dark',
                    onSelect: (event) => {
                        event.preventDefault();
                        setAppearance('dark');
                    },
                },
            ],
        },
    ],
    [
        {
            label: 'Log out',
            icon: 'i-lucide-log-out',
            onSelect: () => router.post('/logout'),
        },
    ],
]);

const currentPath = computed(() => page.url?.split('?')[0] || '/home');

const navigateTo = (path) => {
    open.value = false;
    router.visit(path);
};

const links = computed(() => [
    [
        {
            label: 'Home',
            icon: 'i-lucide-house',
            active: currentPath.value === '/home',
            onSelect: () => navigateTo('/home'),
        },
        {
            label: 'Master Data',
            icon: 'i-lucide-database',
            onSelect: () => navigateTo('/master-data'),
        },
        {
            label: 'Users',
            icon: 'i-lucide-users',
            active: currentPath.value.startsWith('/users'),
            onSelect: () => navigateTo('/users'),
        },
        {
            label: 'Roles',
            icon: 'i-lucide-shield',
            active: currentPath.value.startsWith('/roles'),
            onSelect: () => navigateTo('/roles'),
        },
        {
            label: 'Permissions',
            icon: 'i-lucide-key-round',
            active: currentPath.value.startsWith('/permissions'),
            onSelect: () => navigateTo('/permissions'),
        },
        {
            label: 'Inbox',
            icon: 'i-lucide-inbox',
            badge: '4',
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: 'Customers',
            icon: 'i-lucide-users',
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: 'Settings',
            icon: 'i-lucide-settings',
            defaultOpen: true,
            type: 'trigger',
            children: [
                {
                    label: 'General',
                    onSelect: () => {
                        open.value = false;
                    },
                },
                {
                    label: 'Members',
                    onSelect: () => {
                        open.value = false;
                    },
                },
                {
                    label: 'Notifications',
                    onSelect: () => {
                        open.value = false;
                    },
                },
                {
                    label: 'Security',
                    onSelect: () => {
                        open.value = false;
                    },
                },
            ],
        },
    ],
    [
        {
            label: 'Feedback',
            icon: 'i-lucide-message-circle',
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: 'Help & Support',
            icon: 'i-lucide-info',
            onSelect: () => {
                open.value = false;
            },
        },
    ],
]);

const searchGroups = computed(() => [
    {
        id: 'links',
        label: 'Go to',
        items: links.value.flat(),
    },
]);

const actionItems = [
    [
        {
            label: 'New user',
            icon: 'i-lucide-user-plus',
            onSelect: () => router.visit('/users/create'),
        },
        {
            label: 'New role',
            icon: 'i-lucide-shield-plus',
            onSelect: () => router.visit('/roles'),
        },
        {
            label: 'New permission',
            icon: 'i-lucide-key-round',
            onSelect: () => router.visit('/permissions'),
        },
    ],
];

const flash = computed(() => page.props.flash || {});

watch(() => flash.value.success, (message) => {
    if (message) {
        toast.add({
            title: 'Berhasil',
            description: message,
            icon: 'i-lucide-circle-check',
            color: 'success',
        });
    }
}, { immediate: true });

watch(() => flash.value.error, (message) => {
    if (message) {
        toast.add({
            title: 'Gagal',
            description: message,
            icon: 'i-lucide-circle-alert',
            color: 'error',
        });
    }
}, { immediate: true });

const notifications = [
    {
        id: 1,
        unread: true,
        date: new Date(Date.now() - 1000 * 60 * 12).toISOString(),
        sender: {
            name: 'System',
            avatar: {
                icon: 'i-lucide-bell',
            },
        },
        body: 'Access management module is ready.',
    },
    {
        id: 2,
        unread: false,
        date: new Date(Date.now() - 1000 * 60 * 130).toISOString(),
        sender: {
            name: 'POS Bengkel',
            avatar: {
                icon: 'i-lucide-badge-dollar-sign',
            },
        },
        body: 'Dashboard layout is shared across pages.',
    },
];

const formatTimeAgo = (date) => {
    const minutes = Math.max(1, Math.round((Date.now() - date.getTime()) / 60000));

    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.round(minutes / 60);

    return `${hours}h ago`;
};
</script>

<template>
    <UDashboardGroup unit="rem">
        <UDashboardSidebar
            id="default"
            v-model:open="open"
            collapsible
            resizable
            class="bg-elevated/25"
            :ui="{ footer: 'lg:border-t lg:border-default' }"
        >
            <template #header="{ collapsed }">
                <UDropdownMenu
                    :items="teamItems"
                    :content="{ align: 'center', collisionPadding: 12 }"
                    :ui="{ content: collapsed ? 'w-40' : 'w-(--reka-dropdown-menu-trigger-width)' }"
                >
                    <UButton
                        v-bind="{
                            ...selectedTeam,
                            label: collapsed ? undefined : selectedTeam?.label,
                            trailingIcon: collapsed ? undefined : 'i-lucide-chevrons-up-down',
                        }"
                        color="neutral"
                        variant="ghost"
                        block
                        :square="collapsed"
                        class="data-[state=open]:bg-elevated"
                        :class="[!collapsed && 'py-2']"
                        :ui="{ trailingIcon: 'text-dimmed' }"
                    />
                </UDropdownMenu>
            </template>

            <template #default="{ collapsed }">
                <UDashboardSearchButton :collapsed="collapsed" class="bg-transparent ring-default" />

                <UNavigationMenu
                    :collapsed="collapsed"
                    :items="links[0]"
                    orientation="vertical"
                    tooltip
                    popover
                />

                <UNavigationMenu
                    :collapsed="collapsed"
                    :items="links[1]"
                    orientation="vertical"
                    tooltip
                    class="mt-auto"
                />
            </template>

            <template #footer="{ collapsed }">
                <UDropdownMenu
                    :items="userItems"
                    :content="{ align: 'center', collisionPadding: 12 }"
                    :ui="{ content: collapsed ? 'w-48' : 'w-(--reka-dropdown-menu-trigger-width)' }"
                >
                    <UButton
                        v-bind="{
                            ...user,
                            label: collapsed ? undefined : user?.name,
                            trailingIcon: collapsed ? undefined : 'i-lucide-chevrons-up-down',
                        }"
                        color="neutral"
                        variant="ghost"
                        block
                        :square="collapsed"
                        class="data-[state=open]:bg-elevated"
                        :ui="{ trailingIcon: 'text-dimmed' }"
                    />

                    <template #chip-leading="{ item }">
                        <div class="inline-flex size-5 shrink-0 items-center justify-center">
                            <span
                                class="size-2 rounded-full bg-(--chip-light) ring ring-bg dark:bg-(--chip-dark)"
                                :style="{
                                    '--chip-light': `var(--color-${item.chip}-500)`,
                                    '--chip-dark': `var(--color-${item.chip}-400)`,
                                }"
                            />
                        </div>
                    </template>
                </UDropdownMenu>
            </template>
        </UDashboardSidebar>

        <UDashboardSearch :groups="searchGroups" />

        <UDashboardPanel :id="panelId">
            <template #header>
                <UDashboardNavbar :title="title" :ui="{ right: 'gap-3' }">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <slot name="navbar-right">
                            <UTooltip text="Notifications" :shortcuts="['N']">
                                <UButton color="neutral" variant="ghost" square @click="notificationsOpen = true">
                                    <UChip color="error" inset>
                                        <UIcon name="i-lucide-bell" class="size-5 shrink-0" />
                                    </UChip>
                                </UButton>
                            </UTooltip>

                            <UDropdownMenu :items="actionItems">
                                <UButton icon="i-lucide-plus" size="md" class="rounded-full" />
                            </UDropdownMenu>
                        </slot>
                    </template>
                </UDashboardNavbar>

            </template>

            <template #body>
                <slot />
            </template>
        </UDashboardPanel>

        <USlideover v-model:open="notificationsOpen" title="Notifications">
            <template #body>
                <button
                    v-for="notification in notifications"
                    :key="notification.id"
                    type="button"
                    class="relative -mx-3 flex w-[calc(100%+1.5rem)] items-center gap-3 rounded-md px-3 py-2.5 text-left hover:bg-elevated/50 first:-mt-3 last:-mb-3"
                >
                    <UChip color="error" :show="!!notification.unread" inset>
                        <UAvatar v-bind="notification.sender.avatar" :alt="notification.sender.name" size="md" />
                    </UChip>

                    <div class="min-w-0 flex-1 text-sm">
                        <p class="flex items-center justify-between gap-3">
                            <span class="truncate font-medium text-highlighted">{{ notification.sender.name }}</span>

                            <time :datetime="notification.date" class="shrink-0 text-xs text-muted">
                                {{ formatTimeAgo(new Date(notification.date)) }}
                            </time>
                        </p>

                        <p class="truncate text-dimmed">
                            {{ notification.body }}
                        </p>
                    </div>
                </button>
            </template>
        </USlideover>
    </UDashboardGroup>
</template>
