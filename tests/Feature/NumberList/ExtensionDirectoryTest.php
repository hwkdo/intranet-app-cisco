<?php

declare(strict_types=1);

use App\Models\Gvp;
use App\Models\User;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\IntranetAppCisco\Models\CiscoExtensionReservation;
use Hwkdo\IntranetAppCisco\Services\ExtensionDirectoryBuilder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\mock;

beforeEach(function () {
    Permission::findOrCreate('manage-app-cisco', 'web');
    config()->set('cisco-phone-services-laravel.axl.pattern', '\+492315493');
});

function mockNumberListAxlService(): void
{
    mock(AxlServiceInterface::class)
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
        ->andReturn([
            [
                'name' => 'PG-Zentrale',
                'description' => '',
                'uuid' => '11111111-1111-1111-1111-111111111111',
                'pattern' => '\+492315493110',
            ],
        ])
        ->shouldReceive('getPickupGroupMembers')
        ->with('PG-Zentrale')
        ->andReturn([])
        ->shouldReceive('listHuntPilots')
        ->andReturn([
            [
                'pattern' => '\+492315493950',
                'description' => 'Fax Eingang',
                'alerting_name' => '',
                'uuid' => '',
                'hunt_list_name' => 'HL-Fax',
                'route_partition' => 'PHONES',
            ],
        ]);
}

test('extension directory builder marks occupied and free extensions', function () {
    mockNumberListAxlService();

    CiscoExtensionReservation::factory()->range('955', '957')->create([
        'description' => 'Fax Pool',
    ]);

    $entries = app(ExtensionDirectoryBuilder::class)->build();

    expect($entries)->toHaveCount(900)
        ->and(collect($entries)->firstWhere('extension', 518)['remark'])->toBe('Line: Max Mustermann')
        ->and(collect($entries)->firstWhere('extension', 518)['remark_lines'])->toBe(['Line: Max Mustermann'])
        ->and(collect($entries)->firstWhere('extension', 110)['remark'])->toBe('Pickup Group: PG-Zentrale')
        ->and(collect($entries)->firstWhere('extension', 950)['remark'])->toBe('Hunt Pilot: Fax Eingang')
        ->and(collect($entries)->firstWhere('extension', 956)['remark'])->toBe('Reservierung: Fax Pool')
        ->and(collect($entries)->firstWhere('extension', 100)['remark'])->toBe('FREI')
        ->and(collect($entries)->firstWhere('extension', 100)['is_free'])->toBeTrue();
});

test('number list page shows occupied and free extensions', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mockNumberListAxlService();

    CiscoExtensionReservation::factory()->create([
        'extension_from' => '200',
        'description' => 'Test reserviert',
    ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.number-list.index')
        ->assertSee('Nummernliste (100–999)')
        ->assertSee('518')
        ->assertSee('Line: Max Mustermann')
        ->assertSee('FREI');
});

test('number list page shows department for resolved line users', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $gvp = Gvp::factory()->create([
        'kuerzel' => 'GB',
        'nummer' => '42',
        'name' => 'Vertrieb',
    ]);

    User::factory()->create([
        'vorname' => 'Max',
        'nachname' => 'Mustermann',
        'active' => true,
        'gvp_id' => $gvp->id,
    ]);

    mockNumberListAxlService();

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.number-list.index')
        ->assertSee('Abteilung')
        ->assertSee('GB 42 Vertrieb');
});

test('number list page can filter to only free extensions', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mockNumberListAxlService();

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.number-list.index')
        ->set('showOnlyFree', true)
        ->assertDontSee('Line: Max Mustermann')
        ->assertSee('FREI');
});
