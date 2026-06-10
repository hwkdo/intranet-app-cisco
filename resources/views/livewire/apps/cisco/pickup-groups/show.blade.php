<?php

use Flux\Flux;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;

use function Livewire\Volt\{mount, placeholder, state, title};

placeholder('<div>Loading...</div>');

state([
    'groupUuid' => null,
    'group' => null,
    'members' => [],
    'groupMemberNumbers' => [],
    'newMemberDirectoryNumber' => '',
    'loading' => true,
]);

title('Pickup Group Details');

mount(function (string $groupUuid) {
    $this->groupUuid = $groupUuid;
    $this->loadGroup();
});

$loadGroup = function () {
    $this->loading = true;
    try {
        $axlService = app(AxlServiceInterface::class);
        $this->group = $axlService->getCallPickupGroup($this->groupUuid);

        if (isset($this->group->name)) {
            try {
                $this->groupMemberNumbers = $axlService->getPickupGroupMembers($this->group->name);
            } catch (\Exception $e) {
                $this->groupMemberNumbers = [];
            }
        }

        $this->loading = false;
    } catch (\Exception $e) {
        Flux::toast(
            text: 'Fehler beim Laden der Pickup Group: '.$e->getMessage(),
            variant: 'danger'
        );
        $this->loading = false;
    }
};

$addMember = function () {
    if (empty($this->newMemberDirectoryNumber)) {
        Flux::toast(
            text: 'Bitte geben Sie eine 3-stellige Nummer ein.',
            variant: 'warning'
        );

        return;
    }

    if (strlen($this->newMemberDirectoryNumber) !== 3 || ! ctype_digit($this->newMemberDirectoryNumber)) {
        Flux::toast(
            text: 'Bitte geben Sie genau 3 Ziffern ein.',
            variant: 'warning'
        );

        return;
    }

    try {
        $axlService = app(AxlServiceInterface::class);
        $pattern = config('cisco-phone-services-laravel.axl.pattern');
        $fullNumber = $pattern.$this->newMemberDirectoryNumber;
        $groupName = $this->group->name ?? $this->groupUuid;

        $axlService->setLinePickupGroupName($fullNumber, $groupName);

        $this->newMemberDirectoryNumber = '';
        $this->loadGroup();

        Flux::toast(
            text: 'Mitglied erfolgreich hinzugefügt',
            variant: 'success'
        );
    } catch (\Exception $e) {
        Flux::toast(
            text: 'Fehler beim Hinzufügen des Mitglieds: '.$e->getMessage(),
            variant: 'danger'
        );
    }
};

$removeMemberFromGroup = function (string $member) {
    try {
        if (str_starts_with($member, '+') && ! str_starts_with($member, '\\+')) {
            $member = '\\'.$member;
        }

        $axlService = app(AxlServiceInterface::class);
        $axlService->setLinePickupGroupName($member, '');

        $this->loadGroup();

        Flux::toast(
            text: 'Mitglied erfolgreich entfernt',
            variant: 'success'
        );
    } catch (\Exception $e) {
        Flux::toast(
            text: 'Fehler beim Entfernen des Mitglieds: '.$e->getMessage(),
            variant: 'danger'
        );
    }
};

$updatedNewMemberDirectoryNumber = function () {
    $this->newMemberDirectoryNumber = preg_replace('/[^0-9]/', '', $this->newMemberDirectoryNumber);
    if (strlen($this->newMemberDirectoryNumber) > 3) {
        $this->newMemberDirectoryNumber = substr($this->newMemberDirectoryNumber, 0, 3);
    }
};

?>
<div>
<x-intranet-app-cisco::cisco-layout heading="Cisco App" subheading="Pickup Group Details">
    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Pickup Group: {{ $this->group->name ?? $this->groupUuid }}</flux:heading>
                @if($this->group && isset($this->group->description))
                    <flux:text class="mt-1 text-gray-600 dark:text-gray-400">{{ $this->group->description }}</flux:text>
                @endif
                @if($this->group && isset($this->group->uuid))
                    <flux:text class="mt-1 text-gray-600 dark:text-gray-400">{{ $this->group->uuid }}</flux:text>
                @endif
            </div>
            <flux:button
                :href="route('apps.cisco.pickup-groups.index')"
                variant="ghost"
                icon="arrow-left"
                wire:navigate
            >
                Zurück
            </flux:button>
        </div>

        @if($this->loading)
            <div class="flex items-center justify-center py-12">
                <flux:text>Lädt Pickup Group Details...</flux:text>
            </div>
        @else
            <flux:card class="glass-card">
                <flux:heading size="lg" class="mb-4">Mitglieder</flux:heading>

                @if(empty($this->members) && empty($this->groupMemberNumbers))
                    <flux:callout variant="subtle" class="mb-4">
                        Diese Pickup Group hat noch keine Mitglieder.
                    </flux:callout>
                @else
                    <div class="mb-6 space-y-2">
                        @if(!empty($this->groupMemberNumbers))
                            @foreach ($this->groupMemberNumbers as $number)
                                <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                    <div>
                                        <flux:text size="lg">{{ $number }}</flux:text>
                                    </div>
                                    <flux:button
                                        wire:click="removeMemberFromGroup('{{ $number }}')"
                                        wire:confirm="Möchten Sie dieses Mitglied wirklich entfernen?"
                                        size="xs"
                                        variant="danger"
                                        icon="trash"
                                        wire:loading.attr="disabled"
                                    >
                                        <span wire:loading.remove wire:target="removeMemberFromGroup('{{ $number }}')">Entfernen</span>
                                        <span wire:loading wire:target="removeMemberFromGroup('{{ $number }}')">Entfernt...</span>
                                    </flux:button>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif

                <flux:separator class="my-4" />

                <div class="space-y-4">
                    <flux:heading size="md">Mitglied hinzufügen</flux:heading>
                    <div class="flex items-end gap-4">
                        <div class="flex-1">
                            <flux:input
                                label="Durchwahl (3 Stellen)"
                                placeholder="z.B. 518"
                                wire:model.live="newMemberDirectoryNumber"
                                wire:keydown.enter="addMember"
                                maxlength="3"
                                pattern="[0-9]{3}"
                                inputmode="numeric"
                            />
                        </div>
                        <flux:button
                            wire:click="addMember"
                            wire:loading.attr="disabled"
                            icon="plus"
                        >
                            <span wire:loading.remove wire:target="addMember">Hinzufügen</span>
                            <span wire:loading wire:target="addMember">Hinzufügen...</span>
                        </flux:button>
                    </div>
                </div>
            </flux:card>
        @endif
    </div>
</x-intranet-app-cisco::cisco-layout>
</div>