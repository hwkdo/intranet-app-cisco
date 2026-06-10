<?php

declare(strict_types=1);

use App\Models\User;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\mock;

beforeEach(function () {
    Permission::findOrCreate('manage-app-cisco', 'web');
});

test('pickup groups index page requires authentication', function () {
    $response = $this->get(route('apps.cisco.pickup-groups.index'));

    $response->assertRedirect(route('login', absolute: false));
});

test('pickup groups index page requires manage-app-cisco permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('apps.cisco.pickup-groups.index'));

    $response->assertForbidden();
});

test('pickup groups index page displays list of groups', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $mockGroups = [
        ['name' => 'Group1', 'description' => 'First Group', 'uuid' => '11111111-1111-1111-1111-111111111111', 'pattern' => '999901'],
        ['name' => 'Group2', 'description' => 'Second Group', 'uuid' => '22222222-2222-2222-2222-222222222222', 'pattern' => '999902'],
    ];

    mock(AxlServiceInterface::class)
        ->shouldReceive('listCallPickupGroups')
        ->once()
        ->andReturn($mockGroups);

    Volt::test('apps.cisco.pickup-groups.index')
        ->actingAs($user)
        ->assertSee('Pickup Groups')
        ->assertSee('Group1')
        ->assertSee('Group2')
        ->assertSee('999901')
        ->assertSee('999902');
});

test('pickup groups index page shows empty state when no groups exist', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mock(AxlServiceInterface::class)
        ->shouldReceive('listCallPickupGroups')
        ->once()
        ->andReturn([]);

    Volt::test('apps.cisco.pickup-groups.index')
        ->actingAs($user)
        ->assertSee('Keine Pickup Groups gefunden');
});

test('pickup groups index page can refresh groups', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $mockGroups = [
        ['name' => 'Group1', 'description' => 'First Group', 'uuid' => '11111111-1111-1111-1111-111111111111', 'pattern' => '999901'],
    ];

    mock(AxlServiceInterface::class)
        ->shouldReceive('listCallPickupGroups')
        ->twice()
        ->andReturn($mockGroups);

    Volt::test('apps.cisco.pickup-groups.index')
        ->actingAs($user)
        ->call('refresh')
        ->assertSee('Group1');
});
