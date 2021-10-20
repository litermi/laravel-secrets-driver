# Secrets Driver for Litermi Laravel Projects

## Features

- Reusable trait
- Ready-to-go database configuration.
- Support for custom secrets managers.
- Overridable configuration.

## Install

### Composer install

```bash
composer require litermi/laravel-secrets-driver
```

### Configure your project's tag

By default, the package takes the application's name and creates a tag using Laravel's `Str::slug()`. If there's no name assigned, the tag will be `"litermi-project"`. In case this needs to be overriden, the environmental variable `SECRETS_DRIVER_PROJECT_TAG` can be changed to provide a new value. If the new tag is not a proper slug, it will be converted to one.

### Create secrets in the Secrets Manager

The default naming convention is:  `<environmenttag>/<projecttag>/<secretname>`

In case this needs to be changed, the environmental variable `SECRETS_DRIVER_NAME_FORMAT` can be changed to provide a new format using a literal (string delimited by single quotes: `'`) using the values `$key`, `$project` and `$env`. The default value is `'$env/$project/$key'`.

**Ex.: stage/blog/tpps_mysql**

Sometimes, the environment production tag can be different on different setups (ex.: `prod`, `production`, `produccion`, `producción`...); to normalize to a single value, the environmental variable `SECRETS_DRIVER_PRODUCTION_TAG` can be changed to a single one. If this environmental variable is set as empty, the production tag will be the same as `APP_ENV`. The default value is `"prod"`, so a typical production key would be searched in the provider as `prod/<projecttag>/<secretname>`.

#### AWS Secrets Manager
By default, the package is configured to work with the AWS Secrets Manager. To use it, configure Laravel's common AWS environmental variables in the `.env` file and create the secrets using the stated convention. You can use either key/value pairs or a JSON string.

#### Custom Secrets Manager

To select a secrets manager, the environmental variable `SECRETS_DRIVER_MANAGER` can be changed to provide a new value; this value can be a full classname or a string that can be made a StudlyCase prefix for a classname (either on `Litermi\SecretsDriver\Managers\<TagName>SecretsManager` or a local equivalent on `App\SecretsDriver\Managers\<TagName>SecretsManager`). Whatever the case, the target class must implement the `Litermi\SecretsDriver\Managers\Interfaces\ManagesSecrets` interface. The default value is `"aws"`.

## Trait usage

To retrieve secrets within any object, make its class use the `Litermi\SecretsDriver\Traits\HasRemoteSecrets` trait; then, retrieve the secret by its name using the `$this->getSecret()` method:

```php
    /* Environment: stage; Project tag: trllnhelp */

    use Illuminate\Support\ServiceProvider;
    use Litermi\SecretsDriver\Traits\HasRemoteSecrets;

    class TppsConfigProvider extends ServiceProvider
    {
        /* Trait included! */
        use HasRemoteSecrets;

        public function boot()
        {
            /**
             * New configuration data for the sent secret
             * 
             * This will look for secret "stage/trllnhelp/tpps",
             * which is a JSON string that represents an object
             * with properties.
             * 
             * If the secret is not found, it'll return
             * an empty array and log an incidence.
             * 
             * @var mixed[]
             */
            $tppsData = $this->getSecret("tpps");

            /* Example: rewrite configuration */
            foreach ($tppsData as $key => $value) {
                /* Change the data */
                config([
                    "tpps.{$key}" => $value,
                ]);
            }
        }
        
        public function register()
        {
            //
        }
    }
```

The `$this->getSecret()` method accepts a single parameter: a string or an array of strings. If the parameter is an array, it'll return a keyed array, with the keys being the secrets' names.

## Trait customization

In case the trait's methods need to be changed (for example, to make a specific configuration override that must not be project-wide), include a method override in the class that uses the trait. Refer to the trait's code to know which method to override.

```php
    use Litermi\SecretsDriver\Traits\HasRemoteSecrets;

    class PaywayRepository
    {
        /* Trait included! */
        use HasRemoteSecrets;

        /**
         * Protected method to get the cache key prefix
         * 
         * Override of HasRemoteSecrets::getCacheKeyPrefix()
         * 
         * @return  string            The prefix
         */
        protected function getCacheKeyPrefix()
        {
            return "payway-data";
        }

        /**
         * Protected method to get the time interval for regular cache
         * 
         * Override of HasRemoteSecrets::getCacheInterval()
         * 
         * @return string Time interval
         */
        protected function getCacheInterval()
        {
            return "15s";
        }
        ...
    }
```

## Database configuration retrieval

In case database credentials need to be taken from the remote manager, add the `use_secrets_driver` key to the connection's configuration inside the `config/database.php` file and set it to `true`:

```php
    ...

    'connections' => [

        'mysql' => [
            ...
            'use_secrets_driver' => true,
        ],
        ...
``` 

If the current environment is set as `local`, database credentials will be the same as in Laravel's `.env` file, to allow usage of the local database for testing and configuration; otherwise, when on a non-local environment, any connection that has the property `use_secrets_driver` set as `true` will be looked for in the remote secrets manager, using the name convention and the connection name as the secret's name:

**Ex.: stage/blog/mysql**

There is no minimum database configuration needed to be saved in the secret; however, it is recommended that these variables be provided (values are examples):

```javascript
{
  "database": "blog_database",
  "driver": "mysql",
  "host": "127.0.0.1",
  "password": "*******",
  "port": "3306",
  "username": "mysql_user",
  "charset": "utf8mb4",
  "collation": "utf8mb4_unicode_ci"
}
```

## Cache configuration

The package uses the project's cache driver and sets different durations for different cache values. The following block shows the environmental variables and their defaults. If these variables are not present, these defaults will still apply.

```env
    SECRETS_DRIVER_CACHE_INTERVAL="30s"
    SECRETS_DRIVER_CACHE_BACKUP_INTERVAL="12h"
    SECRETS_DRIVER_CACHE_NOTIFICATION_INTERVAL="10s"
```

## Configuration publishing

If further configuration needs to be changed, it's possible to publish the configuration to the project's `config` directory:

```bash
php artisan vendor:publish --tag=secrets-driver-config
```

## License

Litermi Secrets Driver for Laravel is released under the MIT Licence. See the bundled [LICENSE](https://github.com/litermi/elasticlog/blob/master/LICENSE.md) file for details.


## Acknowledgements

This package is heavily influenced by the previous work made in litermi/aws-secret-dbdriver, authored by Diego Cotelo (diego.cotelo@litermi.com).