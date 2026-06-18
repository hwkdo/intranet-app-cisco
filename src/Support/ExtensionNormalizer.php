<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Support;

class ExtensionNormalizer
{
    public const int MIN_EXTENSION = 100;

    public const int MAX_EXTENSION = 999;

    public static function toExtension(?string $pattern): ?int
    {
        if ($pattern === null || trim($pattern) === '') {
            return null;
        }

        $normalized = str_replace('\\', '', trim($pattern));
        $prefix = str_replace('\\', '', (string) config('cisco-phone-services-laravel.axl.pattern', ''));

        if ($prefix !== '' && str_starts_with($normalized, $prefix)) {
            $suffix = substr($normalized, strlen($prefix));

            if ($suffix !== '' && ctype_digit($suffix)) {
                $extension = (int) $suffix;

                return self::isValidExtension($extension) ? $extension : null;
            }
        }

        if (ctype_digit($normalized) && strlen($normalized) <= 3) {
            $extension = (int) $normalized;

            return self::isValidExtension($extension) ? $extension : null;
        }

        $digitsOnly = preg_replace('/\D/', '', $normalized) ?? '';

        if (strlen($digitsOnly) >= 3) {
            $extension = (int) substr($digitsOnly, -3);

            return self::isValidExtension($extension) ? $extension : null;
        }

        return null;
    }

    public static function isValidExtension(int $extension): bool
    {
        return $extension >= self::MIN_EXTENSION && $extension <= self::MAX_EXTENSION;
    }
}
