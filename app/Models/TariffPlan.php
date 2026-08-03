<?php

namespace App\Models;

use Database\Factories\TariffPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string $hotel_id
 * @property-read int $services_sum
 */
#[Fillable(['name', 'hotel_id'])]
class TariffPlan extends Model
{
    /** @use HasFactory<TariffPlanFactory> */
    use HasFactory, HasUuids;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the hotel that owns the tariff plan.
     *
     * @return BelongsTo<Hotel, $this>
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the tariffs for the tariff plan.
     *
     * @return HasMany<Tariff, $this>
     */
    public function tariffs(): HasMany
    {
        return $this->hasMany(Tariff::class);
    }

    /**
     * Get the services included in the tariff plan.
     *
     * @return BelongsToMany<Service, $this, ServiceTariffPlan>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'tariff_plans_services')
            ->using(ServiceTariffPlan::class);
    }

    /**
     * Get the total price of the services included in the tariff plan.
     */
    public function getServicesSumAttribute(): float
    {
        return (float) $this->services->sum('price');
    }
}
