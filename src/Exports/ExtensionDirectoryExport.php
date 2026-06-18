<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExtensionDirectoryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  array<int, array{extension: int, extension_display: string, is_free: bool, remark: string, department: string|null}>  $entries
     */
    public function __construct(
        private readonly array $entries,
    ) {}

    public function collection(): Collection
    {
        return collect($this->entries);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Durchwahl',
            'Bemerkung',
            'Abteilung',
        ];
    }

    /**
     * @param  array{extension: int, extension_display: string, is_free: bool, remark: string, remark_lines: list<string>, department: string|null}  $entry
     * @return list<string|int>
     */
    public function map($entry): array
    {
        $remark = 'FREI';

        if (! $entry['is_free']) {
            $remark = $entry['remark_lines'] !== []
                ? implode("\n", $entry['remark_lines'])
                : $entry['remark'];
        }

        return [
            $entry['extension_display'],
            $remark,
            $entry['department'] ?? '',
        ];
    }
}
