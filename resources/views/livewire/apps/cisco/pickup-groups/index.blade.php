<?php

use Flux\Flux;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;

use function Livewire\Volt\{computed, mount, placeholder, state, title};

placeholder('<div>Loading...</div>');

state([
    'groups' => [],
    'loading' => true,
    'search' => '',
]);

title('Pickup Groups');

mount(function () {
    try {
        $axlService = app(AxlServiceInterface::class);
        $this->groups = $axlService->listCallPickupGroups();
        $this->loading = false;
    } catch (\Exception $e) {
        Flux::toast(
            text: 'Fehler beim Laden der Pickup Groups: '.$e->getMessage(),
            variant: 'danger'
        );
        $this->loading = false;
    }
});

$refresh = function () {
    $this->loading = true;
    try {
        $axlService = app(AxlServiceInterface::class);
        $this->groups = $axlService->listCallPickupGroups();
        $this->loading = false;
        Flux::toast(
            text: 'Pickup Groups aktualisiert',
            variant: 'success'
        );
    } catch (\Exception $e) {
        Flux::toast(
            text: 'Fehler beim Laden der Pickup Groups: '.$e->getMessage(),
            variant: 'danger'
        );
        $this->loading = false;
    }
};

$filteredGroups = computed(function () {
    if (empty($this->search)) {
        return $this->groups;
    }

    $search = strtolower($this->search);

    return array_filter($this->groups, function ($group) use ($search) {
        return str_contains(strtolower($group['name'] ?? ''), $search) ||
               str_contains(strtolower($group['pattern'] ?? ''), $search);
    });
});

?>
<div>
<x-intranet-app-cisco::cisco-layout heading="Cisco App" subheading="Pickup Groups">
    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <flux:heading>Pickup Groups</flux:heading>
            <flux:button wire:click="refresh" wire:loading.attr="disabled" icon="arrow-path">
                <span wire:loading.remove>Aktualisieren</span>
                <span wire:loading>Lädt...</span>
            </flux:button>
        </div>

        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Nach Gruppennamen suchen..."
            icon="magnifying-glass"
        />

        @if($this->loading)
            <div class="flex items-center justify-center py-12">
                <flux:text>Lädt Pickup Groups...</flux:text>
            </div>
        @elseif(empty($this->filteredGroups))
            <flux:callout variant="subtle">
                @if(!empty($this->search))
                    Keine Pickup Groups gefunden, die "{{ $this->search }}" enthalten.
                @else
                    Keine Pickup Groups gefunden.
                @endif
            </flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Pattern</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->filteredGroups as $group)
                        <flux:table.row>
                            <flux:table.cell>{{ $group['name'] }}</flux:table.cell>
                            <flux:table.cell>{{ $group['pattern'] }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button
                                    :href="route('apps.cisco.pickup-groups.show', ['groupUuid' => $group['uuid']])"
                                    size="xs"
                                    icon="eye"
                                    wire:navigate
                                >
                                    Details
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</x-intranet-app-cisco::cisco-layout>
</div>