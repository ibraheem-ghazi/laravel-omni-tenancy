<?php

namespace IbraheemGhazi\OmniTenancy\Console\Commands;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TenantFullRestoreCommand extends Command
{
    protected $signature = 'tenant:restore:full {backupSql : The path to the backup SQL file} {backupZip : The path to the backup ZIP file}';
    protected $description = 'Restore the database and files from backup files for the current tenant';

    public function handle(): int
    {
        try {
            if (!Tenancy::context()->getCurrentTenant()) {
                $this->error("YOU MUST BE WITHIN TENANT CONTEXT, Run php artisan tenant:run TENANT_ID " . $this->signature);
                return Command::FAILURE;
            }

            $backupSql = $this->argument('backupSql');
            $backupZip = $this->argument('backupZip');

            if (!file_exists($backupSql)) {
                $this->error('Backup SQL file not found: ' . $backupSql);
                return Command::FAILURE;
            }
            if (!file_exists($backupZip)) {
                $this->error('Backup ZIP file not found: ' . $backupZip);
                return Command::FAILURE;
            }

            $this->info('Starting database restore...');
            $dbRestoreResult = $this->call('tenant:restore:database', ['backupFile' => $backupSql]);
            if ($dbRestoreResult !== Command::SUCCESS) {
                $this->error('Database restore failed, aborting full restore!');
                return Command::FAILURE;
            }

            $this->info('Starting file restore...');
            $fileRestoreResult = $this->call('tenant:restore:files', ['backupZip' => $backupZip]);
            if ($fileRestoreResult !== Command::SUCCESS) {
                $this->error('File restore failed, database was restored but files were not!');
                return Command::FAILURE;
            }

            $this->info('Full restore completed successfully! Database restored from: ' . $backupSql . ', Files restored from: ' . $backupZip);
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Full restore exception', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
    }
}
