<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Support;

class EmployeeNameParser
{
    /**
     * @return array{vorname: string, nachname: string}|null
     */
    public static function parse(?string $label): ?array
    {
        if ($label === null) {
            return null;
        }

        $label = self::cleanLineLabel($label);

        if ($label === '') {
            return null;
        }

        if (str_contains($label, ',')) {
            [$nachname, $vorname] = array_map('trim', explode(',', $label, 2));

            if ($nachname !== '' && $vorname !== '') {
                return [
                    'vorname' => self::stripTitles($vorname),
                    'nachname' => $nachname,
                ];
            }
        }

        $parts = preg_split('/\s+/u', $label) ?: [];

        if (count($parts) < 2) {
            return null;
        }

        $nachname = (string) array_pop($parts);
        $vorname = self::stripTitles(implode(' ', $parts));

        if ($vorname === '' || $nachname === '') {
            return null;
        }

        return [
            'vorname' => $vorname,
            'nachname' => $nachname,
        ];
    }

    public static function cleanLineLabel(string $label): string
    {
        $label = trim(preg_replace('/\s+/u', ' ', $label) ?? '');

        $label = (string) preg_replace('/\s+\+\d[\d\s\(\)\-]*(?:\s+\S+)*$/u', '', $label);
        $label = (string) preg_replace('/\s+\d{3}$/u', '', $label);
        $label = (string) preg_replace('/\s+(?:doard|doruh)\s+DE$/iu', '', $label);

        return trim($label);
    }

    public static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return str_replace(
            ['ä', 'ö', 'ü', 'ß'],
            ['ae', 'oe', 'ue', 'ss'],
            $value
        );
    }

    private static function stripTitles(string $value): string
    {
        return trim((string) preg_replace(
            '/^(?:dr\.?|prof\.?|dipl\.?-?\s*ing\.?|ing\.?)\s+/iu',
            '',
            trim($value)
        ));
    }
}
