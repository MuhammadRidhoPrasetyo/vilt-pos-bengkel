<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import FormPage from './form.vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Mulai Sesi Stock Opname', panelId: 'stock-opnames' }],
});

defineProps({ options: Object });
</script>

<template>
    <FormPage :options="options" mode="create" />
</template>
