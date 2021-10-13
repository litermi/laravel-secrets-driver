<?php

namespace Litermi\SecretsDriver\Traits;

use \Exception;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Str;
use Litermi\SecretsDriver\Exceptions\SecretsManagerException;
use Litermi\SecretsDriver\Exceptions\SecretsRetrievalException;
use Litermi\SecretsDriver\Managers\Contracts\ManagesSecrets;

/**
 * Reusable trait to retrieve remote secrets from a secrets manager.
 * 
 * Methods are protected, as secrets should not be retrieved from outside
 * the implementing class.
 * 
 * @author Carlos González (carlos dot gonzalez at litermi dot com)
 */
trait HasRemoteSecrets
{
    protected ?ManagesSecrets $secretsManager = null;

    /**
     * Protected method to retrieve a secret's value from the manager
     * @param  string Full secret name
     * @return mixed  The value
     * @throws SecretsManagerException|SecretsRetrievalException
     */
    protected function getRemoteSecretData(string $secret)
    {
        if (is_null($this->secretsManager)) {
            $this->secretsManager = app()->make(ManagesSecrets::class);
        }

        return $this->secretsManager
                        ->getSecret($secret);
    }

    /**
     * Protected method to check if the current environment is production
     * 
     * @return boolean
     */
    protected function isEnvProduction()
    {
        if (function_exists("is_env_production")) {
            /* Use previously-established criteria */
            return is_env_production();
        }

        return app()->environment(['prod', 'production', 'produccion', 'producción']);
    }

    /**
     * Protected method to check if the current environment is local
     * 
     * @return boolean
     */
    protected function isEnvLocal()
    {
        if (function_exists("is_env_local")) {
            /* Use previously-established criteria */
            return is_env_local();
        }

        return app()->environment(['local']);
    }

    /**
     * Protected function to return the project's desired production tag for secrets' names
     * 
     * @return string The tag
     */
    protected function getProductionTag()
    {
        return config("secrets-driver.production-tag");
    }

    /**
     * Protected method to get a normalized environment tag
     * @return string The tag
     */
    protected function getEnvTag()
    {
        /** @var string Normalized production tag */
        $normalProdTag = $this->getProductionTag();

        /** @var string Raw environment tag */
        $tag = Str::lower(
            app()->environment()
        );

        if ($this->isEnvProduction() && !empty($normalProdTag)) {
            /* Normalize environment tag */
            $tag = $normalProdTag;
        }

        return $tag;
    }

    /**
     * Protected method to get the cache key prefix
     * 
     * @return  string            The prefix
     */
    protected function getCacheKeyPrefix()
    {
        return config("secrets-driver.cache-key-prefix.regular");
    }

    /**
     * Potected method to get the notification cache key prefix
     * 
     * @return  string            The prefix
     */
    protected function getNotificationCacheKeyPrefix()
    {
        return config("secrets-driver.cache-key-prefix.notification");
    }

    /**
     * Protected metod to return the name of the project tag
     * @return string       The project tag
     */
    protected function getProjectTag()
    {
        return config("secrets-driver.project-tag");
    }

    /**
     * Protected method to get a time interval from a string
     * 
     * @return CarbonInterval Time interval
     */
    protected function createTimeIntervalFromString(string $interval)
    {
        return CarbonInterval::fromString($interval);
    }

    /**
     * Protected method to create a date from an interval.
     * 
     * If no date is given, now() is used.
     * 
     * @param string Time interval
     * @param null|Carbon Date instance to add to
     * @param Carbon New date
     */
    protected function createDateFromTimeInterval(string $interval, Carbon $date = null)
    {
        if (is_null($date)) {
            $date = now();
        }

        return $date->add(
            $this->createTimeIntervalFromString($interval)
        );
    }

    /**
     * Protected method to get the time interval for regular cache
     * 
     * @return string Time interval
     */
    protected function getCacheInterval()
    {
        return config("secrets-driver.cache-interval.regular");
    }

    /**
     * Protected method to get the time interval for backup cache
     * 
     * @return string Time interval
     */
    protected function getBackupCacheInterval()
    {
        return config("secrets-driver.cache-interval.backup");
    }

