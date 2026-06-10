<?php

use function Livewire\Volt\{title};

title('Cisco - Meine Einstellungen');

?>

<x-intranet-app-cisco::cisco-layout heading="Meine Einstellungen" subheading="Persönliche Einstellungen für die Cisco App">
    @livewire('intranet-app-base::user-settings', ['appIdentifier' => 'cisco'])
</x-intranet-app-cisco::cisco-layout>
