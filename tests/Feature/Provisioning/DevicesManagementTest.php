<?php

declare(strict_types=1);

use App\Models\User;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\IntranetAppCisco\Models\CiscoPhysicalDeviceMetadata;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\mock;

beforeEach(function () {
    Permission::findOrCreate('manage-app-cisco', 'web');
});

test('devices page requires authentication', function () {
    $this->get(route('apps.cisco.devices.index'))
        ->assertRedirect(route('login', absolute: false));
});

test('devices page requires manage-app-cisco permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('apps.cisco.devices.index'))
        ->assertForbidden();
});

test('devices page lists phones', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mock(AxlServiceInterface::class)
        ->shouldReceive('listPhones')
        ->once()
        ->andReturn([
            [
                'name' => 'CSFdemo',
                'description' => 'Demo Phone',
                'uuid' => '11111111-1111-1111-1111-111111111111',
                'product' => 'CSF',
                'protocol' => 'SIP',
                'device_pool' => 'Default',
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
        ->assertSee('Geräte (Phones)')
        ->assertSee('1 Eintrag')
        ->assertSee('CSFdemo')
        ->assertSee('Demo Phone')
        ->assertSee('Default')
        ->assertSee('+492315493518')
        ->assertSee('PHONES');
});

test('devices page filters locally by description and product', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mock(AxlServiceInterface::class)
        ->shouldReceive('listPhones')
        ->once()
        ->andReturn([
            [
                'name' => 'CSFmax.mustermann',
                'description' => 'Feuerwehreinfahrt',
                'uuid' => '11111111-1111-1111-1111-111111111111',
                'product' => 'Cisco Unified Client Services Framework',
                'protocol' => 'SIP',
                'device_pool' => 'Default',
                'lines' => [],
            ],
            [
                'name' => 'CSFdemo',
                'description' => 'Büro',
                'uuid' => '22222222-2222-2222-2222-222222222222',
                'product' => 'CSF',
                'protocol' => 'SIP',
                'device_pool' => 'Default',
                'lines' => [],
            ],
        ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.devices.index')
        ->set('search', 'feuerwehr')
        ->assertSee('1 von 2 Einträgen angezeigt')
        ->assertSee('CSFmax.mustermann')
        ->assertDontSee('CSFdemo');
});

test('devices page filters locally by assigned line pattern', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mock(AxlServiceInterface::class)
        ->shouldReceive('listPhones')
        ->once()
        ->andReturn([
            [
                'name' => 'CSFmax.mustermann',
                'description' => 'Büro',
                'uuid' => '11111111-1111-1111-1111-111111111111',
                'product' => 'CSF',
                'protocol' => 'SIP',
                'device_pool' => 'Default',
                'lines' => [
                    [
                        'index' => 1,
                        'pattern' => '+492315493518',
                        'route_partition' => 'PHONES',
                    ],
                ],
            ],
            [
                'name' => 'CSFdemo',
                'description' => 'Andere',
                'uuid' => '22222222-2222-2222-2222-222222222222',
                'product' => 'CSF',
                'protocol' => 'SIP',
                'device_pool' => 'Default',
                'lines' => [],
            ],
        ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.devices.index')
        ->set('search', '493518')
        ->assertSee('1 von 2 Einträgen angezeigt')
        ->assertSee('CSFmax.mustermann')
        ->assertDontSee('CSFdemo');
});

test('devices page filters to physical devices with SEP and AN5 prefix', function () {
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
                'product' => 'Cisco IP Phone 8841',
                'protocol' => 'SIP',
                'device_pool' => 'Default',
                'lines' => [],
            ],
            [
                'name' => 'AN5123456789AB',
                'description' => 'Analog Adapter',
                'uuid' => '33333333-3333-3333-3333-333333333333',
                'product' => 'Analog Phone',
                'protocol' => 'SCCP',
                'device_pool' => 'Default',
                'lines' => [],
            ],
            [
                'name' => 'CSFdemo',
                'description' => 'Softphone',
                'uuid' => '22222222-2222-2222-2222-222222222222',
                'product' => 'CSF',
                'protocol' => 'SIP',
                'device_pool' => 'Default',
                'lines' => [],
            ],
        ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.devices.index')
        ->assertSee('3 Einträge')
        ->assertSee('SEP001122334455')
        ->assertSee('AN5123456789AB')
        ->assertSee('CSFdemo')
        ->set('onlyPhysicalDevices', true)
        ->assertSee('2 von 3 Einträgen angezeigt')
        ->assertSee('SEP001122334455')
        ->assertSee('AN5123456789AB')
        ->assertDontSee('CSFdemo');
});

test('devices page can open edit form for phone with fk object fields', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mock(AxlServiceInterface::class)
        ->shouldReceive('listPhones')
        ->andReturn([])
        ->shouldReceive('getPhone')
        ->once()
        ->with('CSFdemo')
        ->andReturn((object) [
            'name' => 'CSFdemo',
            'description' => 'Demo Phone',
            'product' => (object) ['_' => 'Cisco Unified Client Services Framework'],
            'protocol' => (object) ['_' => 'SIP'],
            'devicePoolName' => (object) ['_' => 'Default'],
            'ownerUserName' => (object) ['_' => 'demo.user'],
        ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.devices.index')
        ->call('openEditForm', 'CSFdemo')
        ->assertSet('showForm', true)
        ->assertSet('formOwnerUserName', 'demo.user')
        ->assertSet('formDevicePool', 'Default');
});

test('devices page can create phone', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $axlMock = mock(AxlServiceInterface::class);
    $axlMock->shouldReceive('listPhones')->andReturn([]);
    $axlMock->shouldReceive('addPhone')
        ->once()
        ->with(\Mockery::on(function (array $phone): bool {
            return $phone['name'] === 'CSFdemo'
                && $phone['product'] === 'Cisco Unified Client Services Framework';
        }))
        ->andReturn((object) []);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.devices.index')
        ->set('showForm', true)
        ->set('formName', 'CSFdemo')
        ->set('formDescription', 'Demo')
        ->call('save')
        ->assertSet('showForm', false);
});

test('devices page can save location metadata for physical phone', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    CiscoPhysicalDeviceMetadata::factory()->create([
        'device_name' => 'SEP001122334455',
        'standort' => 'Bielefeld',
        'raum' => '212',
    ]);

    mock(AxlServiceInterface::class)
        ->shouldReceive('listPhones')
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
                'standort' => 'Bielefeld',
                'raum' => '212',
                'etage' => '',
                'haus' => '',
            ],
        ])
        ->shouldReceive('getPhone')
        ->once()
        ->with('SEP001122334455')
        ->andReturn((object) [
            'name' => 'SEP001122334455',
            'description' => 'Büro Telefon',
            'product' => 'Cisco 7841',
            'protocol' => 'SIP',
            'devicePoolName' => 'Default',
            'ownerUserName' => 'max.mustermann',
        ])
        ->shouldReceive('updatePhone')
        ->once()
        ->andReturn((object) [])
        ->shouldReceive('applyPhone')
        ->once()
        ->with('SEP001122334455')
        ->andReturn((object) []);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.devices.index')
        ->call('openEditForm', 'SEP001122334455')
        ->assertSet('formStandort', 'Bielefeld')
        ->assertSet('formRaum', '212')
        ->set('formEtage', '1')
        ->set('formHaus', 'Nord')
        ->call('save')
        ->assertSet('showForm', false);

    $metadata = CiscoPhysicalDeviceMetadata::query()->where('device_name', 'SEP001122334455')->first();

    expect($metadata->etage)->toBe('1')
        ->and($metadata->haus)->toBe('Nord');
});
