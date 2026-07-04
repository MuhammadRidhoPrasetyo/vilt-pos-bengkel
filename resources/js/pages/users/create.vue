<script setup>
import AdminShell from '../../Components/AdminShell.vue';
import UserForm from '../../Components/UserForm.vue';
import { useForm } from '@inertiajs/vue3';

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
    <AdminShell title="Tambah User" description="Buat akun pengguna baru.">
        <div class="max-w-4xl rounded-lg border border-default p-5">
            <UserForm :form="form" :roles="roles.data" submit-label="Buat User" require-password @submit="submit" />
        </div>
    </AdminShell>
</template>
