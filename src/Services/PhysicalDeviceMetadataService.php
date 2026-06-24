<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Services;

use Hwkdo\IntranetAppCisco\Models\CiscoPhysicalDeviceMetadata;
use Hwkdo\IntranetAppCisco\Support\CiscoModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class PhysicalDeviceMetadataService
{
    public function __construct(
        private readonly LineEmployeeResolver $lineEmployeeResolver,
    ) {}

    public function isPhysicalDevice(string $name): bool
    {
        $name = strtoupper($name);

        return str_starts_with($name, 'SEP') || str_starts_with($name, 'AN5');
    }

    /**
     * @param  array<string, mixed>  $device
     */
    public function resolveUserForDevice(array $device): ?Model
    {
        $ownerUserName = trim((string) ($device['owner_user_name'] ?? ''));

        if ($ownerUserName !== '') {
            /** @var class-string<Model> $userClass */
            $userClass = CiscoModels::userClass();

            $user = $userClass::query()->where('username', $ownerUserName)->first();

            if ($user !== null) {
                return $user;
            }
        }

        $line = $device['lines'][0] ?? [];
        $lineForResolver = array_merge($line, [
            'description' => (string) ($device['description'] ?? ''),
            'alerting_name' => (string) ($device['description'] ?? ''),
        ]);

        $resolved = $this->lineEmployeeResolver->resolveForLine($lineForResolver);

        if ($resolved !== null) {
            return $resolved->user;
        }

        $resolved = $this->lineEmployeeResolver->resolveFromLabel((string) ($device['description'] ?? ''));

        if ($resolved !== null) {
            return $resolved->user;
        }

        return null;
    }

    /**
     * @return array{standort: ?string, raum: ?string}
     */
    public function initialLocationFromUser(Model $user): array
    {
        $user->loadMissing('standort');

        $standort = trim((string) ($user->standort?->name ?? ''));
        $raum = trim((string) ($user->raum ?? ''));

        return [
            'standort' => $standort !== '' ? $standort : null,
            'raum' => $raum !== '' ? $raum : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $device
     */
    public function ensureForDevice(array $device): ?CiscoPhysicalDeviceMetadata
    {
        $deviceName = (string) ($device['name'] ?? '');

        if (! $this->isPhysicalDevice($deviceName)) {
            return null;
        }

        $existing = CiscoPhysicalDeviceMetadata::query()
            ->where('device_name', $deviceName)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $user = $this->resolveUserForDevice($device);
        $location = $user !== null ? $this->initialLocationFromUser($user) : ['standort' => null, 'raum' => null];

        return CiscoPhysicalDeviceMetadata::query()->create([
            'device_name' => $deviceName,
            'standort' => $location['standort'],
            'raum' => $location['raum'],
            'etage' => null,
            'haus' => null,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $devices
     */
    public function ensureForDevices(array $devices): void
    {
        foreach ($devices as $device) {
            $this->ensureForDevice($device);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $devices
     * @return array<int, array<string, mixed>>
     */
    public function mergeIntoDevices(array $devices): array
    {
        $physicalNames = array_values(array_filter(
            array_map(fn (array $device): string => (string) ($device['name'] ?? ''), $devices),
            fn (string $name): bool => $this->isPhysicalDevice($name),
        ));

        $metadataByName = CiscoPhysicalDeviceMetadata::query()
            ->whereIn('device_name', $physicalNames)
            ->get()
            ->keyBy('device_name');

        return array_map(function (array $device) use ($metadataByName): array {
            $name = (string) ($device['name'] ?? '');

            if (! $this->isPhysicalDevice($name)) {
                return array_merge($device, [
                    'standort' => null,
                    'raum' => null,
                    'etage' => null,
                    'haus' => null,
                ]);
            }

            $metadata = $metadataByName->get($name);

            return array_merge($device, [
                'standort' => $metadata?->standort ?? '',
                'raum' => $metadata?->raum ?? '',
                'etage' => $metadata?->etage ?? '',
                'haus' => $metadata?->haus ?? '',
            ]);
        }, $devices);
    }

    /**
     * @param  array{standort?: ?string, raum?: ?string, etage?: ?string, haus?: ?string}  $data
     */
    public function update(string $deviceName, array $data): CiscoPhysicalDeviceMetadata
    {
        if (! $this->isPhysicalDevice($deviceName)) {
            throw ValidationException::withMessages([
                'formName' => 'Standort-Metadaten können nur für physische Geräte gespeichert werden.',
            ]);
        }

        $metadata = CiscoPhysicalDeviceMetadata::query()
            ->where('device_name', $deviceName)
            ->first();

        if ($metadata === null) {
            throw ValidationException::withMessages([
                'formName' => 'Keine Standort-Metadaten für dieses Gerät gefunden.',
            ]);
        }

        $metadata->update([
            'standort' => $this->nullableString($data['standort'] ?? null),
            'raum' => $this->nullableString($data['raum'] ?? null),
            'etage' => $this->nullableString($data['etage'] ?? null),
            'haus' => $this->nullableString($data['haus'] ?? null),
        ]);

        return $metadata->fresh();
    }

    private function nullableString(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
