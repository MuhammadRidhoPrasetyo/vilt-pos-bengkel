<script setup>
import DeleteConfirmationModal from './DeleteConfirmationModal.vue';
import PaginationLinks from './PaginationLinks.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, h, ref, resolveComponent, watch } from 'vue';

const props = defineProps({
    records: Object,
    filters: Object,
    config: Object,
    options: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters?.search || '');
const rowSelection = ref({});
const columnVisibility = ref({});
const modalMode = ref(null);
const selectedRecord = ref(null);
const deleteModalOpen = ref(false);
const recordToDelete = ref(null);
const deleting = ref(false);
const tagInputs = ref({});
const UCheckbox = resolveComponent('UCheckbox');
const UBadge = resolveComponent('UBadge');

const fields = computed(() => props.config?.fields || []);
const tableFields = computed(() => fields.value.filter((field) => field.table));
const selectedRowsCount = computed(() => Object.keys(rowSelection.value).length);
const form = useForm({});

const displayColumnItems = computed(() => [
    [
        ...tableFields.value.map((field) => ({
            label: field.label,
            type: 'checkbox',
            checked: columnVisibility.value[field.name] !== false,
            onSelect: (event) => {
                event.preventDefault();
                columnVisibility.value = {
                    ...columnVisibility.value,
                    [field.name]: columnVisibility.value[field.name] === false,
                };
            },
        })),
        {
            label: 'Aksi',
            type: 'checkbox',
            checked: columnVisibility.value.actions !== false,
            onSelect: (event) => {
                event.preventDefault();
                columnVisibility.value = {
                    ...columnVisibility.value,
                    actions: columnVisibility.value.actions === false,
                };
            },
        },
    ],
]);

const optionItems = (field) => {
    if (field.optionKey) {
        return props.options?.[field.optionKey] || [];
    }

    return field.options || [];
};

const getNestedValue = (record, path) => {
    if (!path) {
        return null;
    }

    return path.split('.').reduce((value, key) => value?.[key], record);
};

const displayValue = (record, field) => {
    if (field.displayKey === 'roles') {
        return (record.roles || []).map((role) => role.name).join(', ') || '-';
    }

    const value = field.displayKey ? getNestedValue(record, field.displayKey) : record[field.name];

    if (field.type === 'checkbox') {
        return value ? 'Ya' : 'Tidak';
    }

    if (Array.isArray(value)) {
        return value.join(', ') || '-';
    }

    return value || '-';
};

const initialForm = () => {
    const defaults = props.config?.defaults || {};

    return fields.value.reduce((values, field) => {
        if (field.type === 'checkbox') {
            values[field.name] = defaults[field.name] ?? false;
        } else if (field.type === 'tags' || field.type === 'multiselect') {
            values[field.name] = defaults[field.name] ?? [];
        } else {
            values[field.name] = defaults[field.name] ?? '';
        }

        return values;
    }, {});
};

const setFormValues = (values) => {
    Object.entries(values).forEach(([key, value]) => {
        form[key] = value;
    });
};

const extractFormValue = (record, field) => {
    if (field.type === 'multiselect') {
        return (record[field.name] || []).map((item) => item.id);
    }

    if (field.type === 'tags') {
        return [...(record[field.name] || [])];
    }

    if (field.type === 'checkbox') {
        return !!record[field.name];
    }

    return record[field.name] ?? '';
};

const openCreate = () => {
    selectedRecord.value = null;
    form.clearErrors();
    setFormValues(initialForm());
    tagInputs.value = {};
    modalMode.value = 'create';
};

const openEdit = (record) => {
    selectedRecord.value = record;
    form.clearErrors();
    setFormValues(fields.value.reduce((values, field) => {
        values[field.name] = extractFormValue(record, field);

        return values;
    }, {}));
    tagInputs.value = {};
    modalMode.value = 'edit';
};

const openShow = (record) => {
    selectedRecord.value = record;
    modalMode.value = 'show';
};

const closeModal = () => {
    modalMode.value = null;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeModal,
    };

    if (modalMode.value === 'edit') {
        form.put(`${props.config.route}/${selectedRecord.value.id}`, options);
        return;
    }

    form.post(props.config.route, options);
};

const destroyRecord = (record) => {
    recordToDelete.value = record;
    deleteModalOpen.value = true;
};

