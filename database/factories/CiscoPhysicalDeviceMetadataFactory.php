<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Database\Factories;

use Hwkdo\IntranetAppCisco\Models\CiscoPhysicalDeviceMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CiscoPhysicalDeviceMetadata>
 */
class CiscoPhysicalDeviceMetadataFactory extends Factory
{
    protected $model = CiscoPhysicalDeviceMetadata::class;

    public function definition(): array
    {
        return [
            'device_name' => 'SEP'.fake()->unique()->numerify('############'),
            'standort' => fake()->city(),
            'raum' => (string) fake()->numberBetween(100, 499),
            'etage' => null,
            'haus' => null,
        ];
    }
}