    /**
     * Protected method to get the time interval for notification cache
     * 
     * @return string Time interval
     */
    protected function getNotificationCacheInterval()
    {
        return config("secrets-driver.cache-interval.notification");
    }

    /**
     * Protected method to get the raw level of severity a retrieval failure should log
     * 
     * @return string Level of severity tag (according to RFC 5424)
     */
    protected function getSeverityLevel()
    {
        return config("secrets-driver.severity-level");
    }

    /**
     * Protected method to get the level of severity a retrieval failure should log,
     * normalized for log usage (lowercase and for use of the Log façade)
     * 
     * @return string Level of severity tag
     */
    protected function getNormalizedSeverityLevel()
    {
        /** @var string Severity level */
        $severity = Str::lower(
            $this->getSeverityLevel()
        );

        if ($severity == "informational") {
            /* Normalize level tag */
            $severity = "info";
        }

        return $severity;
    }

    /**
     * Potected method to prefix a cache key prefix
     * 
     * @param   string $suffix    Data to add to the prefix
     * @return  string            The prefix (or complete key,
     *                            if suffix present)
     */
    protected function getPrefixedCacheKey(string $suffix = null)
    {
        /** @var string Prefix for the cache key prefix */
        $key = $this->getCacheKeyPrefix();

        /* Add the prefix */
        if (strlen($stringSuffix = (string) $suffix) > 0) {
            $key .= "-{$this->getProjectTag()}-{$stringSuffix}";
        }

        return $key;
    }

    /**
     * Potected method to prefix a notification cache key prefix
     * 
     * @param   string $suffix    Data to add to the prefix
     * @return  string            The prefix (or complete key,
     *                            if suffix present)
     */
    protected function getPrefixedNotificationCacheKey(string $suffix = null)
    {
        /** @var string Prefix for the notification cache key prefix */
        $key = $this->getNotificationCacheKeyPrefix();

        /* Add the prefix */
        if (strlen($stringSuffix = (string) $suffix) > 0) {
            $key .= "-{$this->getProjectTag()}-{$stringSuffix}";
        }

        return $key;
    }

    /**
     * Protected method to check if a particular cache exists
     * @param   string  $key Raw key name
     * @return  boolean Presence flag
     */
    protected function cacheExists(string $key)
    {
        return !is_null(
            cache(
                $this->getPrefixedCacheKey($key)
            )
        );
    }

    /**
     * Protected method to get the value of a particular cache
     * @param   string  $key Raw key name
     * @return  mixed        Value
     */
    protected function getCache(string $key)
    {
        /** @var mixed Value stored */
        $value = null;

        if ($this->cacheExists($key)) {
            $value = cache(
                $this->getPrefixedCacheKey($key)
            );
        }

        return $value;
    }

    /**
     * Protected method to check if a particular notification cache exists
     * @param   string  $key Raw key name
     * @return  boolean Presence flag
     */
    protected function notificationCacheExists(string $key)
    {
        return !is_null(
            cache(
                $this->getPrefixedNotificationCacheKey($key)
            )
        );
    }

    /**
     * Protected method to create a cache key
     * @param string      $key   Raw key name
     * @param mixed       $value Value to store
     * @return void
     */
    protected function createCache(string $key, $value)
    {
        if (!$this->cacheExists($key)) {
            /** @var string Processed cache name */
            $cacheKey = $this->getPrefixedCacheKey($key);

            /** Set the cache key and a backup, in case it expires */
            cache(
                ["{$cacheKey}" => $value,],
                $this->createDateFromTimeInterval(
                    $this->getCacheInterval()
                )
            );

            cache(
                ["{$cacheKey}" => $value,],
                $this->createDateFromTimeInterval(
                    $this->getBackupCacheInterval()
                )
            );
        }
    }

