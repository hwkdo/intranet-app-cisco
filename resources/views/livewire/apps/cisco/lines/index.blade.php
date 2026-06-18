<?php

use Flux\Flux;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlExceptionMessage;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlValueFormatter;
use Hwkdo\CiscoPhoneServicesLaravel\Support\LineCallingPermissionFormatter;
use Hwkdo\IntranetAppCisco\Services\LineEmployeeResolver;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cisco – Lines')] class extends Component
{
    public string $search = '';

    public bool $loading = true;

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    /** @var array<int, array{name: string, description: string, label: string}> */
    public array $callingSearchSpaces = [];

    public bool $showForm = false;

    public bool $isEditing = false;

    public string $formPattern = '';

    public string $formDescription = '';

    public string $formUsage = '';

    public string $formAlertingName = '';

    public string $formCallingSearchSpace = '';

    public function mount(): void
    {
        $this->formUsage = (string) config('cisco-phone-services-laravel.axl.defaults.line.usage', 'Device');
        $this->loadLines();
        $this->loadCallingSearchSpaces();
    }

    public function loadLines(): void
    {
        $this->loading = true;

        try {
            $this->lines = app(LineEmployeeResolver::class)->enrichLines(
                app(AxlServiceInterface::class)->listLines()
            );
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden der Lines: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
            $this->lines = [];
        }

        $this->loading = false;
    }

    public function loadCallingSearchSpaces(): void
    {
        try {
            $this->callingSearchSpaces = app(AxlServiceInterface::class)->listCallingSearchSpaces();
        } catch (\Throwable) {
            $this->callingSearchSpaces = [];
        }
    }

    #[Computed]
    public function filteredLines(): array
    {
        if ($this->search === '') {
            return $this->lines;
        }

        $search = strtolower($this->search);

        return array_values(array_filter($this->lines, function (array $line) use ($search): bool {
            return str_contains(strtolower($line['pattern'] ?? ''), $search)
                || str_contains(strtolower($line['description'] ?? ''), $search)
                || str_contains(strtolower($line['alerting_name'] ?? ''), $search)
                || str_contains(strtolower($line['calling_permission'] ?? ''), $search)
                || str_contains(strtolower($line['calling_search_space'] ?? ''), $search)
                || str_contains(strtolower($line['department'] ?? ''), $search);
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
            $line = app(AxlServiceInterface::class)->getLine($pattern);

            $this->formPattern = AxlValueFormatter::stringify($line->pattern ?? $pattern);
            $this->formDescription = AxlValueFormatter::stringify($line->description ?? '');
            $this->formUsage = AxlValueFormatter::stringify($line->usage ?? $this->formUsage);
            $this->formAlertingName = AxlValueFormatter::stringify($line->alertingName ?? '');
            $this->formCallingSearchSpace = AxlValueFormatter::stringify($line->shareLineAppearanceCssName ?? '');
            $this->isEditing = true;
            $this->showForm = true;
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden der Line: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function save(): void
    {
        $this->validate([
            'formPattern' => ['required', 'string', 'max:128'],
            'formDescription' => ['nullable', 'string', 'max:128'],
            'formUsage' => ['required', 'string', 'max:64'],
            'formAlertingName' => ['nullable', 'string', 'max:50'],
            'formCallingSearchSpace' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $axlService = app(AxlServiceInterface::class);

            if ($this->isEditing) {
                $axlService->updateLineByPattern($this->formPattern, array_filter([
                    'description' => $this->formDescription,
                    'alertingName' => $this->formAlertingName,
                    'shareLineAppearanceCssName' => $this->formCallingSearchSpace,
                ], fn ($value) => $value !== ''));
                Flux::toast(text: 'Line aktualisiert', variant: 'success');
            } else {
                $axlService->addLine(array_filter([
                    'pattern' => $this->formPattern,
                    'description' => $this->formDescription,
                    'alertingName' => $this->formAlertingName,
                    'usage' => $this->formUsage,
                    'shareLineAppearanceCssName' => $this->formCallingSearchSpace,
                ], fn ($value) => $value !== ''));
                Flux::toast(text: 'Line angelegt', variant: 'success');
            }

            $this->showForm = false;
            $this->loadLines();
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
            app(AxlServiceInterface::class)->removeLine($pattern);
            Flux::toast(text: 'Line gelöscht', variant: 'success');
            $this->loadLines();
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
        $this->formUsage = (string) config('cisco-phone-services-laravel.axl.defaults.line.usage', 'Device');
        $this->formAlertingName = '';
        $this->formCallingSearchSpace = '';
    }
};
?>
<div>
<x-intranet-app-cisco::cisco-layout heading="Cisco App" subheading="Lines">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <flux:heading>Lines (Telefonnummern)</flux:heading>
            <div class="flex gap-2">
                <flux:button wire:click="loadLines" wire:loading.attr="disabled" icon="arrow-path" variant="ghost">Aktualisieren</flux:button>
                <flux:button wire:click="openCreateForm" icon="plus" variant="primary">Neue Line</flux:button>
            </div>
        </div>

        <flux:input wire:model.live.debounce.300ms="search" placeholder="Nach Nummer, Beschreibung, Alerting Name oder Telefonie-Berechtigung suchen..." icon="magnifying-glass" />

        @if($loading)
            <flux:text>Lädt Lines...</flux:text>
        @elseif(empty($this->lines))
            <flux:callout variant="subtle">Keine Lines gefunden.</flux:callout>
        @elseif(empty($this->filteredLines))
            <flux:callout variant="subtle">Keine Lines gefunden, die „{{ $search }}“ enthalten.</flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Pattern</flux:table.column>
                    <flux:table.column>Beschreibung</flux:table.column>
                    <flux:table.column>Abteilung</flux:table.column>
                    <flux:table.column>Alerting Name</flux:table.column>
                    <flux:table.column>Usage</flux:table.column>
                    <flux:table.column>Telefonie</flux:table.column>
                    <flux:table.column>Partition</flux:table.column>
                    <flux:table.column>Aktionen</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->filteredLines as $line)
                        <flux:table.row wire:key="line-{{ $line['pattern'] }}">
                            <flux:table.cell>{{ $line['pattern'] }}</flux:table.cell>
                            <flux:table.cell>{{ $line['description'] }}</flux:table.cell>
                            <flux:table.cell>{{ $line['department'] ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $line['alerting_name'] }}</flux:table.cell>
                            <flux:table.cell>{{ $line['usage'] }}</flux:table.cell>
                            <flux:table.cell>
                                <div>{{ $line['calling_permission'] }}</div>
                                @if(($line['calling_search_space'] ?? '') !== '' && ($line['calling_search_space'] ?? '') !== ($line['calling_permission'] ?? ''))
                                    <flux:text class="text-zinc-500 dark:text-zinc-400">{{ $line['calling_search_space'] }}</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $line['route_partition'] }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button size="xs" wire:click="openEditForm(@js($line['pattern']))">Bearbeiten</flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="danger"
                                        wire:click="delete(@js($line['pattern']))"
                                        wire:confirm="Line wirklich löschen?"
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
            <flux:heading size="lg">{{ $isEditing ? 'Line bearbeiten' : 'Line anlegen' }}</flux:heading>

            <flux:input wire:model="formPattern" label="Pattern (Telefonnummer)" placeholder="z.B. \+492315493518" :disabled="$isEditing" required />
            <flux:input wire:model="formDescription" label="Beschreibung" />
            <flux:input wire:model="formAlertingName" label="Alerting Name" maxlength="50" />
            @if(count($callingSearchSpaces) > 0)
                <flux:select wire:model="formCallingSearchSpace" label="Telefonie-Berechtigung (Calling Search Space)">
                    <flux:select.option value="">{{ $isEditing ? 'Unverändert lassen' : 'Keine' }}</flux:select.option>
                    @if($isEditing && $formCallingSearchSpace !== '' && ! collect($callingSearchSpaces)->contains(fn (array $css): bool => $css['name'] === $formCallingSearchSpace))
                        <flux:select.option value="{{ $formCallingSearchSpace }}">
                            {{ LineCallingPermissionFormatter::label($formCallingSearchSpace) }} ({{ $formCallingSearchSpace }})
                        </flux:select.option>
                    @endif
                    @foreach($callingSearchSpaces as $css)
                        <flux:select.option value="{{ $css['name'] }}">
                            {{ $css['label'] }}{{ $css['label'] !== $css['name'] ? ' ('.$css['name'].')' : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <flux:input wire:model="formCallingSearchSpace" label="Telefonie-Berechtigung (Calling Search Space)" placeholder="z.B. CSS_National" />
            @endif
            <flux:input wire:model="formUsage" label="Usage" :disabled="$isEditing" required />

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showForm', false)">Abbrechen</flux:button>
                <flux:button type="submit" variant="primary">Speichern</flux:button>
            </div>
        </form>
    </flux:modal>
</x-intranet-app-cisco::cisco-layout>
</div>