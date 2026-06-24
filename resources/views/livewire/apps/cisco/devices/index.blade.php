<?php

use Flux\Flux;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlExceptionMessage;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlValueFormatter;
use Hwkdo\IntranetAppCisco\Exports\PhysicalDeviceExport;
use Hwkdo\IntranetAppCisco\Services\PhysicalDeviceMetadataService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

new #[Title('Cisco – Geräte')] class extends Component
{
    public string $search = '';

    public bool $onlyPhysicalDevices = false;

    public bool $loading = true;

    /** @var array<int, array<string, mixed>> */
    public array $devices = [];

    public bool $showForm = false;

    public bool $isEditing = false;

    public string $formName = '';

    public string $formDescription = '';

    public string $formProduct = '';

    public string $formProtocol = '';

    public string $formDevicePool = '';

    public string $formOwnerUserName = '';

    public string $formStandort = '';

    public string $formRaum = '';

    public string $formEtage = '';

    public string $formHaus = '';

    public function mount(): void
    {
        $this->formProduct = (string) config('cisco-phone-services-laravel.axl.defaults.phone.product', '');
        $this->formProtocol = (string) config('cisco-phone-services-laravel.axl.defaults.phone.protocol', 'SIP');
        $this->formDevicePool = (string) config('cisco-phone-services-laravel.axl.defaults.phone.device_pool', 'Default');

        $this->loadDevices();
    }

    public function loadDevices(): void
    {
        $this->loading = true;

        try {
            $devices = app(AxlServiceInterface::class)->listPhones();
            $metadataService = app(PhysicalDeviceMetadataService::class);
            $metadataService->ensureForDevices($devices);
            $this->devices = $metadataService->mergeIntoDevices($devices);
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden der Geräte: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
            $this->devices = [];
        }

        $this->loading = false;
    }

    #[Computed]
    public function filteredDevices(): array
    {
        $devices = $this->devices;

        if ($this->onlyPhysicalDevices) {
            $devices = array_values(array_filter(
                $devices,
                fn (array $device): bool => app(PhysicalDeviceMetadataService::class)->isPhysicalDevice((string) ($device['name'] ?? '')),
            ));
        }

        if ($this->search === '') {
            return $devices;
        }

        $search = strtolower($this->search);

        return array_values(array_filter($devices, function (array $device) use ($search): bool {
            if (str_contains(strtolower($device['name'] ?? ''), $search)
                || str_contains(strtolower($device['description'] ?? ''), $search)
                || str_contains(strtolower($device['product'] ?? ''), $search)
                || str_contains(strtolower($device['device_pool'] ?? ''), $search)
                || str_contains(strtolower((string) ($device['standort'] ?? '')), $search)
                || str_contains(strtolower((string) ($device['raum'] ?? '')), $search)
                || str_contains(strtolower((string) ($device['etage'] ?? '')), $search)
                || str_contains(strtolower((string) ($device['haus'] ?? '')), $search)) {
                return true;
            }

            foreach ($device['lines'] ?? [] as $line) {
                if (str_contains(strtolower($line['pattern'] ?? ''), $search)
                    || str_contains(strtolower($line['route_partition'] ?? ''), $search)) {
                    return true;
                }
            }

            return false;
        }));
    }

    #[Computed]
    public function totalDeviceCount(): int
    {
        return count($this->devices);
    }

    #[Computed]
    public function filteredDeviceCount(): int
    {
        return count($this->filteredDevices);
    }

    #[Computed]
    public function isDeviceFilterActive(): bool
    {
        return $this->onlyPhysicalDevices || $this->search !== '';
    }

    #[Computed]
    public function deviceCountLabel(): string
    {
        if ($this->isDeviceFilterActive) {
            $totalLabel = $this->totalDeviceCount === 1 ? 'Eintrag' : 'Einträgen';

            return sprintf(
                '%d von %d %s angezeigt',
                $this->filteredDeviceCount,
                $this->totalDeviceCount,
                $totalLabel,
            );
        }

        $count = $this->totalDeviceCount;

        return $count.' '.($count === 1 ? 'Eintrag' : 'Einträge');
    }

    public function exportExcelAll(): BinaryFileResponse
    {
        return Excel::download(
            new PhysicalDeviceExport($this->devices),
            $this->exportFilename('alle'),
        );
    }

    public function exportExcelFiltered(): BinaryFileResponse
    {
        return Excel::download(
            new PhysicalDeviceExport($this->filteredDevices),
            $this->exportFilename('gefiltert'),
        );
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showForm = true;
    }

    public function openEditForm(string $name): void
    {
        try {
            $phone = app(AxlServiceInterface::class)->getPhone($name);

            $this->formName = AxlValueFormatter::stringify($phone->name ?? $name);
            $this->formDescription = AxlValueFormatter::stringify($phone->description ?? '');
            $this->formProduct = AxlValueFormatter::stringify($phone->product ?? $this->formProduct);
            $this->formProtocol = AxlValueFormatter::stringify($phone->protocol ?? $this->formProtocol);
            $this->formDevicePool = AxlValueFormatter::stringify($phone->devicePoolName ?? $this->formDevicePool);
            $this->formOwnerUserName = AxlValueFormatter::stringify($phone->ownerUserName ?? '');
            $this->loadLocationFormFields($name);
            $this->isEditing = true;
            $this->showForm = true;
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden des Geräts: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function save(): void
    {
        $rules = [
            'formName' => ['required', 'string', 'max:128'],
            'formDescription' => ['nullable', 'string', 'max:128'],
            'formProduct' => ['required', 'string', 'max:128'],
            'formProtocol' => ['required', 'string', 'max:32'],
            'formDevicePool' => ['required', 'string', 'max:128'],
            'formOwnerUserName' => ['nullable', 'string', 'max:128'],
        ];

        if ($this->isEditingPhysicalDevice()) {
            $rules = array_merge($rules, [
                'formStandort' => ['nullable', 'string', 'max:255'],
                'formRaum' => ['nullable', 'string', 'max:255'],
                'formEtage' => ['nullable', 'string', 'max:255'],
                'formHaus' => ['nullable', 'string', 'max:255'],
            ]);
        }

        $this->validate($rules);

        try {
            $axlService = app(AxlServiceInterface::class);
            $metadataService = app(PhysicalDeviceMetadataService::class);

            if ($this->isEditing) {
                $axlService->updatePhone($this->formName, array_filter([
                    'description' => $this->formDescription,
                    'devicePoolName' => $this->formDevicePool,
                    'ownerUserName' => $this->formOwnerUserName ?: null,
                ], fn ($value) => $value !== null && $value !== ''));
                $axlService->applyPhone($this->formName);

                if ($metadataService->isPhysicalDevice($this->formName)) {
                    $metadataService->update($this->formName, [
                        'standort' => $this->formStandort,
                        'raum' => $this->formRaum,
                        'etage' => $this->formEtage,
                        'haus' => $this->formHaus,
                    ]);
                }

                Flux::toast(text: 'Gerät aktualisiert', variant: 'success');
            } else {
                $axlService->addPhone(array_filter([
                    'name' => $this->formName,
                    'description' => $this->formDescription,
                    'product' => $this->formProduct,
                    'protocol' => $this->formProtocol,
                    'devicePoolName' => $this->formDevicePool,
                    'ownerUserName' => $this->formOwnerUserName ?: null,
                ], fn ($value) => $value !== null && $value !== ''));

                if ($metadataService->isPhysicalDevice($this->formName)) {
                    $metadataService->ensureForDevice([
                        'name' => $this->formName,
                        'description' => $this->formDescription,
                        'owner_user_name' => $this->formOwnerUserName,
                        'lines' => [],
                    ]);
                }

                Flux::toast(text: 'Gerät angelegt', variant: 'success');
            }

            $this->showForm = false;
            $this->loadDevices();
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Speichern: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function delete(string $name): void
    {
        try {
            app(AxlServiceInterface::class)->removePhone($name);
            Flux::toast(text: 'Gerät gelöscht', variant: 'success');
            $this->loadDevices();
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Löschen: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function resetForm(): void
    {
        $this->formName = '';
        $this->formDescription = '';
        $this->formProduct = (string) config('cisco-phone-services-laravel.axl.defaults.phone.product', '');
        $this->formProtocol = (string) config('cisco-phone-services-laravel.axl.defaults.phone.protocol', 'SIP');
        $this->formDevicePool = (string) config('cisco-phone-services-laravel.axl.defaults.phone.device_pool', 'Default');
        $this->formOwnerUserName = '';
        $this->formStandort = '';
        $this->formRaum = '';
        $this->formEtage = '';
        $this->formHaus = '';
    }

    private function exportFilename(string $mode): string
    {
        return 'geraete-'.$mode.'-'.now()->format('Y-m-d-His').'.xlsx';
    }

    public function isEditingPhysicalDevice(): bool
    {
        return $this->isEditing
            && app(PhysicalDeviceMetadataService::class)->isPhysicalDevice($this->formName);
    }

    private function loadLocationFormFields(string $name): void
    {
        $this->formStandort = '';
        $this->formRaum = '';
        $this->formEtage = '';
        $this->formHaus = '';

        $metadataService = app(PhysicalDeviceMetadataService::class);

        if (! $metadataService->isPhysicalDevice($name)) {
            return;
        }

        $deviceFromList = collect($this->devices)->firstWhere('name', $name);
        $device = is_array($deviceFromList)
            ? array_merge($deviceFromList, ['owner_user_name' => $this->formOwnerUserName])
            : [
                'name' => $name,
                'description' => $this->formDescription,
                'owner_user_name' => $this->formOwnerUserName,
                'lines' => [],
            ];

        $metadata = $metadataService->ensureForDevice($device);

        if ($metadata === null) {
            return;
        }

        $this->formStandort = (string) ($metadata->standort ?? '');
        $this->formRaum = (string) ($metadata->raum ?? '');
        $this->formEtage = (string) ($metadata->etage ?? '');
        $this->formHaus = (string) ($metadata->haus ?? '');
    }
};
?>
<div>
<x-intranet-app-cisco::cisco-layout heading="Cisco App" subheading="Geräte">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <flux:heading>Geräte (Phones)</flux:heading>
            <div class="flex flex-wrap items-center gap-2">
                <flux:dropdown>
                    <flux:button variant="ghost" icon="arrow-down-tray" icon-trailing="chevron-down" wire:loading.attr="disabled" :disabled="$loading || empty($this->devices)">
                        Excel exportieren
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="exportExcelAll" icon="document-duplicate">Gesamte Liste exportieren</flux:menu.item>
                        <flux:menu.item wire:click="exportExcelFiltered" icon="funnel">Gefilterte Liste exportieren</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
                <flux:button wire:click="loadDevices" wire:loading.attr="disabled" icon="arrow-path" variant="ghost">Aktualisieren</flux:button>
                <flux:button wire:click="openCreateForm" icon="plus" variant="primary">Neues Gerät</flux:button>
            </div>
        </div>

        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Nach Name, Beschreibung, Line, Produkt, Device Pool oder Standort suchen..."
            icon="magnifying-glass"
        />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            @if(! $loading && ! empty($this->devices))
                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                    {{ $this->deviceCountLabel }}
                </flux:text>
            @endif
            <flux:switch wire:model.live="onlyPhysicalDevices" label="Nur phys. Geräte anzeigen" />
        </div>

        @if($loading)
            <flux:text>Lädt Geräte...</flux:text>
        @elseif(empty($this->devices))
            <flux:callout variant="subtle">Keine Geräte gefunden.</flux:callout>
        @elseif(empty($this->filteredDevices))
            <flux:callout variant="subtle">
                @if($onlyPhysicalDevices && $search === '')
                    Keine physischen Geräte gefunden.
                @elseif($search !== '')
                    Keine Geräte gefunden, die „{{ $search }}“ enthalten.
                @else
                    Keine Geräte gefunden, die den Filterkriterien entsprechen.
                @endif
            </flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Line(s)</flux:table.column>
                    <flux:table.column>Beschreibung</flux:table.column>
                    <flux:table.column>Produkt</flux:table.column>
                    <flux:table.column>Device Pool</flux:table.column>
                    <flux:table.column>Standort</flux:table.column>
                    <flux:table.column>Raum</flux:table.column>
                    <flux:table.column>Etage</flux:table.column>
                    <flux:table.column>Haus</flux:table.column>
                    <flux:table.column>Aktionen</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->filteredDevices as $device)
                        <flux:table.row wire:key="device-{{ $device['name'] }}">
                            <flux:table.cell>{{ $device['name'] }}</flux:table.cell>
                            <flux:table.cell>
                                @if(empty($device['lines']))
                                    —
                                @else
                                    @foreach($device['lines'] as $line)
                                        <div>{{ $line['pattern'] }}</div>
                                        @if($line['route_partition'])
                                            <flux:text class="text-zinc-500 dark:text-zinc-400">({{ $line['route_partition'] }})</flux:text>
                                        @endif
                                    @endforeach
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $device['description'] }}</flux:table.cell>
                            <flux:table.cell>{{ $device['product'] }}</flux:table.cell>
                            <flux:table.cell>{{ $device['device_pool'] }}</flux:table.cell>
                            <flux:table.cell>{{ $device['standort'] ?: '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $device['raum'] ?: '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $device['etage'] ?: '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $device['haus'] ?: '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button size="xs" wire:click="openEditForm(@js($device['name']))">Bearbeiten</flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="danger"
                                        wire:click="delete(@js($device['name']))"
                                        wire:confirm="Gerät wirklich löschen?"
                                    >
                                        Löschen
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal wire:model="showForm" class="md:w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $isEditing ? 'Gerät bearbeiten' : 'Gerät anlegen' }}</flux:heading>

            <flux:input wire:model="formName" label="Gerätename" placeholder="z.B. CSFmax.mustermann" :disabled="$isEditing" required />
            <flux:input wire:model="formDescription" label="Beschreibung" />
            <flux:input wire:model="formProduct" label="Produkt" :disabled="$isEditing" required />
            <flux:input wire:model="formProtocol" label="Protokoll" :disabled="$isEditing" required />
            <flux:input wire:model="formDevicePool" label="Device Pool" required />
            <flux:input wire:model="formOwnerUserName" label="Owner User ID" />

            @if($this->isEditingPhysicalDevice())
                <flux:separator />
                <flux:heading size="sm">Standort</flux:heading>
                <flux:input wire:model="formStandort" label="Standort" />
                <flux:input wire:model="formRaum" label="Raum" />
                <flux:input wire:model="formEtage" label="Etage" />
                <flux:input wire:model="formHaus" label="Haus" />
            @endif

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showForm', false)">Abbrechen</flux:button>
                <flux:button type="submit" variant="primary">Speichern</flux:button>
            </div>
        </form>
    </flux:modal>
</x-intranet-app-cisco::cisco-layout>
</div>
