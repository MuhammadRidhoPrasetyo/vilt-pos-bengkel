<?php

namespace App\Services;

use App\Models\Attribute;
use App\Repositories\AttributeRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AttributeService
{
    public function __construct(private readonly AttributeRepository $attributes) {}

    public function create(array $data): Attribute
    {
        return DB::transaction(function () use ($data) {
            $attribute = $this->attributes->create(Arr::only($data, ['name']));
            $this->syncOptions($attribute, $data['options'] ?? []);

            return $attribute->load('options:id,attribute_id,value');
        });
    }

    public function update(Attribute $attribute, array $data): Attribute
    {
        return DB::transaction(function () use ($attribute, $data) {
            $attribute = $this->attributes->update($attribute, Arr::only($data, ['name']));
            $this->syncOptions($attribute, $data['options'] ?? []);

            return $attribute->load('options:id,attribute_id,value');
        });
    }

    public function delete(Attribute $attribute): void
    {
        $this->attributes->delete($attribute);
    }

    private function syncOptions(Attribute $attribute, array $options): void
    {
        $values = collect($options)
            ->map(fn ($option) => trim((string) $option))
            ->filter()
            ->unique()
            ->values();

        $attribute->options()->delete();

        $values->each(fn (string $value) => $attribute->options()->create(['value' => $value]));
    }
}
