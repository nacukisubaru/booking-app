<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $name
 * @property float $price
 * @property string $hotel_id
 */
#[Fillable(['name', 'price', 'hotel_id'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasUuids;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the hotel that owns the service.
     *
     * @return BelongsTo<Hotel, $this>
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the tariff plans that include the service.
     *
     * @return BelongsToMany<TariffPlan, $this, ServiceTariffPlan>
     */
    public function tariffPlans(): BelongsToMany
    {
        return $this->belongsToMany(TariffPlan::class, 'tariff_plans_services')
            ->using(ServiceTariffPlan::class);
    }
}
