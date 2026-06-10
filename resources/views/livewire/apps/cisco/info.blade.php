<?php

use function Livewire\Volt\{title};

title('Cisco - App-Info');

?>

<x-intranet-app-cisco::cisco-layout heading="App-Info" subheading="Installierte Version und Release-Historie">
    @livewire('intranet-app-base::app-info', ['appIdentifier' => 'cisco'])
</x-intranet-app-cisco::cisco-layout>
