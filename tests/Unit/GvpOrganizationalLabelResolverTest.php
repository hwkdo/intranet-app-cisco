<?php

declare(strict_types=1);

use Hwkdo\IntranetAppCisco\Support\GvpOrganizationalLabelResolver;

test('gvp organizational label resolver splits group and parent department for G users', function () {
    $labels = app(GvpOrganizationalLabelResolver::class)->resolve((object) [
        'kuerzel' => 'G',
        'nummer' => '2',
        'name' => 'Empfang',
        'bezeichnung' => 'G 2 Empfang',
        'parent' => (object) [
            'kuerzel' => 'A',
            'nummer' => '1',
            'name' => 'Zentrale Dienste',
            'bezeichnung' => 'A 1 Zentrale Dienste',
        ],
    ]);

    expect($labels['group'])->toBe('G 2 Empfang')
        ->and($labels['department'])->toBe('A 1 Zentrale Dienste');
});

test('gvp organizational label resolver splits group and parent department for FB users', function () {
    $labels = app(GvpOrganizationalLabelResolver::class)->resolve((object) [
        'kuerzel' => 'FB',
        'nummer' => '4',
        'name' => 'IT-Betrieb',
        'bezeichnung' => 'FB 4 IT-Betrieb',
        'parent' => (object) [
            'kuerzel' => 'A',
            'nummer' => '3',
            'name' => 'IT',
            'bezeichnung' => 'A 3 IT',
        ],
    ]);

    expect($labels['group'])->toBe('FB 4 IT-Betrieb')
        ->and($labels['department'])->toBe('A 3 IT');
});

test('gvp organizational label resolver keeps other gvp types in department only', function () {
    $labels = app(GvpOrganizationalLabelResolver::class)->resolve((object) [
        'kuerzel' => 'A',
        'nummer' => '3',
        'name' => 'IT',
        'bezeichnung' => 'A 3 IT',
        'parent' => null,
    ]);

    expect($labels['group'])->toBeNull()
        ->and($labels['department'])->toBe('A 3 IT');
});
