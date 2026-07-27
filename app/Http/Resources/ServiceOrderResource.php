<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'store_id' => $this->store_id,
            'store' => new StoreResource($this->whenLoaded('store')),
            'customer_id' => $this->customer_id,
            'customer' => new PartnerResource($this->whenLoaded('customer')),
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'vehicle_id' => $this->vehicle_id,
            'plate_number' => $this->plate_number,
            'vehicle_brand' => $this->vehicle_brand,
            'vehicle_model' => $this->vehicle_model,
            'year' => $this->year,
            'color' => $this->color,
            'odometer' => $this->odometer,
            'status' => $this->status,
            'checkin_at' => $this->checkin_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'general_complaint' => $this->general_complaint,
            'diagnosis' => $this->diagnosis,
            'estimated_total' => (float) $this->estimated_total,
            'transaction_id' => $this->transaction_id,
            'created_at' => $this->created_at?->toDateTimeString(),
            'items' => ServiceOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
