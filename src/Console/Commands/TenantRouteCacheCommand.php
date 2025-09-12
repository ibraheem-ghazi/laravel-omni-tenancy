<?php

namespace IbraheemGhazi\OmniTenancy\Console\Commands;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\Core\TenancyRouter;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Filesystem\Filesystem;

class TenantRouteCacheCommand extends Command
{
    protected $name = 'tenant:route:cache';

    protected $description = 'Create a tenancy related route cache file for faster route registration';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

     /**
     * Create a new route command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): int
    {
         if (!app()->bound(TenantContext::class)) {
            $this->error('TenantContext is not bound. This command should be run through tenant:run.');
            return 1;
        }

        $context = Tenancy::context();

        if (!$context->hasTenant()) {
            if(!$this->argument('tenantId')){
                $this->error('No tenant context active. This command should be run through tenant:run or at least pass tenentId argument.');
                return 1;
            }
            $context->setTenant($this->argument('tenantId'));
        }

        $this->cacheRoutes($context->getCurrentTenant());
        
        return 0;
    }

    protected function cacheRoutes(TenantObject $tenant)
    {
        $routes = tap(app('router')->getRoutes(), function ($routes) {
                    $routes->refreshNameLookups();
                    $routes->refreshActionLookups();
        });
        if (count($routes) === 0) {
            return $this->components->error("Your application doesn't have any routes.");
        }
        foreach ($routes as $route) {
            $route->prepareForSerialization();
        }
       
        $stub = "<?php\n\napp('router')->setCompiledRoutes({{routes}});";

        $output = str_replace('{{routes}}', var_export($routes->compile(), true), $stub);

        $dir_path = dirname($this->laravel->getCachedRoutesPath());
        if(!is_dir($dir_path)){
            mkdir($dir_path, 0755, true);
        }

        $this->files->put(
            $this->laravel->getCachedRoutesPath(), $output
        );

        $this->components->info("Routes cached successfully for tenant [{$tenant->getId()} - {$tenant->getName()}].");

    }

     /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['tenantId', InputArgument::OPTIONAL, 'the tenant ID, you pass it or run command through tenant:run'],
        ];
    }
}
