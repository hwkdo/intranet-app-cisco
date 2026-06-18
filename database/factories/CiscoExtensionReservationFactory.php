<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Database\Factories;

use Hwkdo\IntranetAppCisco\Models\CiscoExtensionReservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CiscoExtensionReservation>
 */
class CiscoExtensionReservationFactory extends Factory
{
    protected $model = CiscoExtensionReservation::class;

    public function definition(): array
    {
        return [
            'extension_from' => (string) fake()->numberBetween(100, 899),
            'extension_to' => null,
            'description' => fake()->words(2, true),
        ];
    }

    public function range(string $from, string $to): static
    {
        return $this->state(fn (): array => [
            'extension_from' => $from,
            'extension_to' => $to,
        ]);
    }
}
