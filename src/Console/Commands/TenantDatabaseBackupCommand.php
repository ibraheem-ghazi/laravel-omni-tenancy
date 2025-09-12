<?php

namespace IbraheemGhazi\OmniTenancy\Console\Commands;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Contracts\TenantDatabaseManager;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TenantDatabaseBackupCommand extends Command
{
    protected $signature = 'tenant:backup:database';
    protected $description = 'Backup the database';

    public function handle(): int
    {
        try {

            if(!($tenant = Tenancy::context()->getCurrentTenant())){
                $this->error("YOU MUST BE WITHIN TENANT CONTEXT, Run php artisan tenant:run TENANT_ID " . $this->signature);
                return Command::FAILURE;
            }

            $backupPath = storage_path("app/backups/tenant-{$tenant->getId()}/database");
            $backupFile = $backupPath . '/backup_' . $tenant->getId() . '_' . date('Y-m-d_H-i-s') . '.sql';

            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $result = Tenancy::databaseManager()->backupDatabase($tenant->getDatabaseName(), $backupFile);
            if ($result) {
                $this->info('Database backup created successfully: ' . $backupFile);
            } else {
                $this->error('Database backup failed!');
            }
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Database backup exception', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }

    }
}
