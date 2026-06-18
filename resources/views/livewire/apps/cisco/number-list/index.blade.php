<?php

use Flux\Flux;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlExceptionMessage;
use Hwkdo\IntranetAppCisco\Exports\ExtensionDirectoryExport;
use Hwkdo\IntranetAppCisco\Services\ExtensionDirectoryBuilder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

new #[Title('Cisco – Nummernliste')] class extends Component
{
    public bool $loading = true;

    public bool $showOnlyFree = false;

    public string $search = '';

    /** @var array<int, array{extension: int, extension_display: string, is_free: bool, remark: string, remark_lines: list<string>, department: string|null}> */
    public array $entries = [];

    public function mount(): void
    {
        $this->loadEntries();
    }

    public function loadEntries(): void
    {
        $this->loading = true;

        try {
            $this->entries = app(ExtensionDirectoryBuilder::class)->build();
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden der Nummernliste: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
            $this->entries = [];
        }

        $this->loading = false;
    }

    #[Computed]
    public function filteredEntries(): array
    {
        $entries = $this->entries;

        if ($this->showOnlyFree) {
            $entries = array_values(array_filter($entries, fn (array $entry): bool => $entry['is_free']));
        }

        if ($this->search === '') {
            return $entries;
        }

        $search = strtolower($this->search);

        return array_values(array_filter($entries, function (array $entry) use ($search): bool {
            return str_contains(strtolower($entry['extension_display']), $search)
                || str_contains(strtolower($entry['remark']), $search)
                || str_contains(strtolower((string) ($entry['department'] ?? '')), $search);
        }));
    }

    #[Computed]
    public function occupiedCount(): int
    {
        return count(array_filter($this->entries, fn (array $entry): bool => ! $entry['is_free']));
    }

    #[Computed]
    public function freeCount(): int
    {
        return count($this->entries) - $this->occupiedCount;
    }

    public function exportExcelAll(): BinaryFileResponse
    {
        return Excel::download(
            new ExtensionDirectoryExport($this->entries),
            $this->exportFilename('alle'),
        );
    }

    public function exportExcelFiltered(): BinaryFileResponse
    {
        return Excel::download(
            new ExtensionDirectoryExport($this->filteredEntries),
            $this->exportFilename('gefiltert'),
        );
    }

    private function exportFilename(string $mode): string
    {
        return 'nummernliste-'.$mode.'-'.now()->format('Y-m-d-His').'.xlsx';
    }
};
?>
<div>
<x-intranet-app-cisco::cisco-layout heading="Cisco App" subheading="Nummernliste">
    <div class="min-w-0 space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <flux:heading>Nummernliste (100–999)</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Übersicht aller Durchwahlen aus Reservierungen, Lines, Pickup Groups und Hunt Pilots.
                </flux:text>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <flux:dropdown>
                    <flux:button variant="ghost" icon="arrow-down-tray" icon-trailing="chevron-down" wire:loading.attr="disabled" :disabled="$loading || empty($entries)">
                        Excel exportieren
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="exportExcelAll" icon="document-duplicate">Gesamte Liste exportieren</flux:menu.item>
                        <flux:menu.item wire:click="exportExcelFiltered" icon="funnel">Gefilterte Liste exportieren</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
                <flux:button wire:click="loadEntries" wire:loading.attr="disabled" icon="arrow-path" variant="ghost">
                    Aktualisieren
                </flux:button>
            </div>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                <span>Belegt: {{ $this->occupiedCount }}</span>
                <span>Frei: {{ $this->freeCount }}</span>
            </div>
            <flux:switch wire:model.live="showOnlyFree" label="Nur freie anzeigen" />
        </div>

        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Nach Durchwahl, Bemerkung oder Abteilung suchen..."
            icon="magnifying-glass"
        />

        @if($loading)
            <flux:text>Lädt Nummernliste...</flux:text>
        @elseif(empty($this->filteredEntries))
            <flux:callout variant="subtle">
                @if($showOnlyFree)
                    Keine freien Durchwahlen gefunden.
                @elseif($search !== '')
                    Keine Einträge gefunden, die „{{ $search }}“ enthalten.
                @else
                    Keine Einträge vorhanden.
                @endif
            </flux:callout>
        @else
            <div class="min-w-0 overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-24 shrink-0">Durchwahl</flux:table.column>
                        <flux:table.column class="min-w-0">Bemerkung</flux:table.column>
                        <flux:table.column class="w-56 shrink-0">Abteilung</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->filteredEntries as $entry)
                            <flux:table.row wire:key="extension-{{ $entry['extension'] }}" class="align-top">
                                <flux:table.cell class="whitespace-nowrap tabular-nums">{{ $entry['extension_display'] }}</flux:table.cell>
                                <flux:table.cell class="min-w-0 max-w-prose break-words">
                                    @if($entry['is_free'])
                                        <flux:badge variant="success" size="sm">FREI</flux:badge>
                                    @else
                                        <div class="space-y-1.5 text-sm leading-snug">
                                            @foreach($entry['remark_lines'] ?? [$entry['remark']] as $remarkLine)
                                                <div class="break-words">{{ $remarkLine }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="max-w-56 break-words text-sm leading-snug">{{ $entry['department'] ?? '—' }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </div>
</x-intranet-app-cisco::cisco-layout>
</div>
