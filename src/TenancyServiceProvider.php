<?php

namespace IbraheemGhazi\OmniTenancy;

use IbraheemGhazi\OmniTenancy\Console\Commands\TenantAwareTinkerCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantDatabaseBackupCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantDatabaseRestoreCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantFilesBackupCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantFilesRestoreCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantFullBackupCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantFullRestoreCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantListCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantRunCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantSetMaintenanceModeCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantInitCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantMigrateCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantSeedCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantRouteCacheCommand;
use IbraheemGhazi\OmniTenancy\Console\Commands\TenantRouteClearCommand;
use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\Core\Bootstrappers\AboutCommandTenantContextBootstrapper;
use IbraheemGhazi\OmniTenancy\Core\Bootstrappers\AbstractTenancyBootstrapper;
use IbraheemGhazi\OmniTenancy\Core\Bootstrappers\TenancyConsoleBootstrapper;
use IbraheemGhazi\OmniTenancy\Core\Bootstrappers\TenancySingleDatabaseBootstrapper;
use IbraheemGhazi\OmniTenancy\Core\Bootstrappers\TenancyTenantBootstrapper;
use IbraheemGhazi\OmniTenancy\Core\DatabaseTenantRegistry;
use IbraheemGhazi\OmniTenancy\Core\MultiTenantContext;
use IbraheemGhazi\OmniTenancy\Core\SingleDatabaseTenancy;
use IbraheemGhazi\OmniTenancy\Core\TenantIdentifier;
use IbraheemGhazi\OmniTenancy\Events\TenancyServiceBootedEvent;
use IbraheemGhazi\OmniTenancy\Events\TenancyServiceRegisteredEvent;
use IbraheemGhazi\OmniTenancy\Exceptions\TenantNotActiveException;
use IbraheemGhazi\OmniTenancy\Exceptions\TenantNotFoundException;
use IbraheemGhazi\OmniTenancy\Http\Middlewares\IdentifyTenantMiddleware;
use IbraheemGhazi\OmniTenancy\Http\Middlewares\TenancyMaintenanceModeMiddleware;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class TenancyServiceProvider extends ServiceProvider
{

    /**
     * @var array<class-string<AbstractTenancyBootstrapper>>
     */
    protected static array $bootstrappers = [
        TenancyConsoleBootstrapper::class,
        AboutCommandTenantContextBootstrapper::class,
        TenancyTenantBootstrapper::class,
        TenancySingleDatabaseBootstrapper::class,
    ];

    /**
     * @var array<class-string<AbstractTenancyBootstrapper>>
     */
    public static array $customBootstrappers = [];


    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerPublishes();
        if(!Tenancy::isEnabled()){
            return;
        }
        $this->adjustDatabaseConnections();
        $this->loadHelpers();
        $this->loadMigrations();
        $this->loadConfigs();
        $this->registerCommands();;
        $this->dontReportExceptions();
        $this->registerSingleDatabaseTenancyConcept();
        $this->registerSingletons();
        $this->registerServableFiles();
        

        $this->initTenancy();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if(!Tenancy::isEnabled()){
            return;
        }
        $this->app->booted(fn()=>$this->executeBootstrappers());
    }

    private function adjustDatabaseConnections()
    {
        $centralConnection = config('tenancy.connections.central', config('database.default'));
        config([
            'database.default' => $centralConnection,
        ]);
    }

    private function loadHelpers()
    {
        require_once __DIR__ . '/helpers.php';    
    }

    private function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database');    
    }

    private function loadConfigs()
    {
         $this->mergeConfigFrom(
            __DIR__ . '/../config/tenancy.php', 'tenancy'
        );
    }
    private function registerPublishes()
    {
        $this->publishes([
            __DIR__ . '/../config/tenancy.php' => config_path('tenancy.php')
        ]);
    }

    private function registerCommands(): void
    {
        $this->commands([
            TenantListCommand::class,
            TenantRunCommand::class,
            TenantSetMaintenanceModeCommand::class,
            TenantAwareTinkerCommand::class,
            TenantDatabaseBackupCommand::class,
            TenantDatabaseRestoreCommand::class,
            TenantFilesBackupCommand::class,
            TenantFilesRestoreCommand::class,
            TenantFullBackupCommand::class,
            TenantFullRestoreCommand::class,
            TenantInitCommand::class,
            TenantMigrateCommand::class,
            TenantSeedCommand::class,
            TenantRouteCacheCommand::class,
            TenantRouteClearCommand::class,
        ]);
    }

    private function registerServableFiles()
    {
        if(!config('tenancy.servable_paths.enabled', false)){
            return;
        }
        $prefix = config('tenancy.servable_paths.route_prefix', '');
        if(filled($prefix)){
            $prefix = rtrim($prefix,'/') .'/';
        }
        Route::get($prefix . '{subFolder}/{path}', [Http\Controllers\TenancyFilesController::class, 'serve'])
            ->name('omni-tenancy.servable-files')
            ->where('key', '\d*[xX]\d*')
            ->where('path', '.*');
    }

    private function registerSingletons(): void
    {
        $this->app->singleton(TenantRegistry::class, function () {
            $registryClass = config('tenancy.registry.class', DatabaseTenantRegistry::class);
            if(!in_array(TenantRegistry::class, class_implements($registryClass))){
                throw new RuntimeException('tenancy.registry.class config class must implement ' . TenantRegistry::class);
            }
            return new $registryClass();
        });
        $this->app->singleton(TenantContext::class, function () {
            return new MultiTenantContext();
        });
        $this->app->singleton(AboutCommandTenantContextBootstrapper::class, function () {
            return new AboutCommandTenantContextBootstrapper();
        });
        $this->app->singleton(TenantIdentifier::class, function () {
            return new TenantIdentifier();
        });

    }
    private function executeBootstrappers(): void
    {
        foreach ([...static::$bootstrappers, ...static::$customBootstrappers] as $bootstrapper){
            if(!in_array(AbstractTenancyBootstrapper::class, class_parents($bootstrapper))){
                continue;
            }
            resolve($bootstrapper)->bootstrap();
        }
    }
    private function dontReportExceptions(): void
    {
        $handler = app(ExceptionHandler::class);
        if(!$handler instanceof Handler){
            return;
        }
        $exceptions = new Exceptions($handler);
        foreach([
            TenantNotFoundException::class,
            TenantNotActiveException::class,
        ] as $exception) {
            $exceptions->dontReport($exception);
        }
    }
    private function registerSingleDatabaseTenancyConcept(): void
    {
        SingleDatabaseTenancy::$columnName = config('tenancy.single_database_concept.column');

        $models = config('tenancy.single_database_concept.models', []);
        assert(is_array($models), 'tenancy.single_database_models must be an array');
        foreach ($models as $model) {
            SingleDatabaseTenancy::addModel($model);
        }
    }

    private function initTenancy()
    {
        $this->app->booted(function () {
            $kernel = $this->app->make(Kernel::class);
            // since this is a prepend operation the last prepended MW has the top priority
            $kernel->prependMiddleware(TenancyMaintenanceModeMiddleware::class);
            $kernel->prependMiddleware(IdentifyTenantMiddleware::class);
        });

        Config::set('app.base_url', Config::get('app.url'));

        TenancyServiceRegisteredEvent::dispatch();
    }


}
