<?php

use Flux\Flux;
use Hwkdo\IntranetAppCisco\Models\CiscoExtensionReservation;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    /** @var array<int, CiscoExtensionReservation> */
    public array $reservations = [];

    public bool $showForm = false;

    public bool $isEditing = false;

    public ?int $editingId = null;

    public string $formExtensionFrom = '';

    public string $formExtensionTo = '';

    public string $formDescription = '';

    public function mount(): void
    {
        $this->loadReservations();
    }

    public function loadReservations(): void
    {
        $this->reservations = CiscoExtensionReservation::query()
            ->ordered()
            ->get()
            ->all();
    }

    #[Computed]
    public function filteredReservations(): array
    {
        if ($this->search === '') {
            return $this->reservations;
        }

        $search = strtolower($this->search);

        return array_values(array_filter($this->reservations, function (CiscoExtensionReservation $reservation) use ($search): bool {
            return str_contains(strtolower($reservation->extension_display), $search)
                || str_contains(strtolower($reservation->description), $search)
                || str_contains(strtolower($reservation->extension_from), $search)
                || str_contains(strtolower((string) $reservation->extension_to), $search);
        }));
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showForm = true;
    }

    public function openEditForm(int $id): void
    {
        $reservation = CiscoExtensionReservation::query()->findOrFail($id);

        $this->editingId = $reservation->id;
        $this->formExtensionFrom = $reservation->extension_from;
        $this->formExtensionTo = $reservation->extension_to ?? '';
        $this->formDescription = $reservation->description;
        $this->isEditing = true;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->formExtensionFrom = preg_replace('/\D/', '', $this->formExtensionFrom) ?? '';
        $this->formExtensionTo = preg_replace('/\D/', '', $this->formExtensionTo) ?? '';

        $this->validate([
            'formExtensionFrom' => ['required', 'string', 'regex:/^\d{1,5}$/'],
            'formExtensionTo' => ['nullable', 'string', 'regex:/^\d{1,5}$/'],
            'formDescription' => ['required', 'string', 'max:255'],
        ]);

        if ($this->formExtensionTo !== '' && (int) $this->formExtensionTo < (int) $this->formExtensionFrom) {
            $this->addError('formExtensionTo', 'Die End-Durchwahl muss größer oder gleich der Start-Durchwahl sein.');

            return;
        }

        CiscoExtensionReservation::assertNoOverlap(
            $this->formExtensionFrom,
            $this->formExtensionTo !== '' ? $this->formExtensionTo : null,
            $this->isEditing ? $this->editingId : null,
        );

        $payload = [
            'extension_from' => $this->formExtensionFrom,
            'extension_to' => $this->formExtensionTo !== '' ? $this->formExtensionTo : null,
            'description' => $this->formDescription,
        ];

        if ($this->isEditing && $this->editingId !== null) {
            CiscoExtensionReservation::query()
                ->findOrFail($this->editingId)
                ->update($payload);
            Flux::toast(text: 'Reservierung aktualisiert', variant: 'success');
        } else {
            CiscoExtensionReservation::query()->create($payload);
            Flux::toast(text: 'Reservierung angelegt', variant: 'success');
        }

        $this->showForm = false;
        $this->loadReservations();
    }

    public function delete(int $id): void
    {
        CiscoExtensionReservation::query()->findOrFail($id)->delete();
        Flux::toast(text: 'Reservierung gelöscht', variant: 'success');
        $this->loadReservations();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->formExtensionFrom = '';
        $this->formExtensionTo = '';
        $this->formDescription = '';
    }
};
?>
<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading>Reservierte Durchwahlen</flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                Einzelne Durchwahlen oder Bereiche mit Beschreibung hinterlegen, z. B. Notruf oder Fax-Pool.
            </flux:text>
        </div>
        <flux:button wire:click="openCreateForm" icon="plus" variant="primary">Neue Reservierung</flux:button>
    </div>

    <flux:input
        wire:model.live.debounce.300ms="search"
        placeholder="Nach Durchwahl oder Beschreibung suchen..."
        icon="magnifying-glass"
    />

    @if(empty($this->filteredReservations))
        <flux:callout variant="subtle">
            @if($search !== '')
                Keine Reservierungen gefunden, die „{{ $search }}“ enthalten.
            @else
                Noch keine Reservierungen hinterlegt.
            @endif
        </flux:callout>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Durchwahl</flux:table.column>
                <flux:table.column>Beschreibung</flux:table.column>
                <flux:table.column>Aktionen</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->filteredReservations as $reservation)
                    <flux:table.row wire:key="reservation-{{ $reservation->id }}">
                        <flux:table.cell>{{ $reservation->extension_display }}</flux:table.cell>
                        <flux:table.cell>{{ $reservation->description }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button size="xs" wire:click="openEditForm({{ $reservation->id }})">Bearbeiten</flux:button>
                                <flux:button
                                    size="xs"
                                    variant="danger"
                                    wire:click="delete({{ $reservation->id }})"
                                    wire:confirm="Reservierung wirklich löschen?"
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

    <flux:modal wire:model="showForm" class="md:w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $isEditing ? 'Reservierung bearbeiten' : 'Reservierung anlegen' }}</flux:heading>

            <flux:input
                wire:model="formExtensionFrom"
                label="Durchwahl von"
                placeholder="z.B. 110"
                inputmode="numeric"
                required
            />
            <flux:input
                wire:model="formExtensionTo"
                label="Durchwahl bis (optional)"
                placeholder="z.B. 959 für Bereich 950–959"
                inputmode="numeric"
            />
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                Für eine einzelne Durchwahl nur „von“ ausfüllen. Für einen Bereich beide Felder setzen.
            </flux:text>
            <flux:input wire:model="formDescription" label="Beschreibung" placeholder="z.B. Notruf" required />

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showForm', false)">Abbrechen</flux:button>
                <flux:button type="submit" variant="primary">Speichern</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
