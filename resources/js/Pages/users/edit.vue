<script setup>
import UserForm from '../../Components/UserForm.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { onUnmounted } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Edit User', panelId: 'users-edit' }],
});

const props = defineProps({
    user: Object,
    roles: Object,
});

const form = useForm({
    name: props.user.data.name,
    email: props.user.data.email,
    password: '',
    password_confirmation: '',
    nik: props.user.data.nik || '',
    phone: props.user.data.phone || '',
    address: props.user.data.address || '',
    active: props.user.data.active,
    top_navigation: props.user.data.top_navigation,
    roles: props.user.data.roles.map((role) => role.id),
});

const submit = () => {
    form.put(`/users/${props.user.data.id}`);
};

setLayoutProps({
    navbarAction: {
        label: 'Kembali',
        icon: 'i-lucide-arrow-left',
        color: 'neutral',
        variant: 'outline',
        onClick: () => router.visit('/users'),
    },
});

onUnmounted(() => {
    setLayoutProps({ navbarAction: null });
});
</script>

<template>
    <UserForm :form="form" :roles="roles.data" submit-label="Simpan Perubahan" :show-cancel="false" @submit="submit" />
</template>
