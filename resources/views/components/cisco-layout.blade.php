@props([
    'heading' => '',
    'subheading' => '',
    'navItems' => []
])

@php
    $defaultNavItems = [
        ['label' => 'Übersicht', 'href' => route('apps.cisco.index'), 'icon' => 'home', 'description' => 'Zurück zur Übersicht', 'buttonText' => 'Übersicht anzeigen'],
        ['label' => 'Beispielseite', 'href' => route('apps.cisco.example'), 'icon' => 'document-text', 'description' => 'Beispielseite anzeigen', 'buttonText' => 'Beispielseite öffnen'],
        ['label' => 'Meine Einstellungen', 'href' => route('apps.cisco.settings.user'), 'icon' => 'cog-6-tooth', 'description' => 'Persönliche Einstellungen anpassen', 'buttonText' => 'Einstellungen öffnen'],
        ['label' => 'App-Info', 'href' => route('apps.cisco.info'), 'icon' => 'information-circle', 'description' => 'Installierte Version und Release-Historie', 'buttonText' => 'App-Info anzeigen'],
        ['label' => 'Geräte', 'href' => route('apps.cisco.devices.index'), 'icon' => 'device-phone-mobile', 'description' => 'CUCM-Geräte verwalten', 'buttonText' => 'Geräte öffnen', 'permission' => 'manage-app-cisco'],
        ['label' => 'Lines', 'href' => route('apps.cisco.lines.index'), 'icon' => 'hashtag', 'description' => 'Telefonnummern (Lines) verwalten', 'buttonText' => 'Lines öffnen', 'permission' => 'manage-app-cisco'],
        ['label' => 'User', 'href' => route('apps.cisco.users.index'), 'icon' => 'user', 'description' => 'CUCM-End-User verwalten', 'buttonText' => 'User öffnen', 'permission' => 'manage-app-cisco'],
        ['label' => 'Pickup Groups', 'href' => route('apps.cisco.pickup-groups.index'), 'icon' => 'phone', 'description' => 'Pickup Groups verwalten', 'buttonText' => 'Pickup Groups öffnen', 'permission' => 'manage-app-cisco'],
        ['label' => 'Admin', 'href' => route('apps.cisco.admin.index'), 'icon' => 'shield-check', 'description' => 'Administrationsbereich verwalten', 'buttonText' => 'Admin öffnen', 'permission' => 'manage-app-cisco']
    ];
    
    $navItems = !empty($navItems) ? $navItems : $defaultNavItems;
    $customBgUrl = \Hwkdo\IntranetAppBase\Models\AppBackground::getCustomBackgroundUrl('cisco');
@endphp

@if($customBgUrl)
    @push('app-styles')
    <style data-app-bg data-ts="{{ uniqid() }}">
        :root { --app-bg-image: url('{{ $customBgUrl }}'); }
    </style>
    @endpush
@endif

@if(request()->routeIs('apps.cisco.index'))
    <x-intranet-app-base::app-layout 
        app-identifier="cisco"
        :heading="$heading"
        :subheading="$subheading"
        :nav-items="$navItems"
        :wrap-in-card="false"
    >
        <x-intranet-app-base::app-index-auto 
            app-identifier="cisco"
            app-name="Cisco App"
            app-description="Generated app: Cisco"
            :nav-items="$navItems"
            welcome-title="Willkommen zur Cisco App"
            welcome-description="Dies ist eine Beispiel-App, die als Cisco für neue Intranet-Apps dient."
        />
    </x-intranet-app-base::app-layout>
@else
    <x-intranet-app-base::app-layout 
        app-identifier="cisco"
        :heading="$heading"
        :subheading="$subheading"
        :nav-items="$navItems"
        :wrap-in-card="true"
    >
        {{ $slot }}
    </x-intranet-app-base::app-layout>
@endif
