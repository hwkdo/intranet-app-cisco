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

test('users page lists cucm end users', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mock(AxlServiceInterface::class)
        ->shouldReceive('listUsers')
        ->once()
        ->andReturn([
            [
                'userid' => 'demo.user',
                'first_name' => 'Demo',
                'last_name' => 'User',
                'mailid' => 'demo@example.test',
                'department' => 'IT',
                'uuid' => '11111111-1111-1111-1111-111111111111',
            ],
        ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.users.index')
        ->assertSee('CUCM End User')
        ->assertSee('demo.user')
        ->assertSee('Demo')
        ->assertSee('User');
});

test('users page can create user', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $axlMock = mock(AxlServiceInterface::class);
    $axlMock->shouldReceive('listUsers')->andReturn([]);
    $axlMock->shouldReceive('addUser')
        ->once()
        ->with([
            'userid' => 'demo.user',
            'firstName' => 'Demo',
            'lastName' => 'User',
            'mailid' => 'demo@example.test',
        ])
        ->andReturn((object) []);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.users.index')
        ->set('showForm', true)
        ->set('formUserid', 'demo.user')
        ->set('formFirstName', 'Demo')
        ->set('formLastName', 'User')
        ->set('formMailid', 'demo@example.test')
        ->call('save')
        ->assertSet('showForm', false);
});
