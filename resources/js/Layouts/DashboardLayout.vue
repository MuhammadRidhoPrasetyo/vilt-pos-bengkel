<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { useAppConfig } from '#imports';
import { useToast } from '@nuxt/ui/composables';
import { computed, ref, watch } from 'vue';
import { usePermission } from '../composables/usePermission';

defineProps({
    title: {
        type: String,
        default: 'Beranda',
    },
    panelId: {
        type: String,
        default: 'default',
    },
    navbarAction: {
        type: Object,
        default: null,
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

const { can, canAny } = usePermission();

const teams = computed(() => {
    const list = [
        {
            label: 'POS Bengkel',
            avatar: {
                icon: 'i-lucide-badge-dollar-sign',
            },
        },
    ];

    if (canAny(['roles.view', 'permissions.view', 'users.view'])) {
        list.push({
            label: 'Manajemen Akses',
            avatar: {
                icon: 'i-lucide-shield-check',
            },
        });
    }

    return list;
});

const selectedTeam = ref({
    label: 'POS Bengkel',
    avatar: {
        icon: 'i-lucide-badge-dollar-sign',
    },
});

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
            label: 'Profil',
            icon: 'i-lucide-user',
        },
        {
            label: 'Pengaturan',
            icon: 'i-lucide-settings',
        },
    ],
    [
        {
            label: 'Tema',
            icon: 'i-lucide-palette',
            children: [
                {
                    label: 'Warna Utama',
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
                    label: 'Warna Netral',
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
            label: 'Tampilan',
            icon: 'i-lucide-sun-moon',
            children: [
                {
                    label: 'Terang',
                    icon: 'i-lucide-sun',
                    type: 'checkbox',
                    checked: appearance.value === 'light',
                    onSelect: (event) => {
                        event.preventDefault();
                        setAppearance('light');
                    },
                },
                {
                    label: 'Gelap',
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
            label: 'Keluar',
            icon: 'i-lucide-log-out',
            onSelect: () => router.post('/logout'),
        },
    ],
]);

const currentPath = computed(() => page.url?.split('?')[0] || '/dashboard');

const navigateTo = (path) => {
    open.value = false;
    router.visit(path);
};

const links = computed(() => {
    const navItems = [];

    // 1. Beranda
    if (canAny(['dashboard.view', 'pos.view', 'services.view'])) {
        navItems.push({
            label: 'Beranda',
            icon: 'i-lucide-house',
            active: currentPath.value === '/dashboard',
            onSelect: () => navigateTo('/dashboard'),
        });
    }

    // 2. POS Penjualan
    if (canAny(['pos.view', 'transactions.view'])) {
        navItems.push({
            label: 'POS Penjualan',
            icon: 'i-lucide-shopping-cart',
            active: currentPath.value.startsWith('/transactions'),
            onSelect: () => navigateTo('/transactions'),
        });
    }

    // 3. Servis / SPK
    if (canAny(['services.view', 'work-orders.view'])) {
        navItems.push({
            label: 'Servis / SPK',
            icon: 'i-lucide-wrench',
            active: currentPath.value.startsWith('/services'),
            onSelect: () => navigateTo('/services'),
        });
    }

    // 4. Katalog & Stok
    const catalogChildren = [];
    if (can('products.view')) {
        catalogChildren.push({
            label: 'Semua Produk',
            onSelect: () => navigateTo('/products'),
        });
    }
    if (can('product-categories.view')) {
        catalogChildren.push({
            label: 'Kategori Produk',
            onSelect: () => navigateTo('/product-categories'),
        });
    }
    if (can('product-stocks.view')) {
        catalogChildren.push({
            label: 'Stok Barang',
            onSelect: () => navigateTo('/product-stocks'),
        });
    }
    if (can('purchases.view')) {
        catalogChildren.push({
            label: 'Pembelian / Restok',
            onSelect: () => navigateTo('/purchases'),
        });
    }
    if (catalogChildren.length > 0) {
        navItems.push({
            label: 'Katalog & Stok',
            icon: 'i-lucide-package',
            active: currentPath.value.startsWith('/products')
                || currentPath.value.startsWith('/product-categories')
                || currentPath.value.startsWith('/product-stocks')
                || currentPath.value.startsWith('/purchases'),
            defaultOpen: currentPath.value.startsWith('/products')
                || currentPath.value.startsWith('/product-categories')
                || currentPath.value.startsWith('/product-stocks')
                || currentPath.value.startsWith('/purchases'),
            type: 'trigger',
            children: catalogChildren,
        });
    }

    // 5. Gudang & Logistik
    const warehouseChildren = [];
    if (can('warehouses.view')) {
        warehouseChildren.push({
            label: 'Daftar Warehouse',
            onSelect: () => navigateTo('/warehouses'),
        });
    }
    if (can('warehouse-locations.view')) {
        warehouseChildren.push({
            label: 'Lokasi / Rak Warehouse',
            onSelect: () => navigateTo('/warehouse-locations'),
        });
    }
    if (can('stock-adjustments.view')) {
        warehouseChildren.push({
            label: 'Stock Adjustment',
            onSelect: () => navigateTo('/stock-adjustments'),
        });
    }
    if (can('stock-transfers.view')) {
        warehouseChildren.push({
            label: 'Stock Transfer',
            onSelect: () => navigateTo('/stock-transfers'),
        });
    }
    if (can('stock-opnames.view')) {
        warehouseChildren.push({
            label: 'Stock Opname',
            onSelect: () => navigateTo('/stock-opnames'),
        });
    }
    if (warehouseChildren.length > 0) {
        navItems.push({
            label: 'Gudang & Logistik',
            icon: 'i-lucide-warehouse',
            active: currentPath.value.startsWith('/warehouses')
                || currentPath.value.startsWith('/warehouse-locations')
                || currentPath.value.startsWith('/stock-adjustments')
                || currentPath.value.startsWith('/stock-transfers')
                || currentPath.value.startsWith('/stock-opnames'),
            defaultOpen: currentPath.value.startsWith('/warehouses')
                || currentPath.value.startsWith('/warehouse-locations')
                || currentPath.value.startsWith('/stock-adjustments')
                || currentPath.value.startsWith('/stock-transfers')
                || currentPath.value.startsWith('/stock-opnames'),
            type: 'trigger',
            children: warehouseChildren,
        });
    }

    // 6. Keuangan & Kas
    const financeChildren = [];
    if (can('cash-flows.view')) {
        financeChildren.push({
            label: 'Arus Kas (Cash Flow)',
            onSelect: () => navigateTo('/cash-flows'),
        });
    }
    if (can('cash-flow-categories.view')) {
        financeChildren.push({
            label: 'Kategori Arus Kas',
            onSelect: () => navigateTo('/cash-flow-categories'),
        });
    }
    if (can('payments.view')) {
        financeChildren.push({
            label: 'Metode Pembayaran',
            onSelect: () => navigateTo('/payments'),
        });
    }
    if (financeChildren.length > 0) {
        navItems.push({
            label: 'Keuangan & Kas',
            icon: 'i-lucide-wallet',
            active: currentPath.value.startsWith('/cash-flows')
                || currentPath.value.startsWith('/cash-flow-categories')
                || currentPath.value.startsWith('/payments'),
            defaultOpen: currentPath.value.startsWith('/cash-flows')
                || currentPath.value.startsWith('/cash-flow-categories')
                || currentPath.value.startsWith('/payments'),
            type: 'trigger',
            children: financeChildren,
        });
    }

    // 7. Mitra & Kontak
    const partnerChildren = [];
    if (can('partners.view')) {
        partnerChildren.push({
            label: 'Mitra / Pelanggan / Supplier',
            onSelect: () => navigateTo('/partners'),
        });
    }
    if (can('partner-roles.view')) {
        partnerChildren.push({
            label: 'Role Partner',
            onSelect: () => navigateTo('/partner-roles'),
        });
    }
    if (partnerChildren.length > 0) {
        navItems.push({
            label: 'Mitra & Kontak',
            icon: 'i-lucide-users',
            active: currentPath.value.startsWith('/partners')
                || currentPath.value.startsWith('/partner-roles'),
            defaultOpen: currentPath.value.startsWith('/partners')
                || currentPath.value.startsWith('/partner-roles'),
            type: 'trigger',
            children: partnerChildren,
        });
    }

    // 8. Pengaturan & System
    const settingChildren = [];
    if (can('stores.view')) {
        settingChildren.push({
            label: 'Cabang Toko',
            onSelect: () => navigateTo('/stores'),
        });
    }
    if (can('brands.view')) {
        settingChildren.push({
            label: 'Merek / Brand',
            onSelect: () => navigateTo('/brands'),
        });
    }
    if (can('units.view')) {
        settingChildren.push({
            label: 'Satuan Barang',
            onSelect: () => navigateTo('/units'),
        });
    }
    if (can('discount-types.view')) {
        settingChildren.push({
            label: 'Jenis Diskon',
            onSelect: () => navigateTo('/discount-types'),
        });
    }
    if (can('users.view')) {
        settingChildren.push({
            label: 'Pengguna Sistem',
            onSelect: () => navigateTo('/users'),
        });
    }
    if (can('roles.view')) {
        settingChildren.push({
            label: 'Peran / Role',
            onSelect: () => navigateTo('/roles'),
        });
    }
    if (can('permissions.view')) {
        settingChildren.push({
            label: 'Hak Akses / Permission',
            onSelect: () => navigateTo('/permissions'),
        });
    }
    if (can('printers.view')) {
        settingChildren.push({
            label: 'Printer Toko',
            onSelect: () => navigateTo('/printers'),
        });
    }
    if (can('database-backup.view')) {
        settingChildren.push({
            label: 'Backup / Restore Database',
            onSelect: () => navigateTo('/settings/database'),
        });
    }
    if (settingChildren.length > 0) {
        navItems.push({
            label: 'Pengaturan & System',
            icon: 'i-lucide-settings',
            active: currentPath.value.startsWith('/stores')
                || currentPath.value.startsWith('/brands')
                || currentPath.value.startsWith('/units')
                || currentPath.value.startsWith('/discount-types')
                || currentPath.value.startsWith('/users')
                || currentPath.value.startsWith('/roles')
                || currentPath.value.startsWith('/permissions')
                || currentPath.value.startsWith('/printers')
                || currentPath.value.startsWith('/settings/database'),
            defaultOpen: currentPath.value.startsWith('/stores')
                || currentPath.value.startsWith('/brands')
                || currentPath.value.startsWith('/units')
                || currentPath.value.startsWith('/discount-types')
                || currentPath.value.startsWith('/users')
                || currentPath.value.startsWith('/roles')
                || currentPath.value.startsWith('/permissions')
                || currentPath.value.startsWith('/printers')
                || currentPath.value.startsWith('/settings/database'),
            type: 'trigger',
            children: settingChildren,
        });
    }

    return [navItems];
});

const searchGroups = computed(() => [
    {
        id: 'links',
        label: 'Navigasi',
        items: links.value.flat(),
    },
]);

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
            name: 'Sistem',
            avatar: {
                icon: 'i-lucide-bell',
            },
        },
        body: 'Modul manajemen akses siap digunakan.',
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
        body: 'Layout dashboard digunakan bersama di semua halaman.',
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
    <Head :title="title" />
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
                            <UTooltip text="Portal Menu" :shortcuts="['P']">
                                <UButton color="neutral" variant="ghost" square @click="navigateTo('/home')">
                                    <UIcon name="i-lucide-layout-grid" class="size-5 shrink-0" />
                                </UButton>
                            </UTooltip>

                            <UTooltip text="Notifikasi" :shortcuts="['N']">
                                <UButton color="neutral" variant="ghost" square @click="notificationsOpen = true">
                                    <UChip color="error" inset>
                                        <UIcon name="i-lucide-bell" class="size-5 shrink-0" />
                                    </UChip>
                                </UButton>
                            </UTooltip>

                            <UButton
                                v-if="navbarAction"
                                :icon="navbarAction.icon"
                                :label="navbarAction.label"
                                :color="navbarAction.color || 'primary'"
                                :variant="navbarAction.variant || 'solid'"
                                @click="navbarAction.onClick"
                            />
                        </slot>
                    </template>
                </UDashboardNavbar>

            </template>

            <template #body>
                <slot />
            </template>
        </UDashboardPanel>

        <USlideover v-model:open="notificationsOpen" title="Notifikasi">
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
