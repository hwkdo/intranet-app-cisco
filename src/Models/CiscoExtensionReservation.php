<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CiscoExtensionReservation extends Model
{
    /** @use HasFactory<\Hwkdo\IntranetAppCisco\Database\Factories\CiscoExtensionReservationFactory> */
    use HasFactory;

    protected $fillable = [
        'extension_from',
        'extension_to',
        'description',
    ];

    public function rangeStart(): int
    {
        return (int) $this->extension_from;
    }

    public function rangeEnd(): int
    {
        return (int) ($this->extension_to ?? $this->extension_from);
    }

    public function isRange(): bool
    {
        return $this->extension_to !== null && $this->extension_to !== '';
    }

    public function getExtensionDisplayAttribute(): string
    {
        if ($this->isRange()) {
            return $this->extension_from.' - '.$this->extension_to;
        }

        return $this->extension_from;
    }

    /**
     * @param  Builder<CiscoExtensionReservation>  $query
     * @return Builder<CiscoExtensionReservation>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('extension_from')->orderBy('extension_to');
    }

    public static function overlaps(string $extensionFrom, ?string $extensionTo, ?int $ignoreId = null): bool
    {
        $start = (int) $extensionFrom;
        $end = (int) ($extensionTo ?? $extensionFrom);

        return self::query()
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->get()
            ->contains(function (CiscoExtensionReservation $reservation) use ($start, $end): bool {
                return $start <= $reservation->rangeEnd() && $end >= $reservation->rangeStart();
            });
    }

    /**
     * @throws ValidationException
     */
    public static function assertNoOverlap(string $extensionFrom, ?string $extensionTo, ?int $ignoreId = null): void
    {
        if (self::overlaps($extensionFrom, $extensionTo, $ignoreId)) {
            throw ValidationException::withMessages([
                'formExtensionFrom' => 'Die Durchwahl oder der Bereich überschneidet sich mit einer bestehenden Reservierung.',
            ]);
        }
    }
}
