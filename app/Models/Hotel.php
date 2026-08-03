<?php

namespace App\Models;

use Database\Factories\HotelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $check_in_time
 * @property string $check_out_time
 * @property bool $is_archive
 */
#[Fillable(['name', 'description', 'check_in_time', 'check_out_time', 'is_archive'])]
class Hotel extends Model
{
    /** @use HasFactory<HotelFactory> */
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
     * Get the room types for the hotel.
     *
     * @return HasMany<RoomType, $this>
     */
    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    /**
     * Get the tariff plans for the hotel.
     *
     * @return HasMany<TariffPlan, $this>
     */
    public function tariffPlans(): HasMany
    {
        return $this->hasMany(TariffPlan::class);
    }

    /**
     * Get the services for the hotel.
     *
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
