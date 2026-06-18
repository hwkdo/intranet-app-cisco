<?php

declare(strict_types=1);

use Hwkdo\IntranetAppCisco\Support\ExtensionNormalizer;

test('extension normalizer extracts extension from configured pattern prefix', function () {
    config()->set('cisco-phone-services-laravel.axl.pattern', '\+492315493');

    expect(ExtensionNormalizer::toExtension('\+492315493518'))->toBe(518)
        ->and(ExtensionNormalizer::toExtension('+492315493110'))->toBe(110);
});

test('extension normalizer accepts plain three digit extensions', function () {
    expect(ExtensionNormalizer::toExtension('518'))->toBe(518)
        ->and(ExtensionNormalizer::toExtension('110'))->toBe(110);
});

test('extension normalizer rejects out of range extensions', function () {
    expect(ExtensionNormalizer::toExtension('99'))->toBeNull()
        ->and(ExtensionNormalizer::toExtension('1000'))->toBeNull();
});

test('extension normalizer falls back to last three digits', function () {
    config()->set('cisco-phone-services-laravel.axl.pattern', '');

    expect(ExtensionNormalizer::toExtension('+4912345678950'))->toBe(950);
});
