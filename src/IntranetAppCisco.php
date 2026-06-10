<?php

namespace Hwkdo\IntranetAppCisco;
use Hwkdo\IntranetAppBase\Interfaces\IntranetAppInterface;
use Illuminate\Support\Collection;

class IntranetAppCisco implements IntranetAppInterface 
{
    public static function app_name(): string
    {
        return 'Cisco';
    }

    public static function app_icon(): string
    {
        return 'magnifying-glass';
    }

    public static function identifier(): string
    {
        return 'cisco';
    }

    public static function roles_admin(): Collection
    {
        return collect(config('intranet-app-cisco.roles.admin'));
    }

    public static function roles_user(): Collection
    {
        return collect(config('intranet-app-cisco.roles.user'));
    }
    
    public static function userSettingsClass(): ?string
    {
        return \Hwkdo\IntranetAppCisco\Data\UserSettings::class;
    }
    
    public static function appSettingsClass(): ?string
    {
        return \Hwkdo\IntranetAppCisco\Data\AppSettings::class;
    }

    public static function mcpServers(): array
    {
        return [];
    }
}
