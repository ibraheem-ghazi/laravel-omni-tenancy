<?php

namespace IbraheemGhazi\OmniTenancy\Console\Commands;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TenantFullBackupCommand extends Command
{
    protected $signature = 'tenant:backup:full';
    protected $description = 'Backup the database and files';

    public function handle(): int
    {
        if(!Tenancy::context()->getCurrentTenant()){
            $this->error("YOU MUST BE WITHIN TENANT CONTEXT, Run php artisan tenant:run TENANT_ID " . $this->signature);
            return Command::FAILURE;
        }

        $this->call('tenant:backup:database');
        $this->call('tenant:backup:files');
        $this->info('Full backup completed!');
        return Command::SUCCESS;
    }
}
