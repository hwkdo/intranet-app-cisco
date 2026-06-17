<?php

use Flux\Flux;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlExceptionMessage;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlValueFormatter;

use function Livewire\Volt\{mount, placeholder, state, title};

placeholder('<div>Loading...</div>');

state([
    'identifier' => null,
    'lineGroup' => null,
    'members' => [],
    'newMemberPattern' => '',
    'newMemberShortNumber' => '',
    'newLineSelectionOrder' => 1,
    'loading' => true,
]);

title('Line Group Details');

mount(function (string $identifier) {
    $this->identifier = $identifier;
    $this->loadLineGroup();
});

$loadLineGroup = function () {
    $this->loading = true;

    try {
        $axlService = app(AxlServiceInterface::class);
        $this->lineGroup = $axlService->getLineGroup($this->identifier);
        $this->members = $axlService->getLineGroupMembers($this->identifier);
        $this->loading = false;
    } catch (\Throwable $throwable) {
        Flux::toast(
            text: 'Fehler beim Laden der Line Group: '.AxlExceptionMessage::from($throwable),
            variant: 'danger'
        );
        $this->loading = false;
    }
};

$addMember = function () {
    $pattern = $this->newMemberPattern;

    if ($pattern === '' && $this->newMemberShortNumber !== '') {
        $pattern = config('cisco-phone-services-laravel.axl.pattern').$this->newMemberShortNumber;
    }

    if ($pattern === '') {
        Flux::toast(text: 'Bitte geben Sie eine Nummer ein.', variant: 'warning');

        return;
    }

    $this->validate([
        'newLineSelectionOrder' => ['required', 'integer', 'min:1'],
    ]);

    try {
        $identifier = AxlValueFormatter::stringify($this->lineGroup->name ?? $this->identifier);

        app(AxlServiceInterface::class)->addLineGroupMember(
            $identifier,
            $pattern,
            null,
            (int) $this->newLineSelectionOrder
        );

        $this->newMemberPattern = '';
        $this->newMemberShortNumber = '';
        $this->newLineSelectionOrder = 1;
        $this->loadLineGroup();

        Flux::toast(text: 'Line hinzugefügt', variant: 'success');
    } catch (\Throwable $throwable) {
        Flux::toast(
            text: 'Fehler beim Hinzufügen: '.AxlExceptionMessage::from($throwable),
            variant: 'danger'
        );
    }
};

$removeMember = function (string $pattern, string $routePartition = '') {
    try {
        if (str_starts_with($pattern, '+') && ! str_starts_with($pattern, '\\+')) {
            $pattern = '\\'.$pattern;
        }

        $identifier = AxlValueFormatter::stringify($this->lineGroup->name ?? $this->identifier);

        app(AxlServiceInterface::class)->removeLineGroupMember(
            $identifier,
            $pattern,
            $routePartition !== '' ? $routePartition : null
        );

        $this->loadLineGroup();

        Flux::toast(text: 'Line entfernt', variant: 'success');
    } catch (\Throwable $throwable) {
        Flux::toast(
            text: 'Fehler beim Entfernen: '.AxlExceptionMessage::from($throwable),
            variant: 'danger'
        );
    }
};

$updatedNewMemberShortNumber = function () {
    $this->newMemberShortNumber = preg_replace('/[^0-9]/', '', $this->newMemberShortNumber);
    if (strlen($this->newMemberShortNumber) > 3) {
        $this->newMemberShortNumber = substr($this->newMemberShortNumber, 0, 3);
    }
};

?>
<div>
<x-intranet-app-cisco::cisco-layout heading="Cisco App" subheading="Line Group Details">
    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Line Group: {{ $this->lineGroup->name ?? $this->identifier }}</flux:heading>
                @if($this->lineGroup && isset($this->lineGroup->distributionAlgorithm))
                    <flux:text class="mt-1 text-gray-600 dark:text-gray-400">
                        {{ $this->lineGroup->distributionAlgorithm }}
                    </flux:text>
                @endif
            </div>
            <flux:button
                :href="route('apps.cisco.line-groups.index')"
                variant="ghost"
                icon="arrow-left"
                wire:navigate
            >
                Zurück
            </flux:button>
        </div>

        @if($this->loading)
            <flux:text>Lädt Line Group Details...</flux:text>
        @else
            <flux:card class="glass-card">
                <flux:heading size="lg" class="mb-4">Lines (Mitglieder)</flux:heading>

                @if(empty($this->members))
                    <flux:callout variant="subtle" class="mb-4">
                        Diese Line Group hat noch keine Mitglieder.
                    </flux:callout>
                @else
                    <div class="mb-6 space-y-2">
                        @foreach ($this->members as $member)
                            <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700" wire:key="member-{{ $member['pattern'] }}">
                                <div>
                                    <flux:text size="lg">{{ $member['pattern'] }}</flux:text>
                                    <flux:text class="text-gray-500">
                                        Partition: {{ $member['route_partition'] ?: '—' }} · Reihenfolge: {{ $member['line_selection_order'] }}
                                    </flux:text>
                                </div>
                                <flux:button
                                    wire:click="removeMember(@js($member['pattern']), @js($member['route_partition']))"
                                    wire:confirm="Line wirklich aus der Line Group entfernen?"
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
                    <flux:heading size="md">Line hinzufügen</flux:heading>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <flux:input wire:model="newMemberPattern" label="Vollständiges Pattern (optional)" placeholder="z.B. \+492315493518" />
                        </div>
                        <div class="w-full sm:w-40">
                            <flux:input
                                wire:model.live="newMemberShortNumber"
                                label="oder Durchwahl (3 Stellen)"
                                placeholder="z.B. 518"
                                maxlength="3"
                                inputmode="numeric"
                            />
                        </div>
                        <div class="w-full sm:w-32">
                            <flux:input wire:model="newLineSelectionOrder" type="number" min="1" label="Reihenfolge" required />
                        </div>
                        <flux:button type="submit" icon="plus">Hinzufügen</flux:button>
                    </div>
                </form>
            </flux:card>
        @endif
    </div>
</x-intranet-app-cisco::cisco-layout>
</div>
