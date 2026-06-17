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

test('hunt list show page displays members', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $axlMock = mock(AxlServiceInterface::class);
    $axlMock->shouldReceive('getHuntList')
        ->once()
        ->with('HL-Zentrale')
        ->andReturn((object) [
            'name' => 'HL-Zentrale',
            'description' => 'Zentrale Hunt List',
        ]);
    $axlMock->shouldReceive('getHuntListMembers')
        ->once()
        ->with('HL-Zentrale')
        ->andReturn([
            [
                'line_group_name' => 'LG-Zentrale',
                'selection_order' => 1,
                'uuid' => '11111111-1111-1111-1111-111111111111',
            ],
        ]);
    $axlMock->shouldReceive('listLineGroups')->once()->andReturn([]);

    Volt::actingAs($user)
        ->test('apps.cisco.hunt-lists.show', ['identifier' => 'HL-Zentrale'])
        ->assertSee('Hunt List: HL-Zentrale')
        ->assertSee('LG-Zentrale');
});

test('line group show page can add member', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $axlMock = mock(AxlServiceInterface::class);
    $axlMock->shouldReceive('getLineGroup')
        ->andReturn((object) ['name' => 'LG-Zentrale', 'distributionAlgorithm' => 'Longest Idle Time']);
    $axlMock->shouldReceive('getLineGroupMembers')
        ->andReturn([]);
    $axlMock->shouldReceive('addLineGroupMember')
        ->once()
        ->with('LG-Zentrale', '\+492315493518', null, 1)
        ->andReturn((object) []);

    Volt::actingAs($user)
        ->test('apps.cisco.line-groups.show', ['identifier' => 'LG-Zentrale'])
        ->set('newMemberShortNumber', '518')
        ->call('addMember');
});
