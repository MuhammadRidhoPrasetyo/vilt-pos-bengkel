<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { CalendarDate, DateFormatter, getLocalTimeZone, today } from '@internationalized/date';
import { useAppConfig } from '#imports';
import { computed, h, ref, resolveComponent, watch } from 'vue';

const page = usePage();
const appConfig = useAppConfig();
const open = ref(false);
const notificationsOpen = ref(false);
const period = ref('daily');
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

const df = new DateFormatter('en-US', {
    dateStyle: 'medium',
});

const selectedRange = ref({
    start: new Date(Date.now() - 14 * 24 * 60 * 60 * 1000),
    end: new Date(),
});

const teams = ref([
    {
        label: 'Nuxt',
        avatar: {
            src: 'https://github.com/nuxt.png',
            alt: 'Nuxt',
        },
    },
    {
        label: 'NuxtHub',
        avatar: {
            src: 'https://github.com/nuxt-hub.png',
            alt: 'NuxtHub',
        },
    },
    {
        label: 'NuxtLabs',
        avatar: {
            src: 'https://github.com/nuxtlabs.png',
            alt: 'NuxtLabs',
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
    [
        {
            label: 'Create team',
            icon: 'i-lucide-circle-plus',
        },
        {
            label: 'Manage teams',
            icon: 'i-lucide-cog',
        },
    ],
]);

const user = computed(() => ({
    name: page.props.auth.user.name || 'Benjamin Canac',
    avatar: {
        src: 'https://github.com/benjamincanac.png',
        alt: page.props.auth.user.name || 'Benjamin Canac',
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
            label: 'Billing',
            icon: 'i-lucide-credit-card',
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
            label: 'Templates',
            icon: 'i-lucide-layout-template',
            children: [
                { label: 'Starter', to: 'https://starter-template.nuxt.dev/' },
                { label: 'Landing', to: 'https://landing-template.nuxt.dev/' },
                { label: 'Docs', to: 'https://docs-template.nuxt.dev/' },
                { label: 'SaaS', to: 'https://saas-template.nuxt.dev/' },
                {
                    label: 'Dashboard',
                    to: 'https://dashboard-template.nuxt.dev/',
                    color: 'primary',
                    checked: true,
                    type: 'checkbox',
                },
                { label: 'Chat', to: 'https://chat-template.nuxt.dev/' },
                { label: 'Portfolio', to: 'https://portfolio-template.nuxt.dev/' },
                { label: 'Changelog', to: 'https://changelog-template.nuxt.dev/' },
            ],
        },
    ],
    [
        {
            label: 'Documentation',
            icon: 'i-lucide-book-open',
            to: 'https://ui.nuxt.com/docs/getting-started/installation/nuxt',
            target: '_blank',
        },
        {
            label: 'GitHub repository',
            icon: 'i-simple-icons-github',
            to: 'https://github.com/nuxt-ui-templates/dashboard',
            target: '_blank',
        },
        {
            label: 'Log out',
            icon: 'i-lucide-log-out',
            onSelect: () => router.post('/logout'),
        },
    ],
]);

const links = computed(() => [
    [
        {
            label: 'Home',
            icon: 'i-lucide-house',
            active: true,
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: 'Master Data',
            icon: 'i-lucide-database',
            onSelect: () => {
                open.value = false;
                router.visit('/master-data');
            },
        },
        {
            label: 'Users',
            icon: 'i-lucide-users',
            onSelect: () => {
                open.value = false;
                router.visit('/users');
            },
        },
        {
            label: 'Roles',
            icon: 'i-lucide-shield',
            onSelect: () => {
                open.value = false;
                router.visit('/roles');
            },
        },
        {
            label: 'Permissions',
            icon: 'i-lucide-key-round',
            onSelect: () => {
                open.value = false;
                router.visit('/permissions');
            },
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
            to: 'https://github.com/nuxt-ui-templates/dashboard',
            target: '_blank',
        },
        {
            label: 'Help & Support',
            icon: 'i-lucide-info',
            to: 'https://github.com/nuxt-ui-templates/dashboard',
            target: '_blank',
        },
    ],
]);

const searchGroups = computed(() => [
    {
        id: 'links',
        label: 'Go to',
        items: links.value.flat(),
    },
    {
        id: 'code',
        label: 'Code',
        items: [
            {
                id: 'source',
                label: 'View page source',
                icon: 'i-simple-icons-github',
                to: 'https://github.com/nuxt-ui-templates/dashboard/blob/main/app/pages/index.vue',
                target: '_blank',
            },
        ],
    },
]);

const actionItems = [
    [
        {
            label: 'New mail',
            icon: 'i-lucide-send',
        },
        {
            label: 'New customer',
            icon: 'i-lucide-user-plus',
        },
    ],
];

const ranges = [
    { label: 'Last 7 days', days: 7 },
    { label: 'Last 14 days', days: 14 },
    { label: 'Last 30 days', days: 30 },
    { label: 'Last 3 months', months: 3 },
    { label: 'Last 6 months', months: 6 },
    { label: 'Last year', years: 1 },
];

const toCalendarDate = (date) => new CalendarDate(
    date.getFullYear(),
    date.getMonth() + 1,
    date.getDate(),
);

const calendarRange = computed({
    get: () => ({
        start: selectedRange.value.start ? toCalendarDate(selectedRange.value.start) : undefined,
        end: selectedRange.value.end ? toCalendarDate(selectedRange.value.end) : undefined,
    }),
    set: (newValue) => {
        selectedRange.value = {
            start: newValue.start ? newValue.start.toDate(getLocalTimeZone()) : new Date(),
            end: newValue.end ? newValue.end.toDate(getLocalTimeZone()) : new Date(),
        };
    },
});

const rangeLabel = computed(() => {
    if (!selectedRange.value.start) {
        return 'Pick a date';
    }

    if (!selectedRange.value.end) {
        return df.format(selectedRange.value.start);
    }

    return `${df.format(selectedRange.value.start)} - ${df.format(selectedRange.value.end)}`;
});

const isRangeSelected = (range) => {
    if (!selectedRange.value.start || !selectedRange.value.end) {
        return false;
    }

    const currentDate = today(getLocalTimeZone());
    let startDate = currentDate.copy();

    if (range.days) {
        startDate = startDate.subtract({ days: range.days });
    } else if (range.months) {
        startDate = startDate.subtract({ months: range.months });
    } else if (range.years) {
        startDate = startDate.subtract({ years: range.years });
    }

    return toCalendarDate(selectedRange.value.start).compare(startDate) === 0
        && toCalendarDate(selectedRange.value.end).compare(currentDate) === 0;
};

const selectRange = (range) => {
    const endDate = today(getLocalTimeZone());
    let startDate = endDate.copy();

    if (range.days) {
        startDate = startDate.subtract({ days: range.days });
    } else if (range.months) {
        startDate = startDate.subtract({ months: range.months });
    } else if (range.years) {
        startDate = startDate.subtract({ years: range.years });
    }

    selectedRange.value = {
        start: startDate.toDate(getLocalTimeZone()),
        end: endDate.toDate(getLocalTimeZone()),
    };
};

const daysInRange = computed(() => {
    const start = selectedRange.value.start;
    const end = selectedRange.value.end;

    return Math.max(1, Math.ceil((end.getTime() - start.getTime()) / 86400000) + 1);
});

const periods = computed(() => {
    if (daysInRange.value <= 8) {
        return ['daily'];
    }

    if (daysInRange.value <= 31) {
        return ['daily', 'weekly'];
    }

    return ['weekly', 'monthly'];
});

watch(periods, () => {
    if (!periods.value.includes(period.value)) {
        [period.value] = periods.value;
    }
});

const formatCurrency = (value) => value.toLocaleString('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
});

const stats = ref([]);

const baseStats = [
    { title: 'Customers', icon: 'i-lucide-users', minValue: 400, maxValue: 1000, minVariation: -15, maxVariation: 25 },
    { title: 'Conversions', icon: 'i-lucide-chart-pie', minValue: 1000, maxValue: 2000, minVariation: -10, maxVariation: 20 },
    { title: 'Revenue', icon: 'i-lucide-circle-dollar-sign', minValue: 200000, maxValue: 500000, minVariation: -20, maxVariation: 30, formatter: formatCurrency },
    { title: 'Orders', icon: 'i-lucide-shopping-cart', minValue: 100, maxValue: 300, minVariation: -5, maxVariation: 15 },
];

const randomInt = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;
const randomFrom = (items) => items[Math.floor(Math.random() * items.length)];

watch([period, selectedRange], () => {
    stats.value = baseStats.map((stat) => {
        const value = randomInt(stat.minValue, stat.maxValue);
        const variation = randomInt(stat.minVariation, stat.maxVariation);

        return {
            title: stat.title,
            icon: stat.icon,
            value: stat.formatter ? stat.formatter(value) : value,
            variation,
        };
    });
}, { immediate: true, deep: true });

const chartData = computed(() => {
    const count = period.value === 'monthly' ? 8 : period.value === 'weekly' ? 10 : 15;

    return Array.from({ length: count }, (_, index) => ({
        label: index === 0 || index === count - 1 ? '' : `${index + 1} Jul`,
        amount: randomInt(1200, 9800),
    }));
});

const chartTotal = computed(() => chartData.value.reduce((total, point) => total + point.amount, 0));
const chartMax = computed(() => Math.max(...chartData.value.map((point) => point.amount)));
const chartPoints = computed(() => chartData.value.map((point, index) => {
    const x = (index / Math.max(1, chartData.value.length - 1)) * 100;
    const y = 100 - ((point.amount / chartMax.value) * 78 + 10);

    return `${x},${y}`;
}).join(' '));
const chartArea = computed(() => `0,100 ${chartPoints.value} 100,100`);

const sampleEmails = [
    'james.anderson@example.com',
    'mia.white@example.com',
    'william.brown@example.com',
    'emma.davis@example.com',
    'ethan.harris@example.com',
];

const sales = ref([]);

watch([period, selectedRange], () => {
    const currentDate = new Date();

    sales.value = Array.from({ length: 5 }, (_, index) => {
        const date = new Date(currentDate.getTime() - randomInt(0, 48) * 3600000);

        return {
            id: (4600 - index).toString(),
            date: date.toISOString(),
            status: randomFrom(['paid', 'failed', 'refunded']),
            email: randomFrom(sampleEmails),
            amount: randomInt(100, 1000),
        };
    }).sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
}, { immediate: true, deep: true });

const UBadge = resolveComponent('UBadge');

const columns = [
    {
        accessorKey: 'id',
        header: 'ID',
        cell: ({ row }) => `#${row.getValue('id')}`,
    },
    {
        accessorKey: 'date',
        header: 'Date',
        cell: ({ row }) => new Date(row.getValue('date')).toLocaleString('en-US', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }),
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const color = {
                paid: 'success',
                failed: 'error',
                refunded: 'neutral',
            }[row.getValue('status')];

            return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () => row.getValue('status'));
        },
    },
    {
        accessorKey: 'email',
        header: 'Email',
    },
    {
        accessorKey: 'amount',
        header: () => h('div', { class: 'text-right' }, 'Amount'),
        cell: ({ row }) => h('div', { class: 'text-right font-medium' }, new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'EUR',
        }).format(Number.parseFloat(row.getValue('amount')))),
    },
];

