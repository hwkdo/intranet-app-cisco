<?php

use Flux\Flux;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlExceptionMessage;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlValueFormatter;

use function Livewire\Volt\{mount, placeholder, state, title};

placeholder('<div>Loading...</div>');

state([
    'identifier' => null,
    'huntList' => null,
    'members' => [],
    'lineGroups' => [],
    'newLineGroupName' => '',
    'newSelectionOrder' => 1,
    'loading' => true,
]);

title('Hunt List Details');

mount(function (string $identifier) {
    $this->identifier = $identifier;
    $this->loadHuntList();
});

$loadHuntList = function () {
    $this->loading = true;

    try {
        $axlService = app(AxlServiceInterface::class);
        $this->huntList = $axlService->getHuntList($this->identifier);
        $this->members = $axlService->getHuntListMembers($this->identifier);
        $this->lineGroups = $axlService->listLineGroups();
        $this->loading = false;
    } catch (\Throwable $throwable) {
        Flux::toast(
            text: 'Fehler beim Laden der Hunt List: '.AxlExceptionMessage::from($throwable),
            variant: 'danger'
        );
        $this->loading = false;
    }
};

$addMember = function () {
    $this->validate([
        'newLineGroupName' => ['required', 'string', 'max:100'],
        'newSelectionOrder' => ['required', 'integer', 'min:1'],
    ]);

    try {
        $identifier = AxlValueFormatter::stringify($this->huntList->name ?? $this->identifier);

        app(AxlServiceInterface::class)->addHuntListMember(
            $identifier,
            $this->newLineGroupName,
            (int) $this->newSelectionOrder
        );

        $this->newLineGroupName = '';
        $this->newSelectionOrder = 1;
        $this->loadHuntList();

        Flux::toast(text: 'Line Group hinzugefügt', variant: 'success');
    } catch (\Throwable $throwable) {
        Flux::toast(
            text: 'Fehler beim Hinzufügen: '.AxlExceptionMessage::from($throwable),
            variant: 'danger'
        );
    }
};

$removeMember = function (string $lineGroupName) {
    try {
        $identifier = AxlValueFormatter::stringify($this->huntList->name ?? $this->identifier);

        app(AxlServiceInterface::class)->removeHuntListMember($identifier, $lineGroupName);
        $this->loadHuntList();

        Flux::toast(text: 'Line Group entfernt', variant: 'success');
    } catch (\Throwable $throwable) {
        Flux::toast(
            text: 'Fehler beim Entfernen: '.AxlExceptionMessage::from($throwable),
            variant: 'danger'
        );
    }
};

?>
<div>
<x-intranet-app-cisco::cisco-layout heading="Cisco App" subheading="Hunt List Details">
    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Hunt List: {{ $this->huntList->name ?? $this->identifier }}</flux:heading>
                @if($this->huntList && isset($this->huntList->description))
                    <flux:text class="mt-1 text-gray-600 dark:text-gray-400">{{ $this->huntList->description }}</flux:text>
                @endif
            </div>
            <flux:button
                :href="route('apps.cisco.hunt-lists.index')"
                variant="ghost"
                icon="arrow-left"
                wire:navigate
            >
                Zurück
            </flux:button>
        </div>

        @if($this->loading)
            <flux:text>Lädt Hunt List Details...</flux:text>
        @else
            <flux:card class="glass-card">
                <flux:heading size="lg" class="mb-4">Line Groups (Mitglieder)</flux:heading>

                @if(empty($this->members))
                    <flux:callout variant="subtle" class="mb-4">
                        Diese Hunt List hat noch keine Line Groups.
                    </flux:callout>
                @else
                    <div class="mb-6 space-y-2">
                        @foreach ($this->members as $member)
                            <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700" wire:key="member-{{ $member['line_group_name'] }}">
                                <div>
                                    <flux:text size="lg">{{ $member['line_group_name'] }}</flux:text>
                                    <flux:text class="text-gray-500">Reihenfolge: {{ $member['selection_order'] }}</flux:text>
                                </div>
                                <flux:button
                                    wire:click="removeMember(@js($member['line_group_name']))"
                                    wire:confirm="Line Group wirklich aus der Hunt List entfernen?"
                                    size="xs"
                                    variant="danger"
                                    icon="trash"
                                >
                                    Entfernen
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <flux:separator class="my-4" />

                <form wire:submit="addMember" class="space-y-4">
                    <flux:heading size="md">Line Group hinzufügen</flux:heading>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            @if(count($this->lineGroups) > 0)
                                <flux:select wire:model="newLineGroupName" label="Line Group" required>
                                    <flux:select.option value="">Bitte wählen...</flux:select.option>
                                    @foreach($this->lineGroups as $lineGroup)
                                        <flux:select.option value="{{ $lineGroup['name'] }}">{{ $lineGroup['name'] }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @else
                                <flux:input wire:model="newLineGroupName" label="Line Group" required />
                            @endif
                        </div>
                        <div class="w-full sm:w-32">
                            <flux:input wire:model="newSelectionOrder" type="number" min="1" label="Reihenfolge" required />
                        </div>
                        <flux:button type="submit" icon="plus">Hinzufügen</flux:button>
                    </div>
                </form>
            </flux:card>
        @endif
    </div>
</x-intranet-app-cisco::cisco-layout>
</div>
