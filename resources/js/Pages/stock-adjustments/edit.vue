<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import FormPage from './form.vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Edit Stock Adjustment', panelId: 'stock-adjustments' }],
});

defineProps({ record: Object, options: Object });
</script>

<template>
    <FormPage :record="record" :options="options" mode="edit" />
</template>
