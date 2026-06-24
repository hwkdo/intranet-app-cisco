<?php

declare(strict_types=1);

use App\Models\User;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\IntranetAppCisco\Exports\PhysicalDeviceExport;
use Hwkdo\IntranetAppCisco\Models\CiscoPhysicalDeviceMetadata;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\mock;

beforeEach(function () {
    Permission::findOrCreate('manage-app-cisco', 'web');
});

test('devices page can export entire list to excel', function () {
    Excel::fake();

    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    CiscoPhysicalDeviceMetadata::factory()->create([
        'device_name' => 'SEP001122334455',
        'standort' => 'Bielefeld',
        'raum' => '212',
        'etage' => '1',
        'haus' => 'Hauptgebäude',
    ]);

    mock(AxlServiceInterface::class)
        ->shouldReceive('listPhones')
        ->once()
        ->andReturn([
            [
                'name' => 'SEP001122334455',
                'description' => 'Büro Telefon',
                'uuid' => '11111111-1111-1111-1111-111111111111',
                'product' => 'Cisco 7841',
                'protocol' => 'SIP',
                'device_pool' => 'Default',
                'owner_user_name' => '',
                'lines' => [
                    [
                        'index' => 1,
                        'pattern' => '+492315493518',
                        'route_partition' => 'PHONES',
                    ],
                ],
            ],
        ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.devices.index')
        ->call('exportExcelAll');

    Excel::assertDownloaded('geraete-alle-*.xlsx', function (PhysicalDeviceExport $export): bool {
        $row = $export->collection()->first();

        return $export->headings() === [
            'Name',
            'Line(s)',
            'Beschreibung',
            'Produkt',
            'Device Pool',
            'Standort',
            'Raum',
            'Etage',
            'Haus',
        ]
            && $row['standort'] === 'Bielefeld'
            && $row['etage'] === '1';
    });
});

test('devices page can export filtered list to excel', function () {
    Excel::fake();

    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mock(AxlServiceInterface::class)
        ->shouldReceive('listPhones')
        ->once()
        ->andReturn([
            [
                'name' => 'SEP001122334455',
                'description' => 'Büro Telefon',
                'uuid' => '11111111-1111-1111-1111-111111111111',
                'product' => 'Cisco 7841',
                'protocol' => 'SIP',
                'device_pool' => 'Default',
                'owner_user_name' => '',
                'lines' => [],
            ],
            [
                'name' => 'CSFdemo',
                'description' => 'Softphone',
                'uuid' => '22222222-2222-2222-2222-222222222222',
                'product' => 'CSF',
                'protocol' => 'SIP',
                'device_pool' => 'Default',
                'owner_user_name' => '',
                'lines' => [],
            ],
        ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.devices.index')
        ->set('onlyPhysicalDevices', true)
        ->call('exportExcelFiltered');

    Excel::assertDownloaded('geraete-gefiltert-*.xlsx', function (PhysicalDeviceExport $export): bool {
        return $export->collection()->count() === 1
            && $export->collection()->first()['name'] === 'SEP001122334455';
    });
});
