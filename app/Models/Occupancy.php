<?php

namespace App\Models;

use Database\Factories\OccupancyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $room_type_id
 * @property int $adults
 * @property int $childrens
 * @property bool $extra_bed
 */
#[Fillable(['room_type_id', 'adults', 'childrens', 'extra_bed'])]
class Occupancy extends Model
{
    /** @use HasFactory<OccupancyFactory> */
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
            'extra_bed' => 'boolean',
        ];
    }

    /**
     * Get the room type that owns the occupancy.
     *
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
