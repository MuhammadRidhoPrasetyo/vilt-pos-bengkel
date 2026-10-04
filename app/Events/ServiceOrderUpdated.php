<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceOrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int|string $storeId,
        public int|string $serviceOrderId,
        public string $status,
        public string $action = 'updated',
        public ?string $orderNumber = null,
        public ?string $plateNumber = null,
        public ?string $customerName = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('store.'.$this->storeId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ServiceOrderUpdated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'store_id' => $this->storeId,
            'service_order_id' => $this->serviceOrderId,
            'status' => $this->status,
            'action' => $this->action,
            'order_number' => $this->orderNumber,
            'plate_number' => $this->plateNumber,
            'customer_name' => $this->customerName,
        ];
    }
}
