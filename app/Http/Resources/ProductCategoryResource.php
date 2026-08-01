<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'store' => $this->whenLoaded('store', fn () => $this->store ? ['id' => $this->store->id, 'name' => $this->store->name] : ['id' => null, 'name' => 'Global (Semua Toko)']),
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent?->id,
                'name' => $this->parent?->name,
            ]),
            'name' => $this->name,
            'pricing_mode' => $this->pricing_mode,
            'income_cash_flow_category_id' => $this->income_cash_flow_category_id,
            'expense_cash_flow_category_id' => $this->expense_cash_flow_category_id,
            'income_cash_flow_category' => $this->whenLoaded('incomeCashFlowCategory', fn () => $this->incomeCashFlowCategory ? ['id' => $this->incomeCashFlowCategory->id, 'name' => $this->incomeCashFlowCategory->name] : null),
            'expense_cash_flow_category' => $this->whenLoaded('expenseCashFlowCategory', fn () => $this->expenseCashFlowCategory ? ['id' => $this->expenseCashFlowCategory->id, 'name' => $this->expenseCashFlowCategory->name] : null),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
