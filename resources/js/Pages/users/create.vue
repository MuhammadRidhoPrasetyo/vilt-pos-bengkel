<script setup>
import UserForm from '../../Components/UserForm.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { useForm } from '@inertiajs/vue3';

defineOptions({
    layout: [DashboardLayout, { title: 'Tambah User', panelId: 'users-create' }],
});

const props = defineProps({
    roles: Object,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    nik: '',
    phone: '',
    address: '',
    active: true,
    top_navigation: false,
    roles: [],
});

const submit = () => {
    form.post('/users');
};
</script>

<template>
    <UserForm :form="form" :roles="roles.data" submit-label="Buat User" require-password @submit="submit" />
</template>
