<?php

namespace Hwkdo\IntranetAppCisco;

use Hwkdo\IntranetAppCisco\Commands\IntranetAppCiscoCommand;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IntranetAppCiscoServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('intranet-app-cisco')
            ->hasConfigFile()
            ->hasViews()
            ->discoversMigrations();
    }

    public function boot(): void
    {
        parent::boot();
        // Gate::policy(Raum::class, RaumPolicy::class);
        $this->app->booted(function () {
            Volt::mount(__DIR__.'/../resources/views/livewire');
            Livewire::addNamespace('intranet-app-cisco', __DIR__.'/../resources/views/livewire');
        });
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

    }
}
