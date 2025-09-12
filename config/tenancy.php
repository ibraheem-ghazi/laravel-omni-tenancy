<?php

use IbraheemGhazi\OmniTenancy\Core\TenantIdentifier;
use IbraheemGhazi\OmniTenancy\Identifiers\TenantIdentifierByDomain;
use IbraheemGhazi\OmniTenancy\Identifiers\TenantIdentifierByHeader;
use IbraheemGhazi\OmniTenancy\Identifiers\TenantIdentifierBySubdomain;

return [

    /**
     * global flag that control the Tenancy, when false, the main provider of the package will be disabled, 
     * leading to disable all functionalities of the tenancy and multi-tenants.
     */
    'enabled' => true,

    /**
     * the class that is ressponsible to read and find tenants and retreive it as TenantObject
     * available options:
     * - \IbraheemGhazi\OmniTenancy\Core\DatabaseTenantRegistry::class
     */
    'registry' => [
        'class' => \IbraheemGhazi\OmniTenancy\Core\DatabaseTenantRegistry::class,

        'enable_cache' => false,
    ],

    'database_manager' => \IbraheemGhazi\OmniTenancy\Core\Databases\MySQLTenantDatabaseManager::class,

    'database' => [
        /**
         * used in tenant creator, it allows the auto database credentials generator to add prefix to
         * database name, and username.
         */
        'prefix' => 'tenant_',

        /**
         * determine the privledges of the database user for the newly created tenants.
         */
        'grants' => [
            'ALTER', 'ALTER ROUTINE', 'CREATE', 'CREATE ROUTINE', 'CREATE TEMPORARY TABLES', 'CREATE VIEW',
            'DELETE', 'DROP', 'EVENT', 'EXECUTE', 'INDEX', 'INSERT', 'LOCK TABLES', 'REFERENCES', 'SELECT',
            'SHOW VIEW', 'TRIGGER', 'UPDATE',
        ],

        /**
         * Shared Migrations that run for all types of tenants (all routes group no matter what it is)
         * note: path is relative to database/migrations
         */
        'migration_paths' => [
            '/'
        ],

        /**
         * Route Group based migrations that only run if and only if the tenant has this route group
         * note: path is relative to database/migrations
         */
        'routes_group_migrations' => [
            'central' => [
                'routes-groups/central'
            ]
//            'ROUTES_GROUP_NAME' => [
//                //list of paths where this route group load its migrations
//            ]
        ],

        /**
         * Shared Seeder that run for all types of tenants (all routes group no matter what it is)
         */
        'seeder_class' => \Database\Seeders\SharedDatabaseSeeder::class,

        /**
         * Route Group based seeders that only run if and only if the tenant has this route group
         */
        'routes_group_seeders' => [
            'central' => \Database\Seeders\CentralOnlyDatabaseSeeder::class,
            'tenant' => \Database\Seeders\TenantOnlyDatabaseSeeder::class
//            'ROUTES_GROUP_NAME' => 'class to seed per route group'
        ],
    ],

    /**
     * Controls how the tenant creator class behave when creating a new tenant.
     */
    'creator' => [
        /**
         * control whether it should begin the migration automatically or not, the event TenantCreatorMigrateEvent::class is triggered
         * before the migration is called, but when this true, only the event is called and you have to call the migrate command manually:
         * Tenancy::newCreator()::migrateDatabase($tenantDto); 
         */
        'migrate_using_event_only' => false,
        /**
         * control whether it should begin the seed automatically or not, the event TenantCreatorSeedEvent::class is triggered
         * before the seed is called, but when this true, only the event is called and you have to call the migrate command manually:
         * Tenancy::newCreator()::seedDatabase($tenantDto); 
         * 
         * Side Note: Just for your information you can also call "Tenancy::newCreator()::callSeeder($tenantDto, Seeder::class)" to
         * seed a custom class if you needed.
         */
        'seed_using_event_only' => false,
    ],

    /**
     * when using multi database system, you can identify the central and tenants connections name here
     * where the "central" is where it should connect to use for DatabaseTenantRegistery.
     * where the "tenants" connection is where it should connect to read tenants related tables
     */
    'connections' => [
        'central' => 'mysql',
        'tenants' => 'mysql_tenancy'
    ],

    'console' => [
        /**
         * which commands are whitelisted to be run under tenancy context, the whitelisted command
         * needs to be run through "php artisan tenant:run TENANT_ID COMMAND ...ARGS +option=value +option ..."
         * and running it directly will through an error, the other not in the list commands will continue to run
         * as usual.
         * but you still can bypass this restriction (USE WITH CAUTION), by calling it as following:
         * "TENANCY_BYPASS=1 php artisan COMMAND ..."
         *
         * ** when we want to pass an option to a command, instead of --option=value we replace -- with +
         * ** so it became +option=value
         *
         * ** when calling using:
         * ** Artisan::call('tenant:run, ['cmd'=>'COMMAND', 'cmdArgs'=>['+param=value',]'+param=value', '+newparam']])
         */
        'allowed_commands' => [
            'migrate' => 'Run database migrations',
            'migrate:rollback' => 'Rollback migrations',
            'db:seed' => 'Seed the database',
            'cache:clear' => 'Clear application cache',
            'about' => 'Show about breif',
            'route:list' => 'Show routes breif',
            'backup:database' => 'Backup current database connection',
            'backup:files' => 'Backup current files from storage',
            'backup:full' => 'Backup current files from storage',
            'restore:database' => 'Restore a backup for database ',
            'restore:files' => 'Restore a backup for files',
            'restore:full' => 'Restore a backup for both files and database',
            'maintenance' => 'Set the maintenance mode for tenants either enable or disable',
        ],
    ],

    'backup' => [
        'source_directories' => [
            config('filesystems.disks.public.root'),
            storage_path('app/uploads'),
        ],
    ],

    'single_database_concept' => [
        /**
         * when using single database for all tenants, set which column name is related
         * to identify that this row belongs to which tenant.
         */
        'column' => 'tenant_id',

        /**
         * when using single database for all tenants, set which models apply to tenants
         * so they don't share these data but return per tenant
         */
        'models' => [
//        \App\Models\Product::class,
        ],
    ],

   
    'suffixed_paths' => [
        /**
         * when suffixing the config_keys, what suffix should be added.
         * 
         * Replacements:
         * - %id = $tenant->getId()
         * - %hash = $tenant->getHash()
         * - %name = $tenant->getName()
         * - %slugged-name = Str::slug($tenant->getName())
         * 
         * NOTE: Tenant hash can be customized by overriding the static $computeHash value
         * IbraheemGhazi\OmniTenancy\DTO\DatabaseTenantObject::$computeHash
         * 
         * @see IbraheemGhazi\OmniTenancy\DTO\DatabaseTenantObject
         */
        'suffix_to_add' => 'tenants/%id',

        /**
         * the list of config keys that represent a path, where this path should be suffixed by tenant sub folder that identified
         * by the current tenant, this allows its content to be separated from other tenants
         */
        'config_keys' => [
            'filesystems.disks.local.root' => '%hash',
            'filesystems.disks.public.root' => '%hash',
            'cache.stores.file.path',
            'cache.stores.file.lock_path',
            'session.files',
        ],
    ],

    /**
     * Servable routes, are like alias to real paths that you want to hide its disk path over its public path.
     * 
     * Why that? some paths of your application are tenant related and for that probably its nested inside tenant
     * named specific folder like "suffixed_paths" part of this config file, but you might want to prevent the access
     * directly to that path from another tenant, or want to hide its subfolder tenant specific path.[
     */
    'servable_paths' => [

        /**
         * should enable this feature by default or not.
         * Note: this value is read while booting the application so changing it value later won't take effect.
         */
        'enabled' => true,
        
        /**
         * When registering the route what it's prefix should be like
         * default value: files/ 
         * leading to path: /files/{key}/{path} (example: files/public/folder1/logo.png)
         */
        'route_prefix' => 'files/',

        /**
         * paths that are supported to be servable, where the array key is the "key" part that is public in the path
         * and the array value is the config key that refers to that path.
         * 
         * NOTE: array value is config key not directly the path, thats to be compatible easily with "suffixed_paths".
         * 
         * Personal Note: Not necessary, but i recommend you remove the links that are mapped here from filesystems.php so its not 
         * be accessable in both ways.
         */
        'paths' => [
            'local'     =>  'filesystems.disks.local.root',
            'public'    =>  'filesystems.disks.public.root',
        ]
    ],

    'session' => [
        /**
         * each tenant has its session cookie key, what should its prefix be ?
         */
        'cookie_prefix' => 'sesst_',

        /**
         * each tenant has its session cookie key, what should its suffix be ?
         */
        'cookie_suffix' => '',
    ],

    'identifications' => [
        /**
         * the default fallback tenant id, so when the identifications method failed to recognize
         * which tenant, you can set a default tenant to show instead of showing error page, also
         * this can be used for marketing, like for example:
         * "you are visiting xxx.domain.com, it's free to use sign up now and claim it for yourself."
         */
        'fallback' => [
            'tenant_id' => env('DEFAULT_TENANT_ID'),
        ],

        'methods' => [
            TenantIdentifierBySubdomain::class => [
                'mapping' => [
                    'manager' => 2,
                ],
                'excluded' => ['www', 'api', 'admin', 'manager', 'mail'],
            ],
            TenantIdentifierByDomain::class => [
                'mapping' => [
                    TenantIdentifier::domain() => 1,
                    TenantIdentifier::domain('www') => 1,
                   // TenantIdentifier::domain('manager') => 2,
                ],
                'excluded' => [],

            ],
            TenantIdentifierByHeader::class => [
                'name' => 'X-Tenant-Identifier',
                'mapping' => [],
                'excluded' => [],
            ],
        ],
    ],
];
