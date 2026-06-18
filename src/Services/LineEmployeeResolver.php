<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Services;

use Hwkdo\IntranetAppCisco\Data\ResolvedLineEmployee;
use Hwkdo\IntranetAppCisco\Support\CiscoModels;
use Hwkdo\IntranetAppCisco\Support\EmployeeNameParser;
use Hwkdo\IntranetAppCisco\Support\ExtensionNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LineEmployeeResolver
{
    /** @var array<string, list<object>>|null */
    private ?array $userIndex = null;

    /** @var array<int, list<object>>|null */
    private ?array $extensionIndex = null;

    /**
     * @param  array<string, mixed>  $line
     */
    public function resolveForLine(array $line): ?ResolvedLineEmployee
    {
        $extension = ExtensionNormalizer::toExtension((string) ($line['pattern'] ?? ''));

        if ($extension !== null) {
            $resolved = $this->resolveByExtension($extension, $line);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        foreach (['alerting_name', 'description'] as $field) {
            $resolved = $this->resolveFromLabel(
                (string) ($line[$field] ?? ''),
                $extension
            );

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    public function enrichLines(array $lines): array
    {
        return array_map(function (array $line): array {
            $line['department'] = $this->resolveForLine($line)?->department;

            return $line;
        }, $lines);
    }

    public function resolveFromLabel(?string $label, ?int $extension = null): ?ResolvedLineEmployee
    {
        $parsed = EmployeeNameParser::parse($label);

        if ($parsed === null) {
            return null;
        }

        $key = $this->indexKey($parsed['nachname'], $parsed['vorname']);
        $candidates = $this->userIndex()[$key] ?? [];

        if ($candidates === []) {
            return null;
        }

        if (count($candidates) === 1) {
            return $this->toResolvedEmployee($candidates[0]);
        }

        if ($extension === null) {
            return null;
        }

        foreach ($candidates as $candidate) {
            if ($this->extensionForUser($candidate) === $extension) {
                return $this->toResolvedEmployee($candidate);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function resolveByExtension(int $extension, array $line): ?ResolvedLineEmployee
    {
        $candidates = $this->extensionIndex()[$extension] ?? [];

        if ($candidates === []) {
            return null;
        }

        if (count($candidates) === 1) {
            return $this->toResolvedEmployee($candidates[0]);
        }

        foreach (['alerting_name', 'description'] as $field) {
            $parsed = EmployeeNameParser::parse((string) ($line[$field] ?? ''));

            if ($parsed === null) {
                continue;
            }

            $key = $this->indexKey($parsed['nachname'], $parsed['vorname']);

            foreach ($candidates as $candidate) {
                $vorname = trim((string) ($candidate->vorname ?? ''));
                $nachname = trim((string) ($candidate->nachname ?? ''));

                if ($this->indexKey($nachname, $vorname) === $key) {
                    return $this->toResolvedEmployee($candidate);
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, list<object>>
     */
    private function userIndex(): array
    {
        $this->ensureIndexesBuilt();

        return $this->userIndex ?? [];
    }

    /**
     * @return array<int, list<object>>
     */
    private function extensionIndex(): array
    {
        $this->ensureIndexesBuilt();

        return $this->extensionIndex ?? [];
    }

    private function ensureIndexesBuilt(): void
    {
        if ($this->userIndex !== null && $this->extensionIndex !== null) {
            return;
        }

        /** @var class-string<Model> $userClass */
        $userClass = CiscoModels::userClass();

        /** @var Builder<Model> $query */
        $query = $userClass::query();

        if ($this->hasScope($userClass, 'aktiv')) {
            $query->aktiv();
        }

        $this->userIndex = [];
        $this->extensionIndex = [];

        foreach ($query->with('gvp')->get(['id', 'vorname', 'nachname', 'telefon', 'gvp_id']) as $user) {
            $vorname = trim((string) ($user->vorname ?? ''));
            $nachname = trim((string) ($user->nachname ?? ''));

            if ($vorname !== '' && $nachname !== '') {
                $key = $this->indexKey($nachname, $vorname);
                $this->userIndex[$key][] = $user;
            }

            $extension = $this->extensionForUser($user);

            if ($extension !== null) {
                $this->extensionIndex[$extension][] = $user;
            }
        }
    }

    private function indexKey(string $nachname, string $vorname): string
    {
        return EmployeeNameParser::normalize($nachname).'|'.EmployeeNameParser::normalize($vorname);
    }

    private function toResolvedEmployee(object $user): ResolvedLineEmployee
    {
        $department = $this->formatDepartment($user->gvp ?? null);

        return new ResolvedLineEmployee(
            user: $user,
            department: $department ?? '',
        );
    }

    private function formatDepartment(?object $gvp): ?string
    {
        if ($gvp === null) {
            return null;
        }

        $bezeichnung = trim((string) ($gvp->bezeichnung ?? ''));

        if ($bezeichnung !== '') {
            return $bezeichnung;
        }

        $name = trim((string) ($gvp->name ?? ''));

        return $name !== '' ? $name : null;
    }

    private function extensionForUser(object $user): ?int
    {
        $telefon = trim((string) ($user->telefon ?? ''));

        if ($telefon === '') {
            return null;
        }

        if (preg_match('/-(\d{3})(?:\D|$)/', $telefon, $matches) === 1) {
            $extension = (int) $matches[1];

            return ExtensionNormalizer::isValidExtension($extension) ? $extension : null;
        }

        return ExtensionNormalizer::toExtension($telefon);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function hasScope(string $modelClass, string $scope): bool
    {
        return method_exists($modelClass, 'scope'.ucfirst($scope));
    }
}
