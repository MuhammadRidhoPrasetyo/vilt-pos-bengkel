<script setup>
import DomainCrudPage from '../../Components/DomainCrudPage.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Cash Flow Categories', panelId: 'cash-flow-categories' }],
});

defineProps({
    records: Object,
    filters: Object,
    config: Object,
});
</script>

<template>
    <DomainCrudPage :records="records" :filters="filters" :config="config" />
</template>
