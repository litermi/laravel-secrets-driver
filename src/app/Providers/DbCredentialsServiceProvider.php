<?php

namespace Litermi\SecretsDriver\Providers;

use Litermi\SecretsDriver\Traits\HasRemoteSecrets;
use DB;
use Illuminate\Support\ServiceProvider;

/**
 * Provider class to retrieve database credentials for any connection
 * in the database configuration where the 'use_vault' property is present
 * and set as true
 * 
 * @author Carlos González (carlos dot gonzalez at litermi dot com)
 */
class DbCredentialsServiceProvider extends ServiceProvider
{
    use HasRemoteSecrets;

    /**
     * Potected method to get the cache key prefix
     * 
     * @return  string            The prefix
     */
    protected function getCacheKeyPrefix()
    {
        return "database-config";
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isEnvLocal()) {
            /* Environment is local; skip and use local data */
            return;
        }

        /* If environment is not local, use remote data instead */

        /** @var string Main database connections config root key */
        $topDbConfigsKey = "database.connections";

        /** @var Collection|array[] Connection definitions that use Vault */
        $vaultConnections = collect(config($topDbConfigsKey))
                                ->where("use_secrets_driver", true);

        /** @var array[] New configuration data for the sent connections */
        $rawConnectionData = $this->getSecret(
            $vaultConnections
                ->keys()
                ->all()
        );

        /** Purge each connection and set the new data */
        foreach ($rawConnectionData as $connectionName => $connectionData) {
            if (empty($connectionData)) {
                /* No data available. Skip and try the next */
                continue;
            }

            /** @var mixed[] New values to rewrite */
            $newConfigs = collect($connectionData)
                            ->mapWithKeys(function ($value, $key) use ($topDbConfigsKey, $connectionName) {
                                return [
                                    "{$topDbConfigsKey}.{$connectionName}.{$key}" => $value,
                                ];
                            })
                            ->all();

            /* Purge current configuration */
            DB::purge($connectionName);

            /* Change the data */
            config($newConfigs);
        }
    }
}
