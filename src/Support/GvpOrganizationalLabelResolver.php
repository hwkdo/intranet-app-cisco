<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Support;

class GvpOrganizationalLabelResolver
{
    /**
     * @return array{group: string|null, department: string|null}
     */
    public function resolve(?object $gvp): array
    {
        if ($gvp === null) {
            return [
                'group' => null,
                'department' => null,
            ];
        }

        $kuerzel = trim((string) ($gvp->kuerzel ?? ''));

        if (in_array($kuerzel, ['G', 'FB'], true)) {
            $parent = $gvp->parent ?? null;

            return [
                'group' => $this->formatLabel($gvp),
                'department' => $parent !== null ? $this->formatLabel($parent) : null,
            ];
        }

        return [
            'group' => null,
            'department' => $this->formatLabel($gvp),
        ];
    }

    private function formatLabel(object $gvp): ?string
    {
        $bezeichnung = trim((string) ($gvp->bezeichnung ?? ''));

        if ($bezeichnung !== '') {
            return $bezeichnung;
        }

        $name = trim((string) ($gvp->name ?? ''));

        return $name !== '' ? $name : null;
    }
}
