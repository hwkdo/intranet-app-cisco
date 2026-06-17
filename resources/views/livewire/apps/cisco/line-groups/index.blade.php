<?php

use Flux\Flux;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlExceptionMessage;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlValueFormatter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cisco – Line Groups')] class extends Component
{
    public string $search = '';

    public bool $loading = true;

    /** @var array<int, array<string, mixed>> */
    public array $lineGroups = [];

    public bool $showForm = false;

    public bool $isEditing = false;

    public string $formName = '';

    public string $formDistributionAlgorithm = '';

    public int $formRnaReversionTimeout = 10;

    public function mount(): void
    {
        $defaults = config('cisco-phone-services-laravel.axl.defaults.line_group', []);
        $this->formDistributionAlgorithm = (string) ($defaults['distribution_algorithm'] ?? 'Longest Idle Time');
        $this->formRnaReversionTimeout = (int) ($defaults['rna_reversion_timeout'] ?? 10);
        $this->loadLineGroups();
    }

    public function loadLineGroups(): void
    {
        $this->loading = true;

        try {
            $this->lineGroups = app(AxlServiceInterface::class)->listLineGroups();
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden der Line Groups: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
            $this->lineGroups = [];
        }

        $this->loading = false;
    }

    #[Computed]
    public function filteredLineGroups(): array
    {
        if ($this->search === '') {
            return $this->lineGroups;
        }

        $search = strtolower($this->search);

        return array_values(array_filter($this->lineGroups, function (array $lineGroup) use ($search): bool {
            return str_contains(strtolower($lineGroup['name'] ?? ''), $search);
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
            $lineGroup = app(AxlServiceInterface::class)->getLineGroup($name);

            $this->formName = AxlValueFormatter::stringify($lineGroup->name ?? $name);
            $this->formDistributionAlgorithm = AxlValueFormatter::stringify($lineGroup->distributionAlgorithm ?? $this->formDistributionAlgorithm);
            $this->formRnaReversionTimeout = (int) ($lineGroup->rnaReversionTimeOut ?? $this->formRnaReversionTimeout);
            $this->isEditing = true;
            $this->showForm = true;
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden der Line Group: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function save(): void
    {
        $this->validate([
            'formName' => ['required', 'string', 'max:100'],
            'formDistributionAlgorithm' => ['required', 'string', 'max:64'],
            'formRnaReversionTimeout' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $axlService = app(AxlServiceInterface::class);

            if ($this->isEditing) {
                $axlService->updateLineGroup($this->formName, [
                    'distributionAlgorithm' => $this->formDistributionAlgorithm,
                    'rnaReversionTimeOut' => $this->formRnaReversionTimeout,
                ]);
                Flux::toast(text: 'Line Group aktualisiert', variant: 'success');
            } else {
                $axlService->addLineGroup([
                    'name' => $this->formName,
                    'distributionAlgorithm' => $this->formDistributionAlgorithm,
                    'rnaReversionTimeOut' => $this->formRnaReversionTimeout,
                ]);
                Flux::toast(text: 'Line Group angelegt', variant: 'success');
            }

            $this->showForm = false;
            $this->loadLineGroups();
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
            app(AxlServiceInterface::class)->removeLineGroup($name);
            Flux::toast(text: 'Line Group gelöscht', variant: 'success');
            $this->loadLineGroups();
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Löschen: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function resetForm(): void
    {
        $defaults = config('cisco-phone-services-laravel.axl.defaults.line_group', []);
        $this->formName = '';
        $this->formDistributionAlgorithm = (string) ($defaults['distribution_algorithm'] ?? 'Longest Idle Time');
        $this->formRnaReversionTimeout = (int) ($defaults['rna_reversion_timeout'] ?? 10);
    }
};
?>
<div>
<x-intranet-app-cisco::cisco-layout heading="Cisco App" subheading="Line Groups">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <flux:heading>Line Groups (Leitungsgruppen)</flux:heading>
            <div class="flex gap-2">
                <flux:button wire:click="loadLineGroups" wire:loading.attr="disabled" icon="arrow-path" variant="ghost">Aktualisieren</flux:button>
                <flux:button wire:click="openCreateForm" icon="plus" variant="primary">Neue Line Group</flux:button>
            </div>
        </div>

        <flux:input wire:model.live.debounce.300ms="search" placeholder="Nach Name suchen..." icon="magnifying-glass" />

        @if($loading)
            <flux:text>Lädt Line Groups...</flux:text>
        @elseif(empty($this->lineGroups))
            <flux:callout variant="subtle">Keine Line Groups gefunden.</flux:callout>
        @elseif(empty($this->filteredLineGroups))
            <flux:callout variant="subtle">Keine Line Groups gefunden, die „{{ $search }}“ enthalten.</flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Verteilungsalgorithmus</flux:table.column>
                    <flux:table.column>RNA Timeout</flux:table.column>
                    <flux:table.column>Aktionen</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->filteredLineGroups as $lineGroup)
                        <flux:table.row wire:key="line-group-{{ $lineGroup['name'] }}">
                            <flux:table.cell>{{ $lineGroup['name'] }}</flux:table.cell>
                            <flux:table.cell>{{ $lineGroup['distribution_algorithm'] }}</flux:table.cell>
                            <flux:table.cell>{{ $lineGroup['rna_reversion_timeout'] }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button
                                        size="xs"
                                        :href="route('apps.cisco.line-groups.show', ['identifier' => $lineGroup['uuid'] ?: $lineGroup['name']])"
                                        icon="eye"
                                        wire:navigate
                                    >
                                        Mitglieder
                                    </flux:button>
                                    <flux:button size="xs" wire:click="openEditForm(@js($lineGroup['name']))">Bearbeiten</flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="danger"
                                        wire:click="delete(@js($lineGroup['name']))"
                                        wire:confirm="Line Group wirklich löschen?"
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
            <flux:heading size="lg">{{ $isEditing ? 'Line Group bearbeiten' : 'Line Group anlegen' }}</flux:heading>

            <flux:input wire:model="formName" label="Name" :disabled="$isEditing" required />
            <flux:input wire:model="formDistributionAlgorithm" label="Verteilungsalgorithmus" required />
            <flux:input wire:model="formRnaReversionTimeout" type="number" min="1" label="RNA Reversion Timeout (Sek.)" required />

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showForm', false)">Abbrechen</flux:button>
                <flux:button type="submit" variant="primary">Speichern</flux:button>
            </div>
        </form>
    </flux:modal>
</x-intranet-app-cisco::cisco-layout>
</div>
