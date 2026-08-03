<?php

namespace App\DTOs;

use Carbon\CarbonInterface;

class AvailabilityQueryDto
{
    public function __construct(
        public readonly string $roomTypeId,
        public readonly string $tariffPlanId,
        public readonly CarbonInterface $checkIn,
        public readonly CarbonInterface $checkOut,
        public readonly int $adults,
        public readonly int $childrens,
        public readonly float $expectedPrice = 0.0,
    ) {}

    /**
     * The number of nights between check-in and check-out.
     */
    public function nights(): int
    {
        return (int) $this->checkIn->diffInDays($this->checkOut);
    }

    /**
     * The check-in date as a Y-m-d string (inclusive).
     */
    public function startDate(): string
    {
        return $this->checkIn->toDateString();
    }

    /**
     * The check-out date as a Y-m-d string (exclusive).
     */
    public function endDate(): string
    {
        return $this->checkOut->toDateString();
    }
}
