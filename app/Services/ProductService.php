<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductMovement;
use App\Models\ProductStock;
use App\Models\PurchaseItem;
use App\Models\ServiceOrderItem;
use App\Models\StockAdjustmentItem;
use App\Models\StockOpnameItem;
use App\Models\StockTransferItem;
use App\Models\TransactionItem;
use App\Repositories\ProductRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(private readonly ProductRepository $products) {}

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = $this->products->create(Arr::except($data, ['images', 'delete_media_ids']));

            if (! empty($data['images'])) {
                foreach ($data['images'] as $file) {
                    $product->addMedia($file)->toMediaCollection('images');
                }
            }

            return $product;
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product = $this->products->update($product, Arr::except($data, ['images', 'delete_media_ids']));

            if (! empty($data['delete_media_ids'])) {
                $product->media()->whereIn('id', $data['delete_media_ids'])->delete();
            }

            if (! empty($data['images'])) {
                foreach ($data['images'] as $file) {
                    $product->addMedia($file)->toMediaCollection('images');
                }
            }

            return $product;
        });
    }

    public function isUsedInTransactions(Product $product): bool
    {
        $variantIds = $product->variants()->pluck('id');

        if ($variantIds->isNotEmpty()) {
            $hasUsage = TransactionItem::whereIn('product_variant_id', $variantIds)->exists()
                || ServiceOrderItem::whereIn('product_variant_id', $variantIds)->exists()
                || PurchaseItem::whereIn('product_variant_id', $variantIds)->exists()
                || StockTransferItem::whereIn('product_variant_id', $variantIds)->exists()
                || StockAdjustmentItem::whereIn('product_variant_id', $variantIds)->exists()
                || StockOpnameItem::whereIn('product_variant_id', $variantIds)->exists()
                || InventoryMovement::whereIn('product_variant_id', $variantIds)->exists()
                || ProductStock::whereIn('product_variant_id', $variantIds)->where('quantity', '!=', 0)->exists();

            if ($hasUsage) {
                return true;
            }
        }

        return StockAdjustmentItem::where('product_id', $product->id)->exists()
            || ProductMovement::where('product_id', $product->id)->exists();
    }

    public function delete(Product $product): void
    {
        $this->products->delete($product);
    }
}
