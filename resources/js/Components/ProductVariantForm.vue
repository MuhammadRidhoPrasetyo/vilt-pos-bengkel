<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    form: Object,
    products: {
        type: Array,
        default: () => [],
    },
    attributes: {
        type: Array,
        default: () => [],
    },
    submitLabel: {
        type: String,
        default: 'Simpan',
    },
    cancelLabel: {
        type: String,
        default: 'Kembali',
    },
    showCancel: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['submit']);

const nameSuffixTouched = ref(false);
const lastGeneratedNameSuffix = ref('');

const selectedOptionCount = computed(() => props.form.attribute_option_ids.length);
const selectedProductLabel = computed(() => props.products.find((product) => product.value === props.form.product_id)?.label || '');
const optionLabelMap = computed(() => new Map(
    props.attributes.flatMap((attribute) => attribute.options.map((option) => [option.id, `${attribute.name}: ${option.value}`]))
));
const generatedNameSuffix = computed(() => {
    const parts = [
        selectedProductLabel.value,
        ...props.form.attribute_option_ids.map((optionId) => optionLabelMap.value.get(optionId)).filter(Boolean),
    ].filter(Boolean);

    return parts.join(' - ');
});

watch(generatedNameSuffix, (value) => {
    if (!nameSuffixTouched.value || props.form.name_suffix === '' || props.form.name_suffix === lastGeneratedNameSuffix.value) {
        props.form.name_suffix = value;
        lastGeneratedNameSuffix.value = value;
    }
}, { immediate: true });

watch(() => props.form.name_suffix, (value) => {
    if (value === generatedNameSuffix.value || value === '') {
        nameSuffixTouched.value = false;
        return;
    }

    nameSuffixTouched.value = true;
});
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.8fr)]">
            <UCard :ui="{ body: 'p-0!' }">
                <div class="border-b border-default p-5">
                    <h2 class="text-base font-semibold text-highlighted">Data Product Variant</h2>
                    <p class="text-sm text-muted">Tentukan produk, identitas variant, dan harga default.</p>
                </div>

                <div class="space-y-5 p-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-1 text-sm md:col-span-2">
                            <span class="font-medium">Produk</span>
                            <USelect v-model="form.product_id" :items="products" placeholder="Pilih produk" class="w-full" />
                            <span v-if="form.errors.product_id" class="text-xs text-red-600">{{ form.errors.product_id }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">SKU</span>
                            <input v-model="form.sku" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" />
                            <span v-if="form.errors.sku" class="text-xs text-red-600">{{ form.errors.sku }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Barcode</span>
                            <input v-model="form.barcode" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" />
                            <span v-if="form.errors.barcode" class="text-xs text-red-600">{{ form.errors.barcode }}</span>
                        </label>

                        <label class="grid gap-1 text-sm md:col-span-2">
                            <span class="font-medium">Nama Tambahan</span>
                            <input v-model="form.name_suffix" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" />
                            <span class="text-xs text-muted">Otomatis terisi dari produk dan attribute terpilih, tetapi tetap bisa diubah manual.</span>
                            <span v-if="form.errors.name_suffix" class="text-xs text-red-600">{{ form.errors.name_suffix }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Harga Beli Default</span>
                            <input v-model="form.default_purchase_price" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="number" min="0" step="0.01" required />
                            <span v-if="form.errors.default_purchase_price" class="text-xs text-red-600">{{ form.errors.default_purchase_price }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Harga Jual Default</span>
                            <input v-model="form.default_selling_price" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="number" min="0" step="0.01" required />
                            <span v-if="form.errors.default_selling_price" class="text-xs text-red-600">{{ form.errors.default_selling_price }}</span>
                        </label>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm">
                        <input v-model="form.is_active" class="size-4" type="checkbox" />
                        Variant aktif
                    </label>
                    <span v-if="form.errors.is_active" class="block text-xs text-red-600">{{ form.errors.is_active }}</span>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-0!' }">
                <div class="border-b border-default p-5">
                    <h2 class="text-base font-semibold text-highlighted">Attribute Variant</h2>
                    <p class="text-sm text-muted">Pilih option attribute yang membentuk variant ini.</p>
                    <p class="mt-2 text-xs text-muted">{{ selectedOptionCount }} option dipilih</p>
                </div>

                <div class="max-h-[34rem] space-y-5 overflow-y-auto p-5">
                    <div v-for="attribute in attributes" :key="attribute.id" class="space-y-3">
                        <div>
                            <h3 class="font-medium text-highlighted">{{ attribute.name }}</h3>
                            <p class="text-xs text-muted">Pilih satu atau lebih option.</p>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="option in attribute.options"
                                :key="option.id"
                                class="flex cursor-pointer items-start gap-3 rounded-md border border-default p-3 text-sm hover:bg-elevated/50"
                                :class="form.attribute_option_ids.includes(option.id) ? 'border-primary/40 bg-primary/5' : ''"
                            >
                                <input v-model="form.attribute_option_ids" class="mt-0.5 size-4" type="checkbox" :value="option.id" />
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-highlighted">{{ option.value }}</span>
                                    <span class="text-xs text-muted">{{ attribute.name }}</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <p v-if="attributes.length === 0" class="text-sm text-muted">Belum ada attribute tersedia.</p>
                    <span v-if="form.errors.attribute_option_ids" class="block text-xs text-red-600">{{ form.errors.attribute_option_ids }}</span>
                    <span v-if="form.errors['attribute_option_ids.0']" class="block text-xs text-red-600">{{ form.errors['attribute_option_ids.0'] }}</span>
                </div>
            </UCard>
        </div>

        <div class="flex justify-end gap-2">
            <Link v-if="showCancel" href="/product-variants" class="rounded-md border border-default px-4 py-2 text-sm hover:bg-elevated">{{ cancelLabel }}</Link>
            <button class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90 disabled:opacity-60" type="submit" :disabled="form.processing">
                {{ submitLabel }}
            </button>
        </div>
    </form>
</template>
