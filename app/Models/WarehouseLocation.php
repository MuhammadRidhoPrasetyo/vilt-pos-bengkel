<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseLocation extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'parent_id',
        'type',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $appends = [
        'full_path',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the hierarchical full path of the warehouse location (e.g. Rak 1 / Rak 2 / Rak 3).
     */
    public function getFullPathAttribute(): string
    {
        $parts = [$this->name];
        $current = $this;
        $visited = [$this->id => true];

        while ($current->parent_id) {
            if (isset($visited[$current->parent_id])) {
                break;
            }
            $visited[$current->parent_id] = true;

            $parent = $current->relationLoaded('parent')
                ? $current->parent
                : self::find($current->parent_id);

            if (! $parent) {
                break;
            }

            array_unshift($parts, $parent->name);
            $current = $parent;
        }

        return implode(' / ', $parts);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class)->withTrashed();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
