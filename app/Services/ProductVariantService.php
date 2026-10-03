<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\PurchaseItem;
use App\Models\ServiceOrderItem;
use App\Models\StockAdjustmentItem;
use App\Models\StockOpnameItem;
use App\Models\StockTransferItem;
use App\Models\TransactionItem;
use App\Repositories\ProductVariantRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductVariantService
{
    public function __construct(private readonly ProductVariantRepository $productVariants) {}

    public function create(array $data): ProductVariant
    {
        return DB::transaction(function () use ($data) {
            $productVariant = $this->productVariants->create(Arr::except($data, ['attribute_option_ids', 'images', 'delete_media_ids']));
            $productVariant->attributeOptions()->sync($data['attribute_option_ids'] ?? []);

            if (! empty($data['images'])) {
                foreach ($data['images'] as $file) {
                    $productVariant->addMedia($file)->toMediaCollection('images');
                }
            }

            return $productVariant->load(['product:id,name', 'attributeOptions:id,attribute_id,value', 'attributeOptions.attribute:id,name', 'media']);
        });
    }

    public function update(ProductVariant $productVariant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($productVariant, $data) {
            $productVariant = $this->productVariants->update($productVariant, Arr::except($data, ['attribute_option_ids', 'images', 'delete_media_ids']));
            $productVariant->attributeOptions()->sync($data['attribute_option_ids'] ?? []);

            if (! empty($data['delete_media_ids'])) {
                $productVariant->media()->whereIn('id', $data['delete_media_ids'])->delete();
            }

            if (! empty($data['images'])) {
                foreach ($data['images'] as $file) {
                    $productVariant->addMedia($file)->toMediaCollection('images');
                }
            }

            return $productVariant->load(['product:id,name', 'attributeOptions:id,attribute_id,value', 'attributeOptions.attribute:id,name', 'media']);
        });
    }

    public function isUsedInTransactions(ProductVariant $variant): bool
    {
        return TransactionItem::where('product_variant_id', $variant->id)->exists()
            || ServiceOrderItem::where('product_variant_id', $variant->id)->exists()
            || PurchaseItem::where('product_variant_id', $variant->id)->exists()
            || StockTransferItem::where('product_variant_id', $variant->id)->exists()
            || StockAdjustmentItem::where('product_variant_id', $variant->id)->exists()
            || StockOpnameItem::where('product_variant_id', $variant->id)->exists()
            || InventoryMovement::where('product_variant_id', $variant->id)->exists()
            || ProductStock::where('product_variant_id', $variant->id)->where('quantity', '!=', 0)->exists();
    }

    public function delete(ProductVariant $productVariant): void
    {
        $this->productVariants->delete($productVariant);
    }
}