    /**
     * Protected method to create a notification cache key to tell the error was sent
     * @param string      $reason Message for notification
     * @param null|string $key    Raw key name that generated the issue
     * @return void
     */
    protected function createNotificationCache(string $reason, string $key = null)
    {
        $projectTag = $this->getProjectTag();
        $env = $this->getEnvTag();

        /** @var string Error message to log */
        $errorMessage = "Could not retrieve external secret data for key '{$key}' for project '{$projectTag}' on environment '{$env}' - {$reason}";

        if (is_null($key)) {
            $key = "general";
            $errorMessage = "Could not retrieve external secret data for project '{$projectTag}' on environment '{$env}' - {$reason}";
        }

        if (!$this->notificationCacheExists($key)) {
            /* To avoid log service flooding */
            cache(
                ["{$this->getPrefixedNotificationCacheKey($key)}" => 'sent',],
                $this->createDateFromTimeInterval(
                    $this->getNotificationCacheInterval()
                )
            );
        }

        /* Logging required! */
        logger()->{$this->getNormalizedSeverityLevel()}($errorMessage);
    }

    /**
     * Protected method to retrieve the secret's remote name format
     * 
     * @return string The format
     */
    protected function getSecretNameFormat()
    {
        return config("secrets-driver.secret-name-format");
    }

    /**
     * Protected method to build a remote secret name
     * 
     * @param  string $key      Secret basename
     * @param  string $project  Project identifier
     * @param  string $env      Environment tag
     * @return string           Full remote name
     */
    protected function parseRemoteSecretName(string $key, string $project, string $env)
    {
        /** @var string Keyname format */
        $keyname = $this->getSecretNameFormat();

        /* Build the keyname by changing placeholders for final values */

        $keyname = Str::replace('$key', $key, $keyname);
        $keyname = Str::replace('$project', $project, $keyname);
        $keyname = Str::replace('$env', $env, $keyname);

        return $keyname;
    }

    /**
     * Protected function to get configuration data from the vendor
     * 
     * @param   string|string[] $secrets    Secret name(s)
     * @return  mixed|mixed[]               Configuration data.
     *                                      If multiple secrets,
     *                                      array keyed by secret name
     */
    protected function getSecret($secrets)
    {
        /** @var string Current project tag */
        $projectTag = $this->getProjectTag();

        /** @var string Current environment name */
        $env = $this->getEnvTag();

        /** @var boolean Flag to check if data should be returned keyed or not  */
        $singleSecret = false;

        if (!is_array($secrets)) {
            $secrets = [
                (string) $secrets
            ];

            $singleSecret = true;
        }

        /** @var mixed[] Connection data to return */
        $secretsData = collect($secrets)
                                ->mapWithKeys(function ($value, $key) {
                                    return [
                                        "{$value}" => [], /* No data */
                                    ];
                                })
                                ->all();

        try {
            foreach ($secrets as $secret) {
                /* Check for data in Cache */
                if ($this->cacheExists($secret)) {
                    $secretsData[$secret] = $this->getCache($secret);
                    continue;
                }

                /* Not in Cache; get it from the secrets manager */

                /* Currently (2021-09-28), there is no way to bulk-retrieve secrets */

                /** @var string Full remote secret name */
                $secretName = $this->parseRemoteSecretName($secret, $projectTag, $env);

                /** @var mixed[] Parsed database configuration */
                $configs = $this->getRemoteSecretData($secretName);
                
                /** Set the cache key and a backup, in case it expires */
                $this->createCache($secret, $configs);
                
                /** Add data to return */
                $secretsData[$secret] = $configs;
            }
        } catch (SecretsRetrievalException $sre) {
            /** @var string Latest connection name */
            $secret = empty($secret)
                            ? 'none'
                            : $secret;

            /* To avoid log service flooding */
            $this->createNotificationCache(
                $ae->getMessage(),
                $secret
            );
        } catch (SecretsManagerException | Exception $e) {
            /* To avoid log service flooding */
            $this->createNotificationCache($e->getMessage());
        }

        if ($singleSecret) {
            /* Only one secret; remove superfluous level */
            $secretsData = collect($secretsData)
                                ->flatten(1)
                                ->all();

            if (count($secretsData) == 1 && !is_array($secretValue = reset($secretsData))) {
                /* Not an array; return the primitive only */
                $secretsData = $secretValue;
            }
        }

        return $secretsData;
    }
}