<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\TariffFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property CarbonInterface $date
 * @property string $room_type_id
 * @property string $tariff_plan_id
 * @property float $price
 */
#[Fillable(['date', 'room_type_id', 'tariff_plan_id', 'price'])]
class Tariff extends Model
{
    /** @use HasFactory<TariffFactory> */
    use HasFactory;

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
            'date' => 'date',
        ];
    }

    /**
     * Get the room type the tariff belongs to.
     *
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Get the tariff plan the tariff belongs to.
     *
     * @return BelongsTo<TariffPlan, $this>
     */
    public function tariffPlan(): BelongsTo
    {
        return $this->belongsTo(TariffPlan::class);
    }
}