const confirmDelete = () => {
    if (!recordToDelete.value) {
        return;
    }

    deleting.value = true;

    router.delete(`${props.config.route}/${recordToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false;
            recordToDelete.value = null;
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
};

const addTag = (field) => {
    const value = (tagInputs.value[field.name] || '').trim();

    if (!value) {
        return;
    }

    form[field.name] = [...new Set([...(form[field.name] || []), value])];
    tagInputs.value[field.name] = '';
};

const removeTag = (field, value) => {
    form[field.name] = (form[field.name] || []).filter((item) => item !== value);
};

const isSelected = (field, value) => (form[field.name] || []).includes(value);

const toggleSelection = (field, value, checked) => {
    const values = form[field.name] || [];

    if (checked) {
        form[field.name] = [...new Set([...values, value])];
        return;
    }

    form[field.name] = values.filter((item) => item !== value);
};

const columns = computed(() => [
    {
        id: 'select',
        header: ({ table }) => h(UCheckbox, {
            modelValue: table.getIsSomePageRowsSelected() ? 'indeterminate' : table.getIsAllPageRowsSelected(),
            'aria-label': `Pilih semua ${props.config.singular}`,
            class: 'mx-auto',
            'onUpdate:modelValue': (value) => table.toggleAllPageRowsSelected(!!value),
        }),
        cell: ({ row }) => h(UCheckbox, {
            modelValue: row.getIsSelected(),
            'aria-label': `Pilih ${props.config.singular}`,
            class: 'mx-auto',
            'onUpdate:modelValue': (value) => row.toggleSelected(!!value),
        }),
        meta: {
            class: {
                th: 'w-12 text-center',
                td: 'w-12 text-center',
            },
        },
    },
    ...tableFields.value.map((field) => ({
        id: field.name,
        accessorKey: field.name,
        header: field.label,
        cell: ({ row }) => {
            if (field.type === 'checkbox') {
                return h(UBadge, {
                    color: row.original[field.name] ? 'success' : 'neutral',
                    variant: 'subtle',
                }, () => row.original[field.name] ? 'Ya' : 'Tidak');
            }

            return h('span', {
                class: field.required ? 'font-medium text-highlighted' : 'text-muted',
            }, displayValue(row.original, field));
        },
    })),
    {
        id: 'actions',
        header: 'Aksi',
        meta: {
            class: {
                th: 'w-28 text-right',
                td: 'w-28 text-right',
            },
        },
    },
]);

watch(search, (value) => {
    router.get(props.config.route, { search: value }, { preserveState: true, replace: true });
});

watch(() => props.records?.data, () => {
    rowSelection.value = {};
});
</script>

<template>
    <div class="space-y-4">
        <UDashboardToolbar
            :ui="{
                root: 'min-h-0 flex-col items-stretch gap-3 overflow-visible border-b-0 px-0 sm:flex-row sm:items-center sm:px-0',
                left: 'w-full sm:w-auto',
                right: 'w-full flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end',
            }"
        >
            <template #left>
                <div class="relative w-full sm:w-72">
                    <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                    <input
                        v-model="search"
                        class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary"
                        type="search"
                        :placeholder="config.searchPlaceholder"
                    />
                </div>
            </template>

            <template #right>
                <UDropdownMenu :items="displayColumnItems" :content="{ align: 'end' }">
                    <UButton color="neutral" variant="outline" icon="i-lucide-sliders-horizontal" label="Display" class="w-full justify-center sm:w-auto" />
                </UDropdownMenu>

                <UButton icon="i-lucide-plus" :label="`Tambah ${config.singular}`" class="w-full justify-center sm:w-auto" @click="openCreate" />
            </template>
        </UDashboardToolbar>

        <UCard :ui="{ root: 'overflow-hidden', body: 'p-0!' }">
            <div class="overflow-x-auto">
                <UTable
                    v-model:column-visibility="columnVisibility"
                    v-model:row-selection="rowSelection"
                    :data="records?.data || []"
                    :columns="columns"
                    :get-row-id="(row) => String(row.id)"
                    class="min-w-[900px]"
                    :empty="`Belum ada ${config.singular}.`"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'first:rounded-tl-lg last:rounded-tr-lg border-b border-default',
                        td: 'border-b border-default',
                        tr: 'data-[selected=true]:bg-elevated/40',
                    }"
                >
                    <template #actions-cell="{ row }">
                        <div class="flex justify-end gap-2">
                            <button class="inline-flex size-8 items-center justify-center rounded-md border border-default text-muted hover:bg-elevated hover:text-highlighted" type="button" title="Detail" @click="openShow(row.original)">
                                <UIcon name="i-lucide-eye" class="size-4" />
                            </button>
                            <button class="inline-flex size-8 items-center justify-center rounded-md border border-default text-muted hover:bg-elevated hover:text-highlighted" type="button" title="Edit" @click="openEdit(row.original)">
                                <UIcon name="i-lucide-pencil" class="size-4" />
                            </button>
                            <button class="inline-flex size-8 items-center justify-center rounded-md border border-error/30 text-error hover:bg-error/10" type="button" title="Hapus" @click="destroyRecord(row.original)">
                                <UIcon name="i-lucide-trash-2" class="size-4" />
                            </button>
                        </div>
                    </template>
                </UTable>
            </div>
        </UCard>

        <p v-if="selectedRowsCount > 0" class="text-sm text-muted">
            {{ selectedRowsCount }} {{ config.singular }} dipilih.
        </p>

        <PaginationLinks v-if="records?.meta?.links" :links="records.meta.links" />

        <div v-if="modalMode" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-3xl overflow-y-auto rounded-lg bg-default p-5 shadow-xl">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold">
                        {{ modalMode === 'create' ? `Tambah ${config.singular}` : modalMode === 'edit' ? `Edit ${config.singular}` : `Detail ${config.singular}` }}
                    </h2>
                    <button class="rounded-md p-2 hover:bg-elevated" type="button" @click="closeModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <div v-if="modalMode === 'show'" class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div v-for="field in fields" :key="field.name">
                        <p class="text-sm text-muted">{{ field.label }}</p>
                        <p class="font-medium text-highlighted">{{ displayValue(selectedRecord, field) }}</p>
                    </div>
                </div>

                <form v-else class="mt-5 grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <label
                        v-for="field in fields"
                        :key="field.name"
                        class="grid gap-1 text-sm"
                        :class="field.type === 'textarea' || field.type === 'tags' || field.type === 'multiselect' ? 'sm:col-span-2' : ''"
                    >
                        <span class="font-medium">{{ field.label }}</span>

                        <textarea
                            v-if="field.type === 'textarea'"
                            v-model="form[field.name]"
                            class="min-h-24 rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary"
                            :required="field.required"
                        />

                        <USelect
                            v-else-if="field.type === 'select'"
                            v-model="form[field.name]"
                            :items="optionItems(field)"
                            class="w-full"
                            :placeholder="`Pilih ${field.label}`"
                        />

                        <div v-else-if="field.type === 'checkbox'" class="flex items-center gap-3 rounded-md border border-default px-3 py-2">
                            <UCheckbox v-model="form[field.name]" :aria-label="field.label" />
                            <span class="text-muted">{{ form[field.name] ? 'Aktif' : 'Tidak aktif' }}</span>
                        </div>

                        <div v-else-if="field.type === 'multiselect'" class="grid gap-2 rounded-md border border-default p-3 sm:grid-cols-2">
                            <label v-for="item in optionItems(field)" :key="item.value" class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-elevated">
                                <UCheckbox :model-value="isSelected(field, item.value)" @update:model-value="(value) => toggleSelection(field, item.value, value)" />
                                <span>{{ item.label }}</span>
                            </label>
                            <p v-if="optionItems(field).length === 0" class="text-sm text-muted">Belum ada opsi.</p>
                        </div>

                        <div v-else-if="field.type === 'tags'" class="space-y-2">
                            <div class="flex gap-2">
                                <input
                                    v-model="tagInputs[field.name]"
                                    class="min-w-0 flex-1 rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary"
                                    type="text"
                                    @keydown.enter.prevent="addTag(field)"
                                />
                                <UButton type="button" icon="i-lucide-plus" color="neutral" variant="outline" @click="addTag(field)" />
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="value in form[field.name]"
                                    :key="value"
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-md border border-default px-2 py-1 text-sm hover:bg-elevated"
                                    @click="removeTag(field, value)"
                                >
                                    <span>{{ value }}</span>
                                    <UIcon name="i-lucide-x" class="size-3" />
                                </button>
                            </div>
                        </div>

                        <input
                            v-else
                            v-model="form[field.name]"
                            class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary"
                            :type="field.type || 'text'"
                            :required="field.required"
                        />

                        <span v-if="form.errors[field.name]" class="text-xs text-red-600">{{ form.errors[field.name] }}</span>
                    </label>

                    <div class="flex justify-end gap-2 sm:col-span-2">
                        <button class="rounded-md border border-default px-4 py-2 text-sm hover:bg-elevated" type="button" @click="closeModal">Batal</button>
                        <button class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90 disabled:opacity-60" type="submit" :disabled="form.processing">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <DeleteConfirmationModal
            v-model:open="deleteModalOpen"
            :title="`Hapus ${config.singular}?`"
            :description="`${recordToDelete?.name || recordToDelete?.code || config.singular} akan dihapus dari sistem.`"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
