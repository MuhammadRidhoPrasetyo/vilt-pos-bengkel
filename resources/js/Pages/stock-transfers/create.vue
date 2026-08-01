<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import FormPage from './form.vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Buat Stock Transfer', panelId: 'stock-transfers' }],
});

defineProps({ options: Object });
</script>

<template>
    <FormPage :options="options" mode="create" />
</template>
