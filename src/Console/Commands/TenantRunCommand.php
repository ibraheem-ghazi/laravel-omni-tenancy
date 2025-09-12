<?php

namespace IbraheemGhazi\OmniTenancy\Console\Commands;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use IbraheemGhazi\OmniTenancy\Contracts\TenantRegistry;
use IbraheemGhazi\OmniTenancy\Core\Bootstrappers\TenancyConsoleBootstrapper;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TenantRunCommand extends Command
{
    protected $signature = 'tenant:run
                            {tenant : Tenant ID or "all" for all tenants}
                            {cmd : Artisan command name}
                            {cmdArgs?* : Arguments for the command}
                            {--yes : Skip confirmation when use "all" as tenant id}
                            {--dry-run : Preview without execution}
                            {--skip-errors : Continue on errors in batch mode}
                            {--limit= : Limit number of tenants in batch mode}
                            {--chunk=500 : Process tenants in chunks}
                            {--active-only : Only process active tenants}
                            {--cmd-verbose : Call command silently}';

    protected $description = 'Execute Artisan commands in tenant context';

    public function handle(): int
    {
        /**
         * this allows to return back to tenant it was active before running the command, as without it we got 
         * issues when running the command using Artisan::call inside php.
         */
        $prevTenant = Tenancy::context()->getCurrentTenant();

        try{
            $tenantId = $this->argument('tenant');
            if (!$this->validateTenant($tenantId)) {
                return self::FAILURE;
            }

            $isAutoConfirmed = $this->option('yes');
            if ($tenantId === 'all' && !$isAutoConfirmed && !$this->confirm('Run command for ALL tenants?')) {
                return self::FAILURE;
            }


            $command = $this->argument('cmd');
            if(strtolower(trim($command)) === 'tinker'){
                $command = 'tenant:tinker';
            }
            if (!$this->isCommandAllowed($command)) {
                $this->error("Command [$command] is not allowed in tenant context");
                return self::INVALID;
            }

            $args = $this->getCommandArgs();
            if ($this->option('dry-run')) {
                $this->info("[DRY RUN] Would execute: $command for tenant: $tenantId");
                $this->line('Arguments: '.json_encode($args));
                return self::SUCCESS;
            }

            if ($tenantId === 'all') {
                return $this->executeForAllTenants($command, $args);
            }

            if(str_contains($tenantId, ',')){
                $tenants = explode(',', $tenantId);
                $result = self::SUCCESS;
                foreach($tenants as $id)
                {
                    $loopResult = $this->executeForSingleTenant($id, $command, $args);
                    if($loopResult !== self::SUCCESS) $result = self::FAILURE;
                }
                return $result;
            }

            return $this->executeForSingleTenant($tenantId, $command, $args);
        } finally {
            if($prevTenant)
                Tenancy::context()->setTenant($prevTenant);
        }
    }

    protected function getCommandArgumentNames(string $command): array
    {
        try {
            $commandInstance = $this->getApplication()->find($command);
            $definition = $commandInstance->getDefinition();
            $argumentNames = [];

            foreach ($definition->getArguments() as $argument) {
                $argumentNames[] = $argument->getName();
            }

            return $argumentNames;
        } catch (\Exception $e) {
            $this->warn("Could not retrieve argument names for command '$command': {$e->getMessage()}");
            return [];
        }
    }

    protected function getCommandArgs(): array
    {
        $args = $this->argument('cmdArgs') ?? [];
        $command = $this->argument('cmd');
        if(strtolower(trim($command)) === 'tinker'){
            $command = 'tenant:tinker';
        }
        $argsNames = $this->getCommandArgumentNames($command);

        $params = [
            'args' => [],
            'options' => [],
        ];

        foreach($args as $key => $inputArg)
        {
            if(str_starts_with($inputArg, '+')){
                $inputArg = '--' . substr($inputArg, 1);
                $parts = explode('=', $inputArg, 2);
                $arg = data_get($parts, 0);
                $value = data_get($parts, 1, true);

                // if was set before, meaning we pass more than one of this option, therefore it should be passed as array in call()
                if(isset($params['options'][$arg])){
                    $params['options'][$arg] = Arr::wrap($params['options'][$arg]);
                        $params['options'][$arg][] = $value;
                }else{
                    $params['options'][$arg] = $value;
                }

                continue;
            }

            if(isset($argsNames[$key])){
                $value = json_decode($inputArg, true) ?? $inputArg;

                $argName = $argsNames[$key];
                
                // if was set before, meaning we pass more than one of this argument, therefore it should be passed as array in call()
                if(isset($params['args'][$argName])){
                    $params['args'][$argName] = Arr::wrap($params['args'][$argName]);
                        $params['args'][$argName][] = $value;
                }else{
                    $params['args'][$argName] = $value;
                }
            }
        }

        return [...$params['args'], ...$params['options']];
    }

    protected function executeForSingleTenant(
        string $tenantId,
        string $command,
        array $args
    ): int {
        Tenancy::context()->setTenant($tenantId);
        $name = Tenancy::context()->getCurrentTenant()->getName();
        $dbName = Tenancy::context()->getCurrentTenant()->getDatabaseName();
        if($dbName) $dbName = "[$dbName]";
        $caption = implode(' - ', array_filter([
            $tenantId,
            $name,
            $dbName
        ], 'filled'));
        $this->info("\n<fg=blue>Executing for tenant:</> <fg=white;bg=blue>$caption</>");

        $start = microtime(true);

        if ($command === 'tinker') {
            $result = $this->call('tenant:tinker', $args);
        } else {
            $result = $this->call($command, $args);
        }

        $this->info("Duration: ".round(microtime(true)-$start, 2)."s");

        if ($command !== 'tinker') {
            Tenancy::context()->reset();
        }

        return $result;
    }

    protected function executeForAllTenants(
        string $command,
        array $args
    ): int {
        $chunkSize = (int)$this->option('chunk');
        $limit = $this->option('limit') ? (int)$this->option('limit') : null;
        $activeOnly = $this->option('active-only');

        $registry = Tenancy::registry();
        $success = 0;
        $processed = 0;

        $allTenants = Tenancy::registry()->getAllTenants();
        if ($activeOnly) {
            $allTenants = $allTenants->filter(fn($tenant) => $tenant->isActive());
        }
        $total = $limit ?? $allTenants->count();

        $this->info("\n<fg=yellow>Executing for up to $total tenants:</>");
        $progress = $this->output->createProgressBar($total);

        $registry->forEachTenant($chunkSize, function ($tenant) use ($command, $args, &$success, &$processed, $progress, $limit) {
            if ($limit && $processed >= $limit) {
                return;
            }

            try {
                Tenancy::context()->setTenant($tenant->getId());
                if($this->option('cmd-verbose')){
                    $this->info("Executing for {$tenant->getId()} - {$tenant->getName()}");
                    $this->call($command, $args);
                }else{
                    $this->callSilent($command, $args);
                }
                $success++;
            } catch (\Exception $e) {
                if (!$this->option('skip-errors')) throw $e;
                $this->newLine();
                $this->error("Failed for {$tenant->getId()}: {$e->getMessage()}");
            } finally {
                Tenancy::context()->reset();
                $progress->advance();
                $processed++;
            }
        }, $activeOnly);

        $progress->finish();
        $this->newLine(2);
        $this->info("Completed: $success/$processed tenants succeeded");

        return $success === $processed ? self::SUCCESS : self::FAILURE;
    }

    protected function fetchTenantsWithProgress(TenantRegistry $registry, bool $activeOnly = false): Collection
    {
        $this->info('Loading tenants...');
        $progress = $this->output->createProgressBar();
        $progress->start();

        $tenants = $registry->getAllTenantsWithProgress(
            fn() => $progress->advance(),
            $activeOnly
        );

        $progress->finish();
        $this->newLine();

        return $tenants;
    }

    public static function getAllowedCommands(): array
    {
        return config('tenancy.console.allowed_commands', []);
    }

    protected function isCommandAllowed(string $command): bool
    {
        $allowed = array_keys($this->getAllowedCommands());
        $allowed = array_merge($allowed, TenancyConsoleBootstrapper::getForceBypassCommands());

        foreach ($allowed as $allowedCmd) {
            if (Str::startsWith($command, $allowedCmd)) {
                return true;
            }
        }

        return in_array($command, $allowed);
    }

    protected function validateTenant(string $tenantId): bool
    {
        if ($tenantId === 'all') return true;

        if(str_contains($tenantId, ',')){
            $tenants = explode(',', $tenantId);
            if(count($tenants) > 1){
                $failed = [];
                foreach($tenants as $tenant)
                {
                    if(!Tenancy::registry()->exists($tenant)){
                        $failed[] = $tenant;
                    }
                }
                if(!empty($failed)){
                    $this->error("Tenants \"". implode('", "', $failed) ."\" not found");
                    return false;
                }
                return true;
            }
        }

        if (!Tenancy::registry()->exists($tenantId)) {
            $this->error("Tenant $tenantId not found");
            return false;
        }

        if ($this->option('active-only') && !Tenancy::registry()->isActive($tenantId)) {
            $this->error("Tenant $tenantId is inactive");
            return false;
        }

        return true;
    }
}
