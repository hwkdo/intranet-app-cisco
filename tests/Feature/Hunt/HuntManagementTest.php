<?php

declare(strict_types=1);

use App\Models\User;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\mock;

beforeEach(function () {
    Permission::findOrCreate('manage-app-cisco', 'web');
});

test('hunt pilots page lists hunt pilots', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mock(AxlServiceInterface::class)
        ->shouldReceive('listHuntPilots')
        ->once()
        ->andReturn([
            [
                'pattern' => '\+492315493518',
                'description' => 'Zentrale',
                'alerting_name' => 'Zentrale',
                'uuid' => '11111111-1111-1111-1111-111111111111',
                'hunt_list_name' => 'HL-Zentrale',
                'route_partition' => 'PHONES',
            ],
        ])
        ->shouldReceive('listHuntLists')
        ->once()
        ->andReturn([]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.hunt-pilots.index')
        ->assertSee('Hunt Pilots (Sammelrufnummern)')
        ->assertSee('HL-Zentrale');
});

test('hunt pilots page can create hunt pilot', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $axlMock = mock(AxlServiceInterface::class);
    $axlMock->shouldReceive('listHuntPilots')->andReturn([]);
    $axlMock->shouldReceive('listHuntLists')->andReturn([
        ['name' => 'HL-Zentrale', 'description' => '', 'uuid' => '', 'call_manager_group' => 'Default', 'route_list_enabled' => false, 'voice_mail_usage' => false],
    ]);
    $axlMock->shouldReceive('addHuntPilot')
        ->once()
        ->with([
            'pattern' => '\+492315493518',
            'description' => 'Zentrale',
            'huntListName' => 'HL-Zentrale',
            'alertingName' => 'Zentrale',
        ])
        ->andReturn((object) []);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.hunt-pilots.index')
        ->set('showForm', true)
        ->set('formPattern', '\+492315493518')
        ->set('formDescription', 'Zentrale')
        ->set('formHuntListName', 'HL-Zentrale')
        ->set('formAlertingName', 'Zentrale')
        ->call('save')
        ->assertSet('showForm', false);
});

test('hunt lists page lists hunt lists', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mock(AxlServiceInterface::class)
        ->shouldReceive('listHuntLists')
        ->once()
        ->andReturn([
            [
                'name' => 'HL-Zentrale',
                'description' => 'Zentrale Hunt List',
                'uuid' => '22222222-2222-2222-2222-222222222222',
                'call_manager_group' => 'Default',
                'route_list_enabled' => false,
                'voice_mail_usage' => false,
            ],
        ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.hunt-lists.index')
        ->assertSee('Hunt Lists (Sammellisten)')
        ->assertSee('HL-Zentrale')
        ->assertSee('Zentrale Hunt List');
});

test('hunt lists page can create hunt list', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $axlMock = mock(AxlServiceInterface::class);
    $axlMock->shouldReceive('listHuntLists')->andReturn([]);
    $axlMock->shouldReceive('addHuntList')
        ->once()
        ->with([
            'name' => 'HL-Zentrale',
            'description' => 'Zentrale Hunt List',
            'callManagerGroupName' => 'Default',
        ])
        ->andReturn((object) []);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.hunt-lists.index')
        ->set('showForm', true)
        ->set('formName', 'HL-Zentrale')
        ->set('formDescription', 'Zentrale Hunt List')
        ->set('formCallManagerGroup', 'Default')
        ->call('save')
        ->assertSet('showForm', false);
});

test('line groups page lists line groups', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mock(AxlServiceInterface::class)
        ->shouldReceive('listLineGroups')
        ->once()
        ->andReturn([
            [
                'name' => 'LG-Zentrale',
                'uuid' => '33333333-3333-3333-3333-333333333333',
                'distribution_algorithm' => 'Longest Idle Time',
                'rna_reversion_timeout' => 10,
                'auto_log_off_hunt' => false,
            ],
        ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.line-groups.index')
        ->assertSee('Line Groups (Leitungsgruppen)')
        ->assertSee('LG-Zentrale');
});

test('line groups page can create line group', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $axlMock = mock(AxlServiceInterface::class);
    $axlMock->shouldReceive('listLineGroups')->andReturn([]);
    $axlMock->shouldReceive('addLineGroup')
        ->once()
        ->with([
            'name' => 'LG-Zentrale',
            'distributionAlgorithm' => 'Longest Idle Time',
            'rnaReversionTimeOut' => 10,
        ])
        ->andReturn((object) []);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.line-groups.index')
        ->set('showForm', true)
        ->set('formName', 'LG-Zentrale')
        ->set('formDistributionAlgorithm', 'Longest Idle Time')
        ->set('formRnaReversionTimeout', 10)
        ->call('save')
        ->assertSet('showForm', false);
});
