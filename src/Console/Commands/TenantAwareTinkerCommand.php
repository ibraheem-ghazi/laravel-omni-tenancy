<?php

namespace IbraheemGhazi\OmniTenancy\Console\Commands;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Exception;
use Illuminate\Support\Env;
use Laravel\Tinker\ClassAliasAutoloader;
use Laravel\Tinker\Console\TinkerCommand;
use Psy\Configuration;
use Psy\Shell;
use Psy\VersionUpdater\Checker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TenantAwareTinkerCommand extends TinkerCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'tenant:tinker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interact with your application in tenant context';

    /**
     * Artisan commands to include in the tinker shell.
     *
     * @var array
     */
    protected $commandWhitelist = [
        'clear-compiled', 'down', 'env', 'inspire', 'migrate', 'migrate:install', 'optimize', 'up',
        'about',
        'tenant:backup:full', 'tenant:backup:database', 'tenant:backup:files',
        'tenant:restore:full', 'tenant:restore:database', 'tenant:restore:files'
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
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

            $tenant = $context->getCurrentTenant();
            $this->info("Starting Tinker for tenant: {$tenant->getName()} (ID: {$tenant->getId()})");
            $this->info("Database: {$tenant->getEncryptedOption('db.name', '-')}");
            $this->info("Domain: {$tenant->getMainDomain()}");
            $this->warn("Note: use \$tenant to access current tenant instance");
            $this->line('');


            return $this->createShell([
                'tenant' => $tenant,
                'Tenancy' => Tenancy::class,
            ]);
        } catch (Exception $e) {
            $this->error('Error starting tenant tinker: ' . $e->getMessage());
            return 1;
        }
    }

    public function createShell(array $scopeVars = []): int
    {
        $this->getApplication()->setCatchExceptions(false);

        $config = Configuration::fromInput($this->input);
        $config->setUpdateCheck(Checker::NEVER);

        $config->getPresenter()->addCasters(
            $this->getCasters()
        );

        if ($this->option('execute')) {
            $config->setRawOutput(true);
        }

        $shell = new Shell($config);
        $shell->addCommands($this->getCommands());
        $shell->setIncludes($this->argument('include'));

        $shell->setScopeVariables($scopeVars);

        $path = Env::get('COMPOSER_VENDOR_DIR', $this->getLaravel()->basePath().DIRECTORY_SEPARATOR.'vendor');

        $path .= '/composer/autoload_classmap.php';

        $config = $this->getLaravel()->make('config');

        $loader = ClassAliasAutoloader::register(
            $shell, $path, $config->get('tinker.alias', []), $config->get('tinker.dont_alias', [])
        );

        if ($code = $this->option('execute')) {
            try {
                $shell->setOutput($this->output);
                $shell->execute($code);
            } finally {
                $loader->unregister();
            }

            return 0;
        }

        try {
            return $shell->run();
        } finally {
            $loader->unregister();
        }
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
            ['include', InputArgument::IS_ARRAY, 'Include file(s) before starting tinker'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['execute', null, InputOption::VALUE_OPTIONAL, 'Execute the given code using Tinker'],
        ];
    }
}
