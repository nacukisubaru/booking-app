<?php

namespace App\Models;

use Database\Factories\RoomTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string $hotel_id
 * @property int $max_adults
 * @property int $max_childrens
 * @property int $total_guests
 * @property bool $is_archive
 */
#[Fillable(['name', 'hotel_id', 'max_adults', 'max_childrens', 'total_guests', 'is_archive'])]
class RoomType extends Model
{
    /** @use HasFactory<RoomTypeFactory> */
    use HasFactory, HasUuids;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_archive' => 'boolean',
        ];
    }

    /**
     * Get the hotel that owns the room type.
     *
     * @return BelongsTo<Hotel, $this>
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the occupancies for the room type.
     *
     * @return HasMany<Occupancy, $this>
     */
    public function occupancies(): HasMany
    {
        return $this->hasMany(Occupancy::class);
    }

    /**
     * Get the quotas for the room type.
     *
     * @return HasMany<Quota, $this>
     */
    public function quotas(): HasMany
    {
        return $this->hasMany(Quota::class);
    }

    /**
     * Get the tariffs for the room type.
     *
     * @return HasMany<Tariff, $this>
     */
    public function tariffs(): HasMany
    {
        return $this->hasMany(Tariff::class);
    }

    /**
     * Get the orders for the room type.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'roomTypeId');
    }
}
