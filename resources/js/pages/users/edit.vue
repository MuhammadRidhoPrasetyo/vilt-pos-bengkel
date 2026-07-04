<script setup>
import AdminShell from '../../Components/AdminShell.vue';
import UserForm from '../../Components/UserForm.vue';
import { useForm } from '@inertiajs/vue3';

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
</script>

<template>
    <AdminShell title="Edit User" description="Perbarui data akun dan role pengguna.">
        <div class="max-w-4xl rounded-lg border border-default p-5">
            <UserForm :form="form" :roles="roles.data" submit-label="Simpan Perubahan" @submit="submit" />
        </div>
    </AdminShell>
</template>