const notifications = [
    {
        id: 1,
        unread: true,
        date: new Date(Date.now() - 1000 * 60 * 12).toISOString(),
        sender: {
            name: 'James Anderson',
            avatar: {
                src: 'https://i.pravatar.cc/80?img=1',
                alt: 'James Anderson',
            },
        },
        body: 'Sent you a message about the latest order.',
    },
    {
        id: 2,
        unread: true,
        date: new Date(Date.now() - 1000 * 60 * 46).toISOString(),
        sender: {
            name: 'Mia White',
            avatar: {
                src: 'https://i.pravatar.cc/80?img=5',
                alt: 'Mia White',
            },
        },
        body: 'Requested access to the customer report.',
    },
    {
        id: 3,
        unread: false,
        date: new Date(Date.now() - 1000 * 60 * 130).toISOString(),
        sender: {
            name: 'William Brown',
            avatar: {
                src: 'https://i.pravatar.cc/80?img=8',
                alt: 'William Brown',
            },
        },
        body: 'Uploaded a new invoice.',
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

        <UDashboardPanel id="home">
            <template #header>
                <UDashboardNavbar title="Home" :ui="{ right: 'gap-3' }">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
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
                    </template>
                </UDashboardNavbar>

                <UDashboardToolbar>
                    <template #left>
                        <UPopover :content="{ align: 'start' }" :modal="true">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-calendar"
                                class="-ms-1 data-[state=open]:bg-elevated group"
                            >
                                <span class="truncate">{{ rangeLabel }}</span>

                                <template #trailing>
                                    <UIcon name="i-lucide-chevron-down" class="size-5 shrink-0 text-dimmed transition-transform duration-200 group-data-[state=open]:rotate-180" />
                                </template>
                            </UButton>

                            <template #content>
                                <div class="flex items-stretch divide-default sm:divide-x">
                                    <div class="hidden flex-col justify-center sm:flex">
                                        <UButton
                                            v-for="(range, index) in ranges"
                                            :key="index"
                                            :label="range.label"
                                            color="neutral"
                                            variant="ghost"
                                            class="rounded-none px-4"
                                            :class="[isRangeSelected(range) ? 'bg-elevated' : 'hover:bg-elevated/50']"
                                            truncate
                                            @click="selectRange(range)"
                                        />
                                    </div>

                                    <UCalendar
                                        v-model="calendarRange"
                                        class="p-2"
                                        :number-of-months="2"
                                        range
                                    />
                                </div>
                            </template>
                        </UPopover>

                        <USelect
                            v-model="period"
                            :items="periods"
                            variant="ghost"
                            class="data-[state=open]:bg-elevated"
                            :ui="{ value: 'capitalize', itemLabel: 'capitalize', trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                        />
                    </template>
                </UDashboardToolbar>
            </template>

            <template #body>
                <UPageGrid class="gap-4 sm:gap-6 lg:grid-cols-4 lg:gap-px">
                    <UPageCard
                        v-for="(stat, index) in stats"
                        :key="index"
                        :icon="stat.icon"
                        :title="stat.title"
                        variant="subtle"
                        :ui="{
                            container: 'gap-y-1.5',
                            wrapper: 'items-start',
                            leading: 'p-2.5 rounded-full bg-primary/10 ring ring-inset ring-primary/25 flex-col',
                            title: 'font-normal text-muted text-xs uppercase',
                        }"
                        class="first:rounded-l-lg last:rounded-r-lg hover:z-1 lg:rounded-none"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-semibold text-highlighted">
                                {{ stat.value }}
                            </span>

                            <UBadge :color="stat.variation > 0 ? 'success' : 'error'" variant="subtle" class="text-xs">
                                {{ stat.variation > 0 ? '+' : '' }}{{ stat.variation }}%
                            </UBadge>
                        </div>
                    </UPageCard>
                </UPageGrid>

                <UCard class="mt-6" :ui="{ root: 'overflow-visible', body: 'px-0! pt-0! pb-3!' }">
                    <template #header>
                        <div>
                            <p class="mb-1.5 text-xs uppercase text-muted">
                                Revenue
                            </p>
                            <p class="text-3xl font-semibold text-highlighted">
                                {{ formatCurrency(chartTotal) }}
                            </p>
                        </div>
                    </template>

                    <div class="relative h-96 overflow-hidden px-4">
                        <div class="absolute inset-x-0 top-10 bottom-10 flex flex-col justify-between px-4">
                            <div v-for="line in 5" :key="line" class="border-t border-default" />
                        </div>

                        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute inset-x-4 top-10 bottom-12 h-[calc(100%-5.5rem)] w-[calc(100%-2rem)] overflow-visible">
                            <polygon :points="chartArea" fill="var(--ui-primary)" opacity="0.1" />
                            <polyline :points="chartPoints" fill="none" stroke="var(--ui-primary)" stroke-width="0.8" vector-effect="non-scaling-stroke" />
                        </svg>

                        <div class="absolute inset-x-4 bottom-3 grid text-xs text-dimmed" :style="{ gridTemplateColumns: `repeat(${chartData.length}, minmax(0, 1fr))` }">
                            <span v-for="(point, index) in chartData" :key="index" class="truncate text-center">
                                {{ point.label }}
                            </span>
                        </div>
                    </div>
                </UCard>

                <UTable
                    :data="sales"
                    :columns="columns"
                    class="mt-6 shrink-0"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                        td: 'border-b border-default',
                    }"
                />
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
