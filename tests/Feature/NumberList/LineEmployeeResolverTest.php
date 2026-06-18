<?php

declare(strict_types=1);

use App\Models\Gvp;
use App\Models\User;
use Hwkdo\IntranetAppCisco\Services\ExtensionDirectoryBuilder;
use Hwkdo\IntranetAppCisco\Services\LineEmployeeResolver;

test('line employee resolver matches user primarily by phone extension', function () {
    $gvp = Gvp::factory()->create([
        'kuerzel' => 'GB',
        'nummer' => '12',
        'name' => 'Verwaltung',
    ]);

    User::factory()->create([
        'vorname' => 'Sebastian',
        'nachname' => 'Kopec',
        'active' => true,
        'gvp_id' => $gvp->id,
        'telefon' => '+49 231 5493-518',
    ]);

    $resolved = app(LineEmployeeResolver::class)->resolveForLine([
        'pattern' => '\+492315493518',
        'description' => 'Sebastian Kopec +492315493518 doard DE',
        'alerting_name' => 'Sebastian Kopec 518',
    ]);

    expect($resolved)->not->toBeNull()
        ->and($resolved->department)->toBe('GB 12 Verwaltung');
});

test('line employee resolver matches user by comma separated name', function () {
    $gvp = Gvp::factory()->create([
        'kuerzel' => 'GB',
        'nummer' => '12',
        'name' => 'Verwaltung',
    ]);

    User::factory()->create([
        'vorname' => 'Max',
        'nachname' => 'Mustermann',
        'active' => true,
        'gvp_id' => $gvp->id,
        'telefon' => '0231-5493518',
    ]);

    $resolved = app(LineEmployeeResolver::class)->resolveForLine([
        'pattern' => '\+492315493518',
        'description' => 'Mustermann, Max',
        'alerting_name' => '',
    ]);

    expect($resolved)->not->toBeNull()
        ->and($resolved->department)->toBe('GB 12 Verwaltung');
});

test('line employee resolver matches user by space separated name', function () {
    $gvp = Gvp::factory()->create([
        'kuerzel' => 'A',
        'nummer' => '3',
        'name' => 'IT',
    ]);

    User::factory()->create([
        'vorname' => 'Erika',
        'nachname' => 'Musterfrau',
        'active' => true,
        'gvp_id' => $gvp->id,
    ]);

    $resolved = app(LineEmployeeResolver::class)->resolveFromLabel('Erika Musterfrau');

    expect($resolved)->not->toBeNull()
        ->and($resolved->department)->toBe('A 3 IT');
});

test('line employee resolver disambiguates duplicate names by extension', function () {
    $gvp = Gvp::factory()->create([
        'kuerzel' => 'GB',
        'nummer' => '1',
        'name' => 'A',
    ]);

    User::factory()->create([
        'vorname' => 'Max',
        'nachname' => 'Mustermann',
        'active' => true,
        'gvp_id' => $gvp->id,
        'telefon' => '0231-5493518',
    ]);

    User::factory()->create([
        'vorname' => 'Max',
        'nachname' => 'Mustermann',
        'active' => true,
        'gvp_id' => $gvp->id,
        'telefon' => '0231-5493999',
    ]);

    $resolved = app(LineEmployeeResolver::class)->resolveForLine([
        'pattern' => '\+492315493518',
        'description' => 'Max Mustermann',
        'alerting_name' => '',
    ]);

    expect($resolved)->not->toBeNull()
        ->and($resolved->user->telefon)->toBe('0231-5493518');
});

test('extension directory builder adds department for resolved line users', function () {
    $gvp = Gvp::factory()->create([
        'kuerzel' => 'GB',
        'nummer' => '99',
        'name' => 'Zentrale',
    ]);

    User::factory()->create([
        'vorname' => 'Max',
        'nachname' => 'Mustermann',
        'active' => true,
        'gvp_id' => $gvp->id,
    ]);

    mock(\Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface::class)
        ->shouldReceive('listLines')
        ->andReturn([
            [
                'pattern' => '\+492315493518',
                'description' => 'Max Mustermann',
                'alerting_name' => '',
                'uuid' => '',
                'usage' => 'Device',
                'route_partition' => 'PHONES',
                'calling_search_space' => '',
                'calling_permission' => '—',
            ],
        ])
        ->shouldReceive('listCallPickupGroups')
        ->andReturn([])
        ->shouldReceive('listHuntPilots')
        ->andReturn([]);

    $entry = collect(app(ExtensionDirectoryBuilder::class)->build())
        ->firstWhere('extension', 518);

    expect($entry['department'])->toBe('GB 99 Zentrale');
});
