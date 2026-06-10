<?php

use Flux\Flux;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlExceptionMessage;
use Hwkdo\CiscoPhoneServicesLaravel\Support\AxlValueFormatter;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cisco – User')] class extends Component
{
    public string $search = '';

    public bool $loading = true;

    /** @var array<int, array<string, mixed>> */
    public array $users = [];

    public bool $showForm = false;

    public bool $isEditing = false;

    public string $formUserid = '';

    public string $formFirstName = '';

    public string $formLastName = '';

    public string $formMailid = '';

    public string $formDepartment = '';

    public function mount(): void
    {
        $this->loadUsers();
    }

    public function loadUsers(): void
    {
        $this->loading = true;

        try {
            $this->users = app(AxlServiceInterface::class)->listUsers($this->search);
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden der User: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
            $this->users = [];
        }

        $this->loading = false;
    }

    public function updatedSearch(): void
    {
        $this->loadUsers();
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showForm = true;
    }

    public function openEditForm(string $userid): void
    {
        try {
            $user = app(AxlServiceInterface::class)->getUser($userid);

            $this->formUserid = AxlValueFormatter::stringify($user->userid ?? $userid);
            $this->formFirstName = AxlValueFormatter::stringify($user->firstName ?? '');
            $this->formLastName = AxlValueFormatter::stringify($user->lastName ?? '');
            $this->formMailid = AxlValueFormatter::stringify($user->mailid ?? '');
            $this->formDepartment = AxlValueFormatter::stringify($user->department ?? '');
            $this->isEditing = true;
            $this->showForm = true;
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Laden des Users: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function save(): void
    {
        $this->validate([
            'formUserid' => ['required', 'string', 'max:128'],
            'formFirstName' => ['nullable', 'string', 'max:128'],
            'formLastName' => ['required', 'string', 'max:128'],
            'formMailid' => ['nullable', 'string', 'max:255'],
            'formDepartment' => ['nullable', 'string', 'max:128'],
        ]);

        try {
            $axlService = app(AxlServiceInterface::class);

            $payload = array_filter([
                'userid' => $this->formUserid,
                'firstName' => $this->formFirstName,
                'lastName' => $this->formLastName,
                'mailid' => $this->formMailid,
                'department' => $this->formDepartment,
            ], fn ($value) => $value !== '');

            if ($this->isEditing) {
                unset($payload['userid']);
                $axlService->updateUser($this->formUserid, $payload);
                Flux::toast(text: 'User aktualisiert', variant: 'success');
            } else {
                $axlService->addUser($payload);
                Flux::toast(text: 'User angelegt', variant: 'success');
            }

            $this->showForm = false;
            $this->loadUsers();
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Speichern: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function delete(string $userid): void
    {
        try {
            app(AxlServiceInterface::class)->removeUser($userid);
            Flux::toast(text: 'User gelöscht', variant: 'success');
            $this->loadUsers();
        } catch (\Throwable $throwable) {
            Flux::toast(
                text: 'Fehler beim Löschen: '.AxlExceptionMessage::from($throwable),
                variant: 'danger'
            );
        }
    }

    public function resetForm(): void
    {
        $this->formUserid = '';
        $this->formFirstName = '';
        $this->formLastName = '';
        $this->formMailid = '';
        $this->formDepartment = '';
    }
};
?>
<div>
<x-intranet-app-cisco::cisco-layout heading="Cisco App" subheading="CUCM User">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <flux:heading>CUCM End User</flux:heading>
            <div class="flex gap-2">
                <flux:button wire:click="loadUsers" wire:loading.attr="disabled" icon="arrow-path" variant="ghost">Aktualisieren</flux:button>
                <flux:button wire:click="openCreateForm" icon="plus" variant="primary">Neuer User</flux:button>
            </div>
        </div>

        <flux:input wire:model.live.debounce.300ms="search" placeholder="Nach User ID suchen..." icon="magnifying-glass" />

        @if($loading)
            <flux:text>Lädt User...</flux:text>
        @elseif(empty($users))
            <flux:callout variant="subtle">Keine User gefunden.</flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>User ID</flux:table.column>
                    <flux:table.column>Vorname</flux:table.column>
                    <flux:table.column>Nachname</flux:table.column>
                    <flux:table.column>E-Mail</flux:table.column>
                    <flux:table.column>Abteilung</flux:table.column>
                    <flux:table.column>Aktionen</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($users as $user)
                        <flux:table.row wire:key="user-{{ $user['userid'] }}">
                            <flux:table.cell>{{ $user['userid'] }}</flux:table.cell>
                            <flux:table.cell>{{ $user['first_name'] }}</flux:table.cell>
                            <flux:table.cell>{{ $user['last_name'] }}</flux:table.cell>
                            <flux:table.cell>{{ $user['mailid'] }}</flux:table.cell>
                            <flux:table.cell>{{ $user['department'] }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button size="xs" wire:click="openEditForm(@js($user['userid']))">Bearbeiten</flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="danger"
                                        wire:click="delete(@js($user['userid']))"
                                        wire:confirm="User wirklich löschen?"
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
            <flux:heading size="lg">{{ $isEditing ? 'User bearbeiten' : 'User anlegen' }}</flux:heading>

            <flux:input wire:model="formUserid" label="User ID" :disabled="$isEditing" required />
            <flux:input wire:model="formFirstName" label="Vorname" />
            <flux:input wire:model="formLastName" label="Nachname" required />
            <flux:input wire:model="formMailid" label="E-Mail" type="email" />
            <flux:input wire:model="formDepartment" label="Abteilung" />

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showForm', false)">Abbrechen</flux:button>
                <flux:button type="submit" variant="primary">Speichern</flux:button>
            </div>
        </form>
    </flux:modal>
</x-intranet-app-cisco::cisco-layout>
</div>