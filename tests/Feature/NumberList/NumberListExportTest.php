<?php

declare(strict_types=1);

use App\Models\User;
use Hwkdo\CiscoPhoneServicesLaravel\Interfaces\AxlServiceInterface;
use Hwkdo\IntranetAppCisco\Exports\ExtensionDirectoryExport;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\mock;

beforeEach(function () {
    Permission::findOrCreate('manage-app-cisco', 'web');
    config()->set('cisco-phone-services-laravel.axl.pattern', '\+492315493');
});

function mockNumberListExportAxlService(): void
{
    mock(AxlServiceInterface::class)
        ->shouldReceive('listLines')
        ->andReturn([
            [
                'pattern' => '\+492315493518',
                'description' => 'Max Mustermann',
                'alerting_name' => '',
                'uuid' => '',
                'usage' => 'Device',
                'route_partition' => 'PHONES',
                'calling_search_space' => '',
                'calling_permission' => '—',
            ],
        ])
        ->shouldReceive('listCallPickupGroups')
        ->andReturn([])
        ->shouldReceive('listHuntPilots')
        ->andReturn([]);
}

test('number list can export entire directory to excel', function () {
    Excel::fake();

    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mockNumberListExportAxlService();

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.number-list.index')
        ->call('exportExcelAll');

    Excel::assertDownloaded('nummernliste-alle-*.xlsx', function (ExtensionDirectoryExport $export): bool {
        return $export->collection()->count() === 900
            && $export->headings() === ['Durchwahl', 'Bemerkung', 'Abteilung'];
    });
});

test('number list can export filtered directory to excel', function () {
    Excel::fake();

    $user = User::factory()->create();
    $user->givePermissionTo('manage-app-cisco');

    mockNumberListExportAxlService();

    Livewire::actingAs($user)
        ->test('intranet-app-cisco::apps.cisco.number-list.index')
        ->set('showOnlyFree', true)
        ->call('exportExcelFiltered');

    Excel::assertDownloaded('nummernliste-gefiltert-*.xlsx', function (ExtensionDirectoryExport $export): bool {
        return $export->collection()->every(fn (array $entry): bool => $entry['is_free']);
    });
});
