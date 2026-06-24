<?php

declare(strict_types=1);

use App\Models\Standort;
use App\Models\User;
use Hwkdo\IntranetAppCisco\Models\CiscoPhysicalDeviceMetadata;
use Hwkdo\IntranetAppCisco\Services\PhysicalDeviceMetadataService;

beforeEach(function () {
    $this->service = app(PhysicalDeviceMetadataService::class);
});

test('physical device detection accepts SEP and AN5 prefixes', function () {
    expect($this->service->isPhysicalDevice('SEP001122334455'))->toBeTrue()
        ->and($this->service->isPhysicalDevice('an5123456789ab'))->toBeTrue()
        ->and($this->service->isPhysicalDevice('CSFdemo'))->toBeFalse();
});

test('ensure for device creates metadata with standort and raum from owner user', function () {
    $standort = Standort::query()->create([
        'name' => 'Bielefeld',
        'extension' => '521',
        'strasse' => 'Musterstraße 1',
        'ort' => 'Bielefeld',
        'plz' => '33602',
    ]);

    User::factory()->create([
        'username' => 'max.mustermann',
        'raum' => '212',
        'standort_id' => $standort->id,
    ]);

    $metadata = $this->service->ensureForDevice([
        'name' => 'SEP001122334455',
        'description' => 'Büro Telefon',
        'owner_user_name' => 'max.mustermann',
        'lines' => [],
    ]);

    expect($metadata)->not->toBeNull()
        ->and($metadata->standort)->toBe('Bielefeld')
        ->and($metadata->raum)->toBe('212')
        ->and($metadata->etage)->toBeNull()
        ->and($metadata->haus)->toBeNull();
});

test('ensure for device does not overwrite existing metadata', function () {
    CiscoPhysicalDeviceMetadata::factory()->create([
        'device_name' => 'SEP001122334455',
        'standort' => 'Manuell',
        'raum' => '999',
        'etage' => '2',
        'haus' => 'A',
    ]);

    $metadata = $this->service->ensureForDevice([
        'name' => 'SEP001122334455',
        'description' => 'Büro Telefon',
        'owner_user_name' => 'max.mustermann',
        'lines' => [],
    ]);

    expect($metadata->standort)->toBe('Manuell')
        ->and($metadata->raum)->toBe('999')
        ->and($metadata->etage)->toBe('2')
        ->and($metadata->haus)->toBe('A');
});

test('merge into devices enriches physical devices with metadata', function () {
    CiscoPhysicalDeviceMetadata::factory()->create([
        'device_name' => 'SEP001122334455',
        'standort' => 'Bielefeld',
        'raum' => '212',
        'etage' => '1',
        'haus' => 'Hauptgebäude',
    ]);

    $devices = $this->service->mergeIntoDevices([
        [
            'name' => 'SEP001122334455',
            'description' => 'Büro',
            'product' => 'Cisco 7841',
            'device_pool' => 'Default',
            'lines' => [],
        ],
        [
            'name' => 'CSFdemo',
            'description' => 'Softphone',
            'product' => 'CSF',
            'device_pool' => 'Default',
            'lines' => [],
        ],
    ]);

    expect($devices[0]['standort'])->toBe('Bielefeld')
        ->and($devices[0]['etage'])->toBe('1')
        ->and($devices[1]['standort'])->toBeNull();
});

test('update stores all location fields for physical device', function () {
    CiscoPhysicalDeviceMetadata::factory()->create([
        'device_name' => 'SEP001122334455',
    ]);

    $metadata = $this->service->update('SEP001122334455', [
        'standort' => 'Gütersloh',
        'raum' => '101',
        'etage' => 'EG',
        'haus' => 'Nord',
    ]);

    expect($metadata->standort)->toBe('Gütersloh')
        ->and($metadata->raum)->toBe('101')
        ->and($metadata->etage)->toBe('EG')
        ->and($metadata->haus)->toBe('Nord');
});
