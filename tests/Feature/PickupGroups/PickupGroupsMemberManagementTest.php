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

test('pickup group show page requires authentication', function () {
    $response = $this->get(route('apps.cisco.pickup-groups.show', ['groupUuid' => '12345678-1234-1234-1234-123456789012']));

    $response->assertRedirect(route('login', absolute: false));
});

test('pickup group show page requires manage-app-cisco permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('apps.cisco.pickup-groups.show', ['groupUuid' => '12345678-1234-1234-1234-123456789012']));

    $response->assertForbidden();
});

test('pickup group show page displays group details and members', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $groupUuid = '12345678-1234-1234-1234-123456789012';

    $mockGroup = (object) [
        'uuid' => $groupUuid,
        'name' => 'TestGroup',
        'pattern' => '999901',
        'description' => 'Test Description',
    ];

    $axlMock = mock(AxlServiceInterface::class);
    $axlMock->shouldReceive('getCallPickupGroup')
        ->once()
        ->with($groupUuid)
        ->andReturn($mockGroup);
    $axlMock->shouldReceive('getPickupGroupMembers')
        ->once()
        ->with('TestGroup')
        ->andReturn(['1001', '1002']);

    Volt::test('apps.cisco.pickup-groups.show', ['groupUuid' => $groupUuid])
        ->actingAs($user)
        ->assertSee('TestGroup')
        ->assertSee('Test Description')
        ->assertSee('1001')
        ->assertSee('1002');
});

test('pickup group show page displays empty state when no members', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $groupUuid = '12345678-1234-1234-1234-123456789012';

    $mockGroup = (object) [
        'uuid' => $groupUuid,
        'name' => 'TestGroup',
        'pattern' => '999901',
        'description' => 'Test Description',
    ];

    $axlMock = mock(AxlServiceInterface::class);
    $axlMock->shouldReceive('getCallPickupGroup')
        ->once()
        ->with($groupUuid)
        ->andReturn($mockGroup);
    $axlMock->shouldReceive('getPickupGroupMembers')
        ->once()
        ->with('TestGroup')
        ->andReturn([]);

    Volt::test('apps.cisco.pickup-groups.show', ['groupUuid' => $groupUuid])
        ->actingAs($user)
        ->assertSee('Diese Pickup Group hat noch keine Mitglieder');
});

test('pickup group show page can add member', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $groupUuid = '12345678-1234-1234-1234-123456789012';

    $mockGroup = (object) [
        'uuid' => $groupUuid,
        'name' => 'TestGroup',
        'pattern' => '999901',
        'description' => 'Test Description',
    ];

    $axlMock = mock(AxlServiceInterface::class);
    $axlMock->shouldReceive('getCallPickupGroup')
        ->with($groupUuid)
        ->andReturn($mockGroup);
    $axlMock->shouldReceive('getPickupGroupMembers')
        ->with('TestGroup')
        ->andReturn([], ['\+492315493518']);
    $axlMock->shouldReceive('setLinePickupGroupName')
        ->once()
        ->with('\+492315493518', 'TestGroup')
        ->andReturn((object) []);

    Volt::test('apps.cisco.pickup-groups.show', ['groupUuid' => $groupUuid])
        ->actingAs($user)
        ->set('newMemberDirectoryNumber', '518')
        ->call('addMember')
        ->assertSee('\+492315493518');
});

test('pickup group show page can remove member', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $groupUuid = '12345678-1234-1234-1234-123456789012';

    $mockGroup = (object) [
        'uuid' => $groupUuid,
        'name' => 'TestGroup',
        'pattern' => '999901',
        'description' => 'Test Description',
    ];

    $axlMock = mock(AxlServiceInterface::class);
    $axlMock->shouldReceive('getCallPickupGroup')
        ->with($groupUuid)
        ->andReturn($mockGroup);
    $axlMock->shouldReceive('getPickupGroupMembers')
        ->with('TestGroup')
        ->andReturn(['\+492315493518'], []);
    $axlMock->shouldReceive('setLinePickupGroupName')
        ->once()
        ->with('\+492315493518', '')
        ->andReturn((object) []);

    Volt::test('apps.cisco.pickup-groups.show', ['groupUuid' => $groupUuid])
        ->actingAs($user)
        ->call('removeMemberFromGroup', '\+492315493518')
        ->assertDontSee('\+492315493518');
});

test('pickup group show page validates empty directory number when adding', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $groupUuid = '12345678-1234-1234-1234-123456789012';

    $mockGroup = (object) [
        'uuid' => $groupUuid,
        'name' => 'TestGroup',
        'pattern' => '999901',
        'description' => 'Test Description',
    ];

    $axlMock = mock(AxlServiceInterface::class);
    $axlMock->shouldReceive('getCallPickupGroup')
        ->once()
        ->with($groupUuid)
        ->andReturn($mockGroup);
    $axlMock->shouldReceive('getPickupGroupMembers')
        ->once()
        ->with('TestGroup')
        ->andReturn([]);

    Volt::test('apps.cisco.pickup-groups.show', ['groupUuid' => $groupUuid])
        ->actingAs($user)
        ->set('newMemberDirectoryNumber', '')
        ->call('addMember')
        ->assertHasNoErrors();
});
