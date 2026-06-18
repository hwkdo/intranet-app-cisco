<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppCisco\Support;

class CiscoModels
{
    public static function userClass(): string
    {
        return (string) config('intranet-app-cisco.user_model', \App\Models\User::class);
    }
}
