<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    form: Object,
    roles: {
        type: Array,
        default: () => [],
    },
    submitLabel: {
        type: String,
        default: 'Simpan',
    },
    requirePassword: {
        type: Boolean,
        default: false,
    },
    showCancel: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['submit']);

const roleSearch = ref('');
const roleGuardFilter = ref('all');

const roleGuardFilterItems = computed(() => {
    const guards = [...new Set(props.roles.map((role) => role.guard_name))];

    return [
        { label: 'All guard', value: 'all' },
        ...guards.map((guard) => ({ label: guard, value: guard })),
    ];
});
const filteredRoles = computed(() => {
    const searchValue = roleSearch.value.trim().toLowerCase();

    return props.roles.filter((role) => {
        const matchesGuard = roleGuardFilter.value === 'all' || role.guard_name === roleGuardFilter.value;
        const matchesSearch = !searchValue || role.name.toLowerCase().includes(searchValue);

        return matchesGuard && matchesSearch;
    });
});
const selectedRolesCount = computed(() => props.form.roles.length);
const allFilteredRolesSelected = computed(() => filteredRoles.value.length > 0 && filteredRoles.value.every((role) => props.form.roles.includes(role.id)));
const selectedRolePreview = computed(() => props.roles.filter((role) => props.form.roles.includes(role.id)).slice(0, 5));
const remainingSelectedRolesCount = computed(() => Math.max(0, selectedRolesCount.value - selectedRolePreview.value.length));

const toggleFilteredRoles = () => {
    const filteredIds = filteredRoles.value.map((role) => role.id);

    if (allFilteredRolesSelected.value) {
        props.form.roles = props.form.roles.filter((roleId) => !filteredIds.includes(roleId));
        return;
    }

    props.form.roles = [...new Set([...props.form.roles, ...filteredIds])];
};

const clearRoles = () => {
    props.form.roles = [];
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.72fr)]">
            <UCard :ui="{ body: 'p-5!' }">
                <div class="space-y-5">
                    <div>
                        <h2 class="text-base font-semibold text-highlighted">Data Diri User</h2>
                        <p class="text-sm text-muted">Informasi profil dan kredensial user.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Nama</span>
                            <input v-model="form.name" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" required />
                            <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Email</span>
                            <input v-model="form.email" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="email" required />
                            <span v-if="form.errors.email" class="text-xs text-red-600">{{ form.errors.email }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">NIK</span>
                            <input v-model="form.nik" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" />
                            <span v-if="form.errors.nik" class="text-xs text-red-600">{{ form.errors.nik }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Telepon</span>
                            <input v-model="form.phone" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" />
                            <span v-if="form.errors.phone" class="text-xs text-red-600">{{ form.errors.phone }}</span>
                        </label>

                        <label class="grid gap-1 text-sm md:col-span-2">
                            <span class="font-medium">Alamat</span>
                            <textarea v-model="form.address" class="min-h-24 rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" />
                            <span v-if="form.errors.address" class="text-xs text-red-600">{{ form.errors.address }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Password</span>
                            <input v-model="form.password" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="password" :required="requirePassword" />
                            <span v-if="!requirePassword" class="text-xs text-muted">Kosongkan jika tidak ingin mengganti password.</span>
                            <span v-if="form.errors.password" class="text-xs text-red-600">{{ form.errors.password }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Konfirmasi Password</span>
                            <input v-model="form.password_confirmation" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="password" :required="requirePassword" />
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 rounded-lg border border-default p-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.active" class="size-4" type="checkbox" />
                            Aktif
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.top_navigation" class="size-4" type="checkbox" />
                            Top navigation
                        </label>
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-0!' }">
                <div class="border-b border-default p-5">
                    <div class="space-y-3">
                        <div>
                            <h2 class="text-base font-semibold text-highlighted">Assign Role</h2>
                            <p class="text-sm text-muted">{{ selectedRolesCount }} dari {{ roles.length }} role dipilih</p>
                        </div>

                        <div class="grid gap-2">
                            <div class="relative">
                                <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                                <input v-model="roleSearch" class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary" type="search" placeholder="Cari role" />
                            </div>

                            <USelect
                                v-model="roleGuardFilter"
                                :items="roleGuardFilterItems"
                                :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                            />
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button class="rounded-md border border-default px-3 py-1.5 text-xs font-medium hover:bg-elevated" type="button" @click="toggleFilteredRoles">
                            {{ allFilteredRolesSelected ? 'Batalkan hasil filter' : 'Pilih semua hasil filter' }}
                        </button>
                        <button class="rounded-md border border-default px-3 py-1.5 text-xs font-medium hover:bg-elevated disabled:opacity-50" type="button" :disabled="selectedRolesCount === 0" @click="clearRoles">
                            Kosongkan pilihan
                        </button>
                    </div>

                    <div v-if="selectedRolesCount > 0" class="mt-3 flex flex-wrap gap-2">
                        <span v-for="role in selectedRolePreview" :key="role.id" class="rounded-md bg-elevated px-2 py-1 text-xs text-highlighted">{{ role.name }}</span>
                        <span v-if="remainingSelectedRolesCount > 0" class="rounded-md bg-elevated px-2 py-1 text-xs text-muted">+{{ remainingSelectedRolesCount }} lainnya</span>
                    </div>
                </div>

                <div class="max-h-[32rem] overflow-y-auto p-5">
                    <div v-if="filteredRoles.length > 0" class="grid gap-2">
                        <label
                            v-for="role in filteredRoles"
                            :key="role.id"
                            class="flex cursor-pointer items-start gap-3 rounded-md border border-default p-3 text-sm hover:bg-elevated/50"
                            :class="form.roles.includes(role.id) ? 'border-primary/40 bg-primary/5' : ''"
                        >
                            <input v-model="form.roles" class="mt-0.5 size-4" type="checkbox" :value="role.id" />
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-highlighted">{{ role.name }}</span>
                                <span class="text-xs text-muted">{{ role.guard_name }}</span>
                            </span>
                        </label>
                    </div>

                    <p v-else class="py-6 text-center text-sm text-muted">Role tidak ditemukan.</p>
                    <span v-if="form.errors.roles" class="mt-3 block text-xs text-red-600">{{ form.errors.roles }}</span>
                </div>
            </UCard>
        </div>

        <div class="flex justify-end gap-2">
            <Link v-if="showCancel" href="/users" class="rounded-md border border-default px-4 py-2 text-sm hover:bg-elevated">Batal</Link>
            <button class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90 disabled:opacity-60" type="submit" :disabled="form.processing">
                {{ submitLabel }}
            </button>
        </div>
    </form>
</template>
