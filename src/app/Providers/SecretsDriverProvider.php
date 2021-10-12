<?php

namespace Litermi\SecretsDriver\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Litermi\SecretsDriver\Exceptions\SecretsManagerException;
use Litermi\SecretsDriver\Managers\Contracts\ManagesSecrets;

class SecretsDriverProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        /* Allow publication of config files */
        $this->publishes(
            [
                __DIR__.'/../../config/secrets-driver.php' => config_path('secrets-driver.php'),
            ],
            "secrets-driver-config" /* php artisan vendor:publish --tag=secrets-driver-config */
        );
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        /* Merge configuration into project */
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/secrets-driver.php',
            'secrets-driver'
        );

        $this->app->bind(ManagesSecrets::class, function ($app) {
            /** @var string Raw secrets manager identifier */
            $managerString = $app["config"]["secrets-driver"]["manager"];

            /** @var string Manager handle, as StudlyCase */
            $managerTag = Str::studly($managerString);

            /** @var string Manager class for further use */
            $managerClassname = "{$managerTag}SecretsManager";

            /** @var string Manager internal namespace; */
            $internalNamespace = "Litermi\\SecretsDriver\\Managers";

            /** @var string Manager internal namespace; */
            $localNamespace = "App\\SecretsDriver\\Managers";

            if (class_exists($managerString) && is_a($managerString, ManagesSecrets::class, true)) {
                /* Full class exists; load it */
                $managerClass = $managerString;
            } else if (class_exists($tempClass = "{$localNamespace}\\{$managerClassname}") && is_a($tempClass, ManagesSecrets::class, true)) {
                /* Local tag exists; load it */
                $managerClass = $tempClass;
            } else if (class_exists($tempClass = "{$internalNamespace}\\{$managerClassname}") && is_a($tempClass, ManagesSecrets::class, true)) {
                /* Internal tag exists; load it */
                $managerClass = $tempClass;
            } else {
                throw new SecretsManagerException("{$managerString} does not point to a valid Secrets Manager class");
            }

            return new $managerClass();
        });
    }
}