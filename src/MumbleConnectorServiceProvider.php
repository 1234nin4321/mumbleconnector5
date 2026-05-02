<?php

namespace Seat\MumbleConnector;

use Seat\Services\AbstractSeatPlugin;
use Seat\MumbleConnector\Commands\SyncMumbleUsers;
use Seat\MumbleConnector\Commands\SyncMumbleGroups;
use Seat\MumbleConnector\Commands\CleanupMumbleGuestLinks;

class MumbleConnectorServiceProvider extends AbstractSeatPlugin
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->addRoutes();
        $this->addViews();
        $this->addMigrations();
        $this->addTranslations();
        $this->addConfig();
        $this->addMenu();
    }

    /**
     * Register sidebar menu.
     */
    private function addMenu(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/menu.sidebar.php',
            'package.sidebar'
        );
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/mumble.php',
            'seat.mumble'
        );

        $this->commands([
            SyncMumbleUsers::class,
            SyncMumbleGroups::class,
            CleanupMumbleGuestLinks::class,
        ]);
    }

    /**
     * Register routes.
     */
    private function addRoutes(): void
    {
        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }
    }

    /**
     * Register views.
     */
    private function addViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'mumble');
    }

    /**
     * Register migrations.
     */
    private function addMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    /**
     * Register translations.
     */
    private function addTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'mumble');
    }

    /**
     * Add config.
     */
    private function addConfig(): void
    {
        $this->publishes([
            __DIR__ . '/Config/mumble.php' => config_path('seat-mumble.php'),
        ], 'config');
    }

    /**
     * Return the plugin public name.
     */
    public function getName(): string
    {
        return 'Mumble Connector';
    }

    /**
     * Return the plugin description.
     */
    public function getDescription(): string
    {
        return 'Sync SeAT users and roles to Mumble server';
    }

    /**
     * Return the package repository URL.
     */
    public function getPackageRepositoryUrl(): string
    {
        return 'https://github.com/seat-plugins/mumble-connector';
    }

    /**
     * Return the packagist package name.
     */
    public function getPackagistPackageName(): string
    {
        return 'mumble-connector';
    }

    /**
     * Return the packagist vendor name.
     */
    public function getPackagistVendorName(): string
    {
        return 'seat-plugins';
    }
}
