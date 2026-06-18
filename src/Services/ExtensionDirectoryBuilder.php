<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Services;

use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\IntranetAppCisco\Models\CiscoExtensionReservation;
use Hwkdo\IntranetAppCisco\Support\ExtensionNormalizer;

class ExtensionDirectoryBuilder
{
    public function __construct(
        private readonly AxlServiceInterface $axlService,
        private readonly LineEmployeeResolver $lineEmployeeResolver,
    ) {}

    /**
     * @return array<int, array{
     *     extension: int,
     *     extension_display: string,
     *     is_free: bool,
     *     remark: string,
     *     remark_lines: list<string>,
     *     group: string|null,
     *     department: string|null
     * }>
     */
    public function build(): array
    {
        /** @var array<int, list<string>> $assignments */
        $assignments = [];
        /** @var array<int, string|null> $groups */
        $groups = [];
        /** @var array<int, string|null> $departments */
        $departments = [];

        $this->addReservations($assignments);
        $this->addLines($assignments, $groups, $departments);
        $this->addPickupGroups($assignments);
        $this->addHuntPilots($assignments);

        $entries = [];

        for ($extension = ExtensionNormalizer::MIN_EXTENSION; $extension <= ExtensionNormalizer::MAX_EXTENSION; $extension++) {
            if (isset($assignments[$extension])) {
                $remarkLines = array_values(array_unique($assignments[$extension]));

                $entries[] = [
                    'extension' => $extension,
                    'extension_display' => (string) $extension,
                    'is_free' => false,
                    'remark' => implode(' · ', $remarkLines),
                    'remark_lines' => $remarkLines,
                    'group' => $groups[$extension] ?? null,
                    'department' => $departments[$extension] ?? null,
                ];

                continue;
            }

            $entries[] = [
                'extension' => $extension,
                'extension_display' => (string) $extension,
                'is_free' => true,
                'remark' => 'FREI',
                'remark_lines' => [],
                'group' => null,
                'department' => null,
            ];
        }

        return $entries;
    }

    /**
     * @param  array<int, list<string>>  $assignments
     */
    private function addReservations(array &$assignments): void
    {
        foreach (CiscoExtensionReservation::query()->get() as $reservation) {
            for ($extension = $reservation->rangeStart(); $extension <= $reservation->rangeEnd(); $extension++) {
                $this->assign($assignments, $extension, 'Reservierung: '.$reservation->description);
            }
        }
    }

    /**
     * @param  array<int, list<string>>  $assignments
     * @param  array<int, string|null>  $groups
     * @param  array<int, string|null>  $departments
     */
    private function addLines(array &$assignments, array &$groups, array &$departments): void
    {
        foreach ($this->axlService->listLines() as $line) {
            $extension = ExtensionNormalizer::toExtension($line['pattern'] ?? '');

            if ($extension === null) {
                continue;
            }

            $description = trim((string) ($line['description'] ?? ''));
            $label = $description !== '' ? $description : (string) ($line['pattern'] ?? '');

            $this->assign($assignments, $extension, 'Line: '.$label);

            $resolved = $this->lineEmployeeResolver->resolveForLine($line);

            if ($resolved === null) {
                continue;
            }

            if ($resolved->group !== '') {
                $groups[$extension] = $resolved->group;
            }

            if ($resolved->department !== '') {
                $departments[$extension] = $resolved->department;
            }
        }
    }

    /**
     * @param  array<int, list<string>>  $assignments
     */
    private function addPickupGroups(array &$assignments): void
    {
        foreach ($this->axlService->listCallPickupGroups() as $group) {
            $name = trim((string) ($group['name'] ?? ''));
            $extension = ExtensionNormalizer::toExtension($group['pattern'] ?? '');

            if ($extension !== null) {
                $label = $name !== '' ? $name : (string) ($group['pattern'] ?? '');
                $this->assign($assignments, $extension, 'Pickup Group: '.$label);
            }

            if ($name === '') {
                continue;
            }

            try {
                foreach ($this->axlService->getPickupGroupMembers($name) as $memberPattern) {
                    $memberExtension = ExtensionNormalizer::toExtension($memberPattern);

                    if ($memberExtension === null) {
                        continue;
                    }

                    $this->assign($assignments, $memberExtension, 'Pickup Group Mitglied ('.$name.')');
                }
            } catch (\Throwable) {
                continue;
            }
        }
    }

    /**
     * @param  array<int, list<string>>  $assignments
     */
    private function addHuntPilots(array &$assignments): void
    {
        foreach ($this->axlService->listHuntPilots() as $huntPilot) {
            $extension = ExtensionNormalizer::toExtension($huntPilot['pattern'] ?? '');

            if ($extension === null) {
                continue;
            }

            $description = trim((string) ($huntPilot['description'] ?? ''));
            $label = $description !== '' ? $description : (string) ($huntPilot['pattern'] ?? '');

            $this->assign($assignments, $extension, 'Hunt Pilot: '.$label);
        }
    }

    /**
     * @param  array<int, list<string>>  $assignments
     */
    private function assign(array &$assignments, int $extension, string $label): void
    {
        if (! ExtensionNormalizer::isValidExtension($extension)) {
            return;
        }

        $assignments[$extension][] = $label;
    }
}
