<?php

use Flux\Flux;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlExceptionMessage;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlValueFormatter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cisco – Hunt Pilots')] class extends Component
{
    public string $search = '';

    public bool $loading = true;

    /** @var array<int, array<string, mixed>> */
    public array $huntPilots = [];

    /** @var array<int, array<string, mixed>> */
    public array $huntLists = [];

    public bool $showForm = false;

    public bool $isEditing = false;

    public string $formPattern = '';

    public string $formDescription = '';

    public string $formHuntListName = '';

    public string $formAlertingName = '';

    public function mount(): void
    {
        $this->loadHuntPilots();
        $this->loadHuntLists();
    }

    public function loadHuntPilots(): void
    {
        $this->loading = true;

        try {
            $this->huntPilots = app(AxlServiceInterface::class)->listHuntPilots();
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden der Hunt Pilots: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
            $this->huntPilots = [];
        }

        $this->loading = false;
    }

    public function loadHuntLists(): void
    {
        try {
            $this->huntLists = app(AxlServiceInterface::class)->listHuntLists();
        } catch (\Throwable) {
            $this->huntLists = [];
        }
    }

    #[Computed]
    public function filteredHuntPilots(): array
    {
        if ($this->search === '') {
            return $this->huntPilots;
        }

        $search = strtolower($this->search);

        return array_values(array_filter($this->huntPilots, function (array $huntPilot) use ($search): bool {
            return str_contains(strtolower($huntPilot['pattern'] ?? ''), $search)
                || str_contains(strtolower($huntPilot['description'] ?? ''), $search)
                || str_contains(strtolower($huntPilot['alerting_name'] ?? ''), $search)
                || str_contains(strtolower($huntPilot['hunt_list_name'] ?? ''), $search);
        }));
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showForm = true;
    }

    public function openEditForm(string $pattern): void
    {
        try {
            $huntPilot = app(AxlServiceInterface::class)->getHuntPilot($pattern);

            $this->formPattern = AxlValueFormatter::stringify($huntPilot->pattern ?? $pattern);
            $this->formDescription = AxlValueFormatter::stringify($huntPilot->description ?? '');
            $this->formHuntListName = AxlValueFormatter::stringify($huntPilot->huntListName ?? '');
            $this->formAlertingName = AxlValueFormatter::stringify($huntPilot->alertingName ?? '');
            $this->isEditing = true;
            $this->showForm = true;
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden des Hunt Pilots: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function save(): void
    {
        $this->validate([
            'formPattern' => ['required', 'string', 'max:128'],
            'formDescription' => ['nullable', 'string', 'max:128'],
            'formHuntListName' => ['required', 'string', 'max:100'],
            'formAlertingName' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $axlService = app(AxlServiceInterface::class);

            if ($this->isEditing) {
                $axlService->updateHuntPilotByPattern($this->formPattern, array_filter([
                    'description' => $this->formDescription,
                    'huntListName' => $this->formHuntListName,
                    'alertingName' => $this->formAlertingName,
                ], fn ($value) => $value !== ''));
                Flux::toast(text: 'Hunt Pilot aktualisiert', variant: 'success');
            } else {
                $axlService->addHuntPilot(array_filter([
                    'pattern' => $this->formPattern,
                    'description' => $this->formDescription,
                    'huntListName' => $this->formHuntListName,
                    'alertingName' => $this->formAlertingName,
                ], fn ($value) => $value !== ''));
                Flux::toast(text: 'Hunt Pilot angelegt', variant: 'success');
            }

            $this->showForm = false;
            $this->loadHuntPilots();
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Speichern: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function delete(string $pattern): void
    {
        try {
            app(AxlServiceInterface::class)->removeHuntPilot($pattern);
            Flux::toast(text: 'Hunt Pilot gelöscht', variant: 'success');
            $this->loadHuntPilots();
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Löschen: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function resetForm(): void
    {
        $this->formPattern = '';
        $this->formDescription = '';
        $this->formHuntListName = '';
        $this->formAlertingName = '';
    }
};
?>
<div>
<x-intranet-app-cisco::cisco-layout heading="Cisco App" subheading="Hunt Pilots">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <flux:heading>Hunt Pilots (Sammelrufnummern)</flux:heading>
            <div class="flex gap-2">
                <flux:button wire:click="loadHuntPilots" wire:loading.attr="disabled" icon="arrow-path" variant="ghost">Aktualisieren</flux:button>
                <flux:button wire:click="openCreateForm" icon="plus" variant="primary">Neuer Hunt Pilot</flux:button>
            </div>
        </div>

        <flux:input wire:model.live.debounce.300ms="search" placeholder="Nach Nummer, Beschreibung, Alerting Name oder Hunt List suchen..." icon="magnifying-glass" />

        @if($loading)
            <flux:text>Lädt Hunt Pilots...</flux:text>
        @elseif(empty($this->huntPilots))
            <flux:callout variant="subtle">Keine Hunt Pilots gefunden.</flux:callout>
        @elseif(empty($this->filteredHuntPilots))
            <flux:callout variant="subtle">Keine Hunt Pilots gefunden, die „{{ $search }}“ enthalten.</flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Pattern</flux:table.column>
                    <flux:table.column>Beschreibung</flux:table.column>
                    <flux:table.column>Alerting Name</flux:table.column>
                    <flux:table.column>Hunt List</flux:table.column>
                    <flux:table.column>Partition</flux:table.column>
                    <flux:table.column>Aktionen</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->filteredHuntPilots as $huntPilot)
                        <flux:table.row wire:key="hunt-pilot-{{ $huntPilot['pattern'] }}">
                            <flux:table.cell>{{ $huntPilot['pattern'] }}</flux:table.cell>
                            <flux:table.cell>{{ $huntPilot['description'] }}</flux:table.cell>
                            <flux:table.cell>{{ $huntPilot['alerting_name'] }}</flux:table.cell>
                            <flux:table.cell>{{ $huntPilot['hunt_list_name'] }}</flux:table.cell>
                            <flux:table.cell>{{ $huntPilot['route_partition'] }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button size="xs" wire:click="openEditForm(@js($huntPilot['pattern']))">Bearbeiten</flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="danger"
                                        wire:click="delete(@js($huntPilot['pattern']))"
                                        wire:confirm="Hunt Pilot wirklich löschen?"
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
            <flux:heading size="lg">{{ $isEditing ? 'Hunt Pilot bearbeiten' : 'Hunt Pilot anlegen' }}</flux:heading>

            <flux:input wire:model="formPattern" label="Pattern (Telefonnummer)" placeholder="z.B. \+492315493518" :disabled="$isEditing" required />
            <flux:input wire:model="formDescription" label="Beschreibung" />
            <flux:input wire:model="formAlertingName" label="Alerting Name" maxlength="50" />
            @if(count($huntLists) > 0)
                <flux:select wire:model="formHuntListName" label="Hunt List" required>
                    <flux:select.option value="">Bitte wählen...</flux:select.option>
                    @foreach($huntLists as $huntList)
                        <flux:select.option value="{{ $huntList['name'] }}">{{ $huntList['name'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <flux:input wire:model="formHuntListName" label="Hunt List" required />
            @endif

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showForm', false)">Abbrechen</flux:button>
                <flux:button type="submit" variant="primary">Speichern</flux:button>
            </div>
        </form>
    </flux:modal>
</x-intranet-app-cisco::cisco-layout>
</div>
