<?php

namespace App\Models;

use App\OrderStatus;
use Carbon\CarbonInterface;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $roomTypeId
 * @property CarbonInterface $check_in_date
 * @property CarbonInterface $check_out_date
 * @property int $reserved_adults
 * @property int $reserved_childrens
 * @property array<int, float> $daily_prices
 * @property float $total_price
 * @property OrderStatus $status
 * @property CarbonInterface|null $created_at
 */
#[Fillable(['roomTypeId', 'check_in_date', 'check_out_date', 'reserved_adults', 'reserved_childrens', 'daily_prices', 'total_price', 'status'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUuids;

    /**
     * The name of the "updated at" column is disabled, only "created at" is tracked.
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in_date' => 'datetime',
            'check_out_date' => 'datetime',
            'daily_prices' => 'array',
            'total_price' => 'float',
            'status' => OrderStatus::class,
        ];
    }

    /**
     * Get the room type the order belongs to.
     *
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'roomTypeId');
    }
}
