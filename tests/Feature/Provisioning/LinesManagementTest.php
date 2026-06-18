<?php

declare(strict_types=1);

use App\Models\Gvp;
use App\Models\User;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\mock;

beforeEach(function () {
    Permission::findOrCreate('manage-app-cisco', 'web');
});

function mockLinePageDependencies(AxlServiceInterface $mock): void
{
    $mock->shouldReceive('listCallingSearchSpaces')->andReturn([
        ['name' => 'CSS_National', 'description' => '', 'label' => 'National'],
        ['name' => 'CSS_International', 'description' => '', 'label' => 'International'],
    ]);
}

test('lines page lists directory numbers', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $gvp = Gvp::factory()->create([
        'kuerzel' => 'GB',
        'nummer' => '7',
        'name' => 'Support',
    ]);

    User::factory()->create([
        'vorname' => 'Max',
        'nachname' => 'Mustermann',
        'active' => true,
        'gvp_id' => $gvp->id,
    ]);

    $axlMock = mock(AxlServiceInterface::class);
    mockLinePageDependencies($axlMock);
    $axlMock->shouldReceive('listLines')
        ->once()
        ->andReturn([
            [
                'pattern' => '\+492315493518',
                'description' => 'Max Mustermann',
                'alerting_name' => 'Max Mustermann',
                'uuid' => '11111111-1111-1111-1111-111111111111',
                'usage' => 'Device',
                'route_partition' => 'PHONES',
                'calling_search_space' => 'CSS_National',
                'calling_permission' => 'National',
            ],
        ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.lines.index')
        ->assertSee('Lines (Telefonnummern)')
        ->assertSee('\+492315493518')
        ->assertSee('Max Mustermann')
        ->assertSee('GB 7 Support')
        ->assertSee('National');
});

test('lines page filters locally by description and alerting name', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $axlMock = mock(AxlServiceInterface::class);
    mockLinePageDependencies($axlMock);
    $axlMock->shouldReceive('listLines')
        ->once()
        ->andReturn([
            [
                'pattern' => '\+492315493518',
                'description' => 'Feuerwehreinfahrt',
                'alerting_name' => '',
                'uuid' => '11111111-1111-1111-1111-111111111111',
                'usage' => 'Device',
                'route_partition' => 'PHONES',
                'calling_search_space' => '',
                'calling_permission' => '—',
            ],
            [
                'pattern' => '\+492315493999',
                'description' => 'Andere Line',
                'alerting_name' => 'Büro',
                'uuid' => '22222222-2222-2222-2222-222222222222',
                'usage' => 'Device',
                'route_partition' => 'PHONES',
                'calling_search_space' => '',
                'calling_permission' => '—',
            ],
        ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.lines.index')
        ->set('search', 'feuerwehr')
        ->assertSee('Feuerwehreinfahrt')
        ->assertDontSee('Andere Line');
});

test('lines page can create line', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $axlMock = mock(AxlServiceInterface::class);
    mockLinePageDependencies($axlMock);
    $axlMock->shouldReceive('listLines')->andReturn([]);
    $axlMock->shouldReceive('addLine')
        ->once()
        ->with([
            'pattern' => '\+492315493518',
            'description' => 'Test Line',
            'alertingName' => 'Max Mustermann',
            'usage' => 'Device',
        ])
        ->andReturn((object) []);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.lines.index')
        ->set('showForm', true)
        ->set('formPattern', '\+492315493518')
        ->set('formDescription', 'Test Line')
        ->set('formAlertingName', 'Max Mustermann')
        ->call('save')
        ->assertSet('showForm', false);
});

test('lines page can update calling search space when editing', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $axlMock = mock(AxlServiceInterface::class);
    mockLinePageDependencies($axlMock);
    $axlMock->shouldReceive('listLines')->andReturn([]);
    $axlMock->shouldReceive('getLine')
        ->once()
        ->with('\+492315493518')
        ->andReturn((object) [
            'pattern' => '\+492315493518',
            'description' => 'Test Line',
            'usage' => 'Device',
            'alertingName' => 'Max Mustermann',
            'shareLineAppearanceCssName' => 'CSS_National',
        ]);
    $axlMock->shouldReceive('updateLineByPattern')
        ->once()
        ->with('\+492315493518', [
            'description' => 'Test Line',
            'alertingName' => 'Max Mustermann',
            'shareLineAppearanceCssName' => 'CSS_International',
        ])
        ->andReturn((object) []);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.lines.index')
        ->call('openEditForm', '\+492315493518')
        ->assertSet('formCallingSearchSpace', 'CSS_National')
        ->set('formCallingSearchSpace', 'CSS_International')
        ->call('save')
        ->assertSet('showForm', false);
});
