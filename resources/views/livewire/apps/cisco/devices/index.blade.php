<?php

use Flux\Flux;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlExceptionMessage;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlValueFormatter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cisco – Geräte')] class extends Component
{
    public string $search = '';

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
            $this->devices = app(AxlServiceInterface::class)->listPhones();
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
        if ($this->search === '') {
            return $this->devices;
        }

        $search = strtolower($this->search);

        return array_values(array_filter($this->devices, function (array $device) use ($search): bool {
            if (str_contains(strtolower($device['name'] ?? ''), $search)
                || str_contains(strtolower($device['description'] ?? ''), $search)
                || str_contains(strtolower($device['product'] ?? ''), $search)
                || str_contains(strtolower($device['protocol'] ?? ''), $search)) {
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
        $this->validate([
            'formName' => ['required', 'string', 'max:128'],
            'formDescription' => ['nullable', 'string', 'max:128'],
            'formProduct' => ['required', 'string', 'max:128'],
            'formProtocol' => ['required', 'string', 'max:32'],
            'formDevicePool' => ['required', 'string', 'max:128'],
            'formOwnerUserName' => ['nullable', 'string', 'max:128'],
        ]);

        try {
            $axlService = app(AxlServiceInterface::class);

            if ($this->isEditing) {
                $axlService->updatePhone($this->formName, array_filter([
                    'description' => $this->formDescription,
                    'devicePoolName' => $this->formDevicePool,
                    'ownerUserName' => $this->formOwnerUserName ?: null,
                ], fn ($value) => $value !== null && $value !== ''));
                $axlService->applyPhone($this->formName);

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
    }
};
?>
<div>
<x-intranet-app-cisco::cisco-layout heading="Cisco App" subheading="Geräte">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <flux:heading>Geräte (Phones)</flux:heading>
            <div class="flex gap-2">
                <flux:button wire:click="loadDevices" wire:loading.attr="disabled" icon="arrow-path" variant="ghost">Aktualisieren</flux:button>
                <flux:button wire:click="openCreateForm" icon="plus" variant="primary">Neues Gerät</flux:button>
            </div>
        </div>

        <flux:input wire:model.live.debounce.300ms="search" placeholder="Nach Name, Beschreibung, Line, Produkt oder Protokoll suchen..." icon="magnifying-glass" />

        @if($loading)
            <flux:text>Lädt Geräte...</flux:text>
        @elseif(empty($this->devices))
            <flux:callout variant="subtle">Keine Geräte gefunden.</flux:callout>
        @elseif(empty($this->filteredDevices))
            <flux:callout variant="subtle">Keine Geräte gefunden, die „{{ $search }}“ enthalten.</flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Line(s)</flux:table.column>
                    <flux:table.column>Beschreibung</flux:table.column>
                    <flux:table.column>Produkt</flux:table.column>
                    <flux:table.column>Protokoll</flux:table.column>
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
                                    <div class="space-y-1">
                                        @foreach($device['lines'] as $line)
                                            <div>
                                                {{ $line['pattern'] }}
                                                @if($line['route_partition'])
                                                    <span class="text-zinc-500 dark:text-zinc-400">({{ $line['route_partition'] }})</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $device['description'] }}</flux:table.cell>
                            <flux:table.cell>{{ $device['product'] }}</flux:table.cell>
                            <flux:table.cell>{{ $device['protocol'] }}</flux:table.cell>
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

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showForm', false)">Abbrechen</flux:button>
                <flux:button type="submit" variant="primary">Speichern</flux:button>
            </div>
        </form>
    </flux:modal>
</x-intranet-app-cisco::cisco-layout>
</div>