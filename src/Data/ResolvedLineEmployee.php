<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Data;

readonly class ResolvedLineEmployee
{
    public function __construct(
        public object $user,
        public string $department,
    ) {}
}
