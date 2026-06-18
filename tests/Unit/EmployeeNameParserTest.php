<?php

declare(strict_types=1);

use Hwkdo\IntranetAppCisco\Support\EmployeeNameParser;

test('employee name parser reads nachname vorname format', function () {
    expect(EmployeeNameParser::parse('Mustermann, Max'))->toBe([
        'vorname' => 'Max',
        'nachname' => 'Mustermann',
    ]);
});

test('employee name parser reads vorname nachname format', function () {
    expect(EmployeeNameParser::parse('Max Mustermann'))->toBe([
        'vorname' => 'Max',
        'nachname' => 'Mustermann',
    ]);
});

test('employee name parser strips common titles', function () {
    expect(EmployeeNameParser::parse('Dr. Max Mustermann'))->toBe([
        'vorname' => 'Max',
        'nachname' => 'Mustermann',
    ]);
});

test('employee name parser strips cisco line description suffixes', function () {
    expect(EmployeeNameParser::parse('Cheyenne Iskur +492315493210 doard DE'))->toBe([
        'vorname' => 'Cheyenne',
        'nachname' => 'Iskur',
    ])->and(EmployeeNameParser::parse('Dirk Drewello 960'))->toBe([
        'vorname' => 'Dirk',
        'nachname' => 'Drewello',
    ]);
});

test('employee name parser normalizes umlauts', function () {
    expect(EmployeeNameParser::normalize('Müller'))->toBe('mueller');
});

test('employee name parser returns null for empty labels', function () {
    expect(EmployeeNameParser::parse(null))->toBeNull()
        ->and(EmployeeNameParser::parse(''))->toBeNull()
        ->and(EmployeeNameParser::parse('Einzelwort'))->toBeNull();
});
