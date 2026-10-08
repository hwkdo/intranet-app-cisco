<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Models;

use Hwkdo\IntranetAppCisco\Database\Factories\CiscoPhysicalDeviceMetadataFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CiscoPhysicalDeviceMetadata extends Model
{
    /** @use HasFactory<\Hwkdo\IntranetAppCisco\Database\Factories\CiscoPhysicalDeviceMetadataFactory> */
    use HasFactory;

    protected static function newFactory(): CiscoPhysicalDeviceMetadataFactory
    {
        return CiscoPhysicalDeviceMetadataFactory::new();
    }

    protected $fillable = [
        'device_name',
        'standort',
        'raum',
        'etage',
        'haus',
    ];

    protected function casts(): array
    {
        return [];
    }
}
