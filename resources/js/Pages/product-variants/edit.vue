<script setup>
import ProductVariantForm from '../../Components/ProductVariantForm.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { useForm } from '@inertiajs/vue3';

defineOptions({
    layout: [DashboardLayout, { title: 'Edit Product Variant', panelId: 'product-variants-edit' }],
});

const props = defineProps({
    productVariant: Object,
    products: Array,
    attributes: Array,
});

const form = useForm({
    product_id: props.productVariant.data.product_id,
    sku: props.productVariant.data.sku || '',
    barcode: props.productVariant.data.barcode || '',
    name_suffix: props.productVariant.data.name_suffix || '',
    receipt_name: props.productVariant.data.receipt_name || '',
    default_purchase_price: props.productVariant.data.default_purchase_price,
    default_selling_price: props.productVariant.data.default_selling_price,
    is_active: props.productVariant.data.is_active,
    attribute_option_ids: props.productVariant.data.attribute_option_ids || [],
    images: [],
    delete_media_ids: [],
});

const submit = () => {
    form.post(`/product-variants/${props.productVariant.data.id}`, {
        _method: 'put',
    });
};
</script>

<template>
    <ProductVariantForm
        :form="form"
        :products="products"
        :attributes="attributes"
        :existing-media="productVariant.data.images || []"
        submit-label="Simpan Perubahan"
        @submit="submit"
    />
</template>
