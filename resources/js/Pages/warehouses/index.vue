<script setup>
import DomainCrudPage from '../../Components/DomainCrudPage.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Warehouses', panelId: 'warehouses' }],
});

defineProps({
    records: Object,
    filters: Object,
    config: Object,
    options: Object,
});
</script>

<template>
    <DomainCrudPage :records="records" :filters="filters" :config="config" :options="options" />
</template>
