<script setup>
import DashboardLayout from '../Layouts/DashboardLayout.vue';
import { CalendarDate, DateFormatter, getLocalTimeZone, today } from '@internationalized/date';
import { computed, h, ref, resolveComponent, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Home', panelId: 'home' }],
});

const period = ref('daily');

const df = new DateFormatter('en-US', {
    dateStyle: 'medium',
});

const selectedRange = ref({
    start: new Date(Date.now() - 14 * 24 * 60 * 60 * 1000),
    end: new Date(),
});

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

</script>

<template>
    <div class="space-y-6">
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
    </div>
</template>
