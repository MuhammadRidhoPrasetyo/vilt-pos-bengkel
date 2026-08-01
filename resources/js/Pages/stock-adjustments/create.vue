<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import FormPage from './form.vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Buat Stock Adjustment', panelId: 'stock-adjustments' }],
});

defineProps({ options: Object });
</script>

<template>
    <FormPage :options="options" mode="create" />
</template>
