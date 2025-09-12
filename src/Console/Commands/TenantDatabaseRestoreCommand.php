<?php

namespace IbraheemGhazi\OmniTenancy\Console\Commands;

use IbraheemGhazi\OmniTenancy\Tenancy;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TenantDatabaseRestoreCommand extends Command
{
    protected $signature = 'tenant:restore:database {backupFile : The path to the backup SQL file}';
    protected $description = 'Restore the database from a backup file to a temp DB, then swap on success';

    public function handle(): int
    {
        if (!($tenant = Tenancy::context()->getCurrentTenant())) {
            $this->error("YOU MUST BE WITHIN TENANT CONTEXT, Run php artisan tenant:run TENANT_ID " . $this->signature);
            return Command::FAILURE;
        }

        $backupFile = $this->argument('backupFile');
        $originalDbName = $tenant->getDatabaseName();
        $tempDbName = $originalDbName . '_temp_' . date('YmdHis');

        try {
            if (!file_exists($backupFile)) {
                $this->error('Backup file not found: ' . $backupFile);
                return Command::FAILURE;
            }

            $tempDbResult = Tenancy::databaseManager()->createDatabase($tempDbName);
            if (!$tempDbResult) {
                $this->error('Failed to create temporary database!');
                return Command::FAILURE;
            }

            $restoreResult = Tenancy::databaseManager()->restoreDatabase($tempDbName, $backupFile);

            if (!$restoreResult) {
                $this->error('Database restore to temp DB failed!');
                Tenancy::databaseManager()->dropDatabase($tempDbName);
                return Command::FAILURE;
            }

            $dropOriginalResult = Tenancy::databaseManager()->dropDatabase($originalDbName);

            if (!$dropOriginalResult) {
                $this->error('Failed to drop original database!');
                Tenancy::databaseManager()->dropDatabase($tempDbName);
                return Command::FAILURE;
            }

            $renameResult = Tenancy::databaseManager()->renameDatabase($tempDbName, $originalDbName);
            if (!$renameResult) {
                $this->error('Failed to rename temp database to original name!');
                return Command::FAILURE;
            }

            $this->info('Database restored successfully from: ' . $backupFile);
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Database restore exception', ['error' => $e->getMessage()]);
            try {
                Tenancy::databaseManager()->dropDatabase($tempDbName);
            } catch (Exception $cleanupException) {
                Log::error('Cleanup failed', ['error' => $cleanupException->getMessage()]);
            }
            return Command::FAILURE;
        }
    }
}
