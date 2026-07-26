<?php

namespace App\Services;

use App\Models\Product;
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

    public function delete(Product $product): void
    {
        $this->products->delete($product);
    }
}
