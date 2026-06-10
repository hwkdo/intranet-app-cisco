<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['web','auth','can:see-app-cisco'])->group(function () {        
    Volt::route('apps/cisco', 'apps.cisco.index')->name('apps.cisco.index');
    Volt::route('apps/cisco/example', 'apps.cisco.example')->name('apps.cisco.example');
    Volt::route('apps/cisco/settings/user', 'apps.cisco.settings.user')->name('apps.cisco.settings.user');
    Volt::route('apps/cisco/info', 'apps.cisco.info')->name('apps.cisco.info');
});

Route::middleware(['web','auth','can:manage-app-cisco'])->group(function () {
    Volt::route('apps/cisco/admin', 'apps.cisco.admin.index')->name('apps.cisco.admin.index');
    Volt::route('apps/cisco/pickup-groups', 'apps.cisco.pickup-groups.index')->name('apps.cisco.pickup-groups.index');
    Volt::route('apps/cisco/pickup-groups/{groupUuid}', 'apps.cisco.pickup-groups.show')->name('apps.cisco.pickup-groups.show');

    Route::livewire('apps/cisco/devices', 'intranet-app-cisco::apps.cisco.devices.index')->name('apps.cisco.devices.index');
    Route::livewire('apps/cisco/lines', 'intranet-app-cisco::apps.cisco.lines.index')->name('apps.cisco.lines.index');
    Route::livewire('apps/cisco/users', 'intranet-app-cisco::apps.cisco.users.index')->name('apps.cisco.users.index');
});
