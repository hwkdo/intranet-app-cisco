<?php

declare(strict_types=1);

use App\Models\User;
use Hwkdo\IntranetAppCisco\Models\CiscoExtensionReservation;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('manage-app-cisco', 'web');
});

test('admin page shows reservierungen tab', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    $this->actingAs($user)
        ->get(route('apps.cisco.admin.index'))
        ->assertOk()
        ->assertSee('Reservierungen');
});

test('reservations component can create single extension reservation', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.admin.reservations')
        ->set('showForm', true)
        ->set('formExtensionFrom', '110')
        ->set('formDescription', 'Notruf')
        ->call('save')
        ->assertSet('showForm', false);

    expect(CiscoExtensionReservation::query()->count())->toBe(1);

    $reservation = CiscoExtensionReservation::query()->first();
    expect($reservation->extension_from)->toBe('110')
        ->and($reservation->extension_to)->toBeNull()
        ->and($reservation->description)->toBe('Notruf')
        ->and($reservation->extension_display)->toBe('110');
});

test('reservations component can create extension range reservation', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.admin.reservations')
        ->set('showForm', true)
        ->set('formExtensionFrom', '950')
        ->set('formExtensionTo', '959')
        ->set('formDescription', 'Fax')
        ->call('save');

    $reservation = CiscoExtensionReservation::query()->first();
    expect($reservation->extension_display)->toBe('950 - 959')
        ->and($reservation->description)->toBe('Fax');
});

test('reservations component rejects overlapping ranges', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    CiscoExtensionReservation::factory()->range('950', '959')->create([
        'description' => 'Fax',
    ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.admin.reservations')
        ->set('showForm', true)
        ->set('formExtensionFrom', '955')
        ->set('formExtensionTo', '965')
        ->set('formDescription', 'Konflikt')
        ->call('save')
        ->assertHasErrors(['formExtensionFrom']);
});

test('reservations component lists existing reservations', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    CiscoExtensionReservation::factory()->create([
        'extension_from' => '110',
        'description' => 'Notruf',
    ]);

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.admin.reservations')
        ->assertSee('110')
        ->assertSee('Notruf');
});
