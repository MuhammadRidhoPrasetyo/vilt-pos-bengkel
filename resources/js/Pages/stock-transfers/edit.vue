<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import FormPage from './form.vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Edit Stock Transfer', panelId: 'stock-transfers' }],
});

defineProps({ record: Object, options: Object });
</script>

<template>
    <FormPage :record="record" :options="options" mode="edit" />
</template>
