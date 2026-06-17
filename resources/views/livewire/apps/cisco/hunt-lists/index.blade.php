<?php

use Flux\Flux;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlExceptionMessage;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlValueFormatter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cisco – Hunt Lists')] class extends Component
{
    public string $search = '';

    public bool $loading = true;

    /** @var array<int, array<string, mixed>> */
    public array $huntLists = [];

    public bool $showForm = false;

    public bool $isEditing = false;

    public string $formName = '';

    public string $formDescription = '';

    public string $formCallManagerGroup = '';

    public function mount(): void
    {
        $this->formCallManagerGroup = (string) config('cisco-phone-services-laravel.axl.defaults.hunt_list.call_manager_group', 'Default');
        $this->loadHuntLists();
    }

    public function loadHuntLists(): void
    {
        $this->loading = true;

        try {
            $this->huntLists = app(AxlServiceInterface::class)->listHuntLists();
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden der Hunt Lists: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
            $this->huntLists = [];
        }

        $this->loading = false;
    }

    #[Computed]
    public function filteredHuntLists(): array
    {
        if ($this->search === '') {
            return $this->huntLists;
        }

        $search = strtolower($this->search);

        return array_values(array_filter($this->huntLists, function (array $huntList) use ($search): bool {
            return str_contains(strtolower($huntList['name'] ?? ''), $search)
                || str_contains(strtolower($huntList['description'] ?? ''), $search);
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
            $huntList = app(AxlServiceInterface::class)->getHuntList($name);

            $this->formName = AxlValueFormatter::stringify($huntList->name ?? $name);
            $this->formDescription = AxlValueFormatter::stringify($huntList->description ?? '');
            $this->formCallManagerGroup = AxlValueFormatter::stringify($huntList->callManagerGroupName ?? $this->formCallManagerGroup);
            $this->isEditing = true;
            $this->showForm = true;
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden der Hunt List: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function save(): void
    {
        $this->validate([
            'formName' => ['required', 'string', 'max:100'],
            'formDescription' => ['nullable', 'string', 'max:128'],
            'formCallManagerGroup' => ['required', 'string', 'max:100'],
        ]);

        try {
            $axlService = app(AxlServiceInterface::class);

            if ($this->isEditing) {
                $axlService->updateHuntList($this->formName, array_filter([
                    'description' => $this->formDescription,
                    'callManagerGroupName' => $this->formCallManagerGroup,
                ], fn ($value) => $value !== ''));
                Flux::toast(text: 'Hunt List aktualisiert', variant: 'success');
            } else {
                $axlService->addHuntList(array_filter([
                    'name' => $this->formName,
                    'description' => $this->formDescription,
                    'callManagerGroupName' => $this->formCallManagerGroup,
                ], fn ($value) => $value !== ''));
                Flux::toast(text: 'Hunt List angelegt', variant: 'success');
            }

            $this->showForm = false;
            $this->loadHuntLists();
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
            app(AxlServiceInterface::class)->removeHuntList($name);
            Flux::toast(text: 'Hunt List gelöscht', variant: 'success');
            $this->loadHuntLists();
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
        $this->formCallManagerGroup = (string) config('cisco-phone-services-laravel.axl.defaults.hunt_list.call_manager_group', 'Default');
    }
};
?>
<div>
<x-intranet-app-cisco::cisco-layout heading="Cisco App" subheading="Hunt Lists">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <flux:heading>Hunt Lists (Sammellisten)</flux:heading>
            <div class="flex gap-2">
                <flux:button wire:click="loadHuntLists" wire:loading.attr="disabled" icon="arrow-path" variant="ghost">Aktualisieren</flux:button>
                <flux:button wire:click="openCreateForm" icon="plus" variant="primary">Neue Hunt List</flux:button>
            </div>
        </div>

        <flux:input wire:model.live.debounce.300ms="search" placeholder="Nach Name oder Beschreibung suchen..." icon="magnifying-glass" />

        @if($loading)
            <flux:text>Lädt Hunt Lists...</flux:text>
        @elseif(empty($this->huntLists))
            <flux:callout variant="subtle">Keine Hunt Lists gefunden.</flux:callout>
        @elseif(empty($this->filteredHuntLists))
            <flux:callout variant="subtle">Keine Hunt Lists gefunden, die „{{ $search }}“ enthalten.</flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Beschreibung</flux:table.column>
                    <flux:table.column>Call Manager Group</flux:table.column>
                    <flux:table.column>Aktionen</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->filteredHuntLists as $huntList)
                        <flux:table.row wire:key="hunt-list-{{ $huntList['name'] }}">
                            <flux:table.cell>{{ $huntList['name'] }}</flux:table.cell>
                            <flux:table.cell>{{ $huntList['description'] }}</flux:table.cell>
                            <flux:table.cell>{{ $huntList['call_manager_group'] }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button
                                        size="xs"
                                        :href="route('apps.cisco.hunt-lists.show', ['identifier' => $huntList['uuid'] ?: $huntList['name']])"
                                        icon="eye"
                                        wire:navigate
                                    >
                                        Mitglieder
                                    </flux:button>
                                    <flux:button size="xs" wire:click="openEditForm(@js($huntList['name']))">Bearbeiten</flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="danger"
                                        wire:click="delete(@js($huntList['name']))"
                                        wire:confirm="Hunt List wirklich löschen?"
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
            <flux:heading size="lg">{{ $isEditing ? 'Hunt List bearbeiten' : 'Hunt List anlegen' }}</flux:heading>

            <flux:input wire:model="formName" label="Name" :disabled="$isEditing" required />
            <flux:input wire:model="formDescription" label="Beschreibung" />
            <flux:input wire:model="formCallManagerGroup" label="Call Manager Group" required />

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showForm', false)">Abbrechen</flux:button>
                <flux:button type="submit" variant="primary">Speichern</flux:button>
            </div>
        </form>
    </flux:modal>
</x-intranet-app-cisco::cisco-layout>
</div>
