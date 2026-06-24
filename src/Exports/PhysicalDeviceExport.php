<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PhysicalDeviceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  array<int, array<string, mixed>>  $devices
     */
    public function __construct(
        private readonly array $devices,
    ) {}

    public function collection(): Collection
    {
        return collect($this->devices);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Name',
            'Line(s)',
            'Beschreibung',
            'Produkt',
            'Device Pool',
            'Standort',
            'Raum',
            'Etage',
            'Haus',
        ];
    }

    /**
     * @param  array<string, mixed>  $device
     * @return list<string>
     */
    public function map($device): array
    {
        return [
            (string) ($device['name'] ?? ''),
            $this->formatLines($device['lines'] ?? []),
            (string) ($device['description'] ?? ''),
            (string) ($device['product'] ?? ''),
            (string) ($device['device_pool'] ?? ''),
            (string) ($device['standort'] ?? ''),
            (string) ($device['raum'] ?? ''),
            (string) ($device['etage'] ?? ''),
            (string) ($device['haus'] ?? ''),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function formatLines(array $lines): string
    {
        if ($lines === []) {
            return '';
        }

        $formatted = [];

        foreach ($lines as $line) {
            $pattern = (string) ($line['pattern'] ?? '');
            $partition = (string) ($line['route_partition'] ?? '');

            $formatted[] = $partition !== ''
                ? $pattern.' ('.$partition.')'
                : $pattern;
        }

        return implode("\n", $formatted);
    }
}
