<script setup>
import ProductVariantForm from '../../Components/ProductVariantForm.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { useForm } from '@inertiajs/vue3';

defineOptions({
    layout: [DashboardLayout, { title: 'Tambah Product Variant', panelId: 'product-variants-create' }],
});

const props = defineProps({
    products: Array,
    attributes: Array,
});

const form = useForm({
    product_id: '',
    sku: '',
    barcode: '',
    name_suffix: '',
    default_purchase_price: 0,
    default_selling_price: 0,
    is_active: true,
    attribute_option_ids: [],
});

const submit = () => {
    form.post('/product-variants');
};
</script>

<template>
    <ProductVariantForm :form="form" :products="products" :attributes="attributes" submit-label="Buat Product Variant" @submit="submit" />
</template>
