<?php

namespace IbraheemGhazi\OmniTenancy\Console\Commands;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class TenantFilesRestoreCommand extends Command
{
    protected $signature = 'tenant:restore:files {backupZip : The path to the backup ZIP file}';
    protected $description = 'Restore application files from a backup ZIP file';

    public function handle(): int
    {
        try {
            if (!Tenancy::context()->getCurrentTenant()) {
                $this->error("YOU MUST BE WITHIN TENANT context, Run php artisan tenant:run TENANT_ID " . $this->signature);
                return Command::FAILURE;
            }

            $backupZip = $this->argument('backupZip');

            $sourceDirs = config('tenancy.backup.source_directories', [
                config('filesystems.disks.public.root'),
                storage_path('app/uploads'),
            ]);

            $tempPath = storage_path('app/backups/temp_restore_' . date('Y-m-d_H-i-s'));

            if (!file_exists($backupZip)) {
                $this->error('Backup ZIP file not found: ' . $backupZip);
                return Command::FAILURE;
            }

            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            $unzipCommand = sprintf('unzip %s -d %s', escapeshellarg($backupZip), escapeshellarg($tempPath));
            exec($unzipCommand, $unzipOutput, $unzipReturnVar);

            if ($unzipReturnVar !== 0) {
                $this->error('Failed to unzip backup file!');
                Log::error('File backup unzip failed', ['output' => $unzipOutput]);
                File::deleteDirectory($tempPath);
                return Command::FAILURE;
            }

            $unzippedDirs = glob($tempPath . '/*', GLOB_ONLYDIR);
            if (empty($unzippedDirs)) {
                $this->error('No directories found in unzipped backup!');
                Log::error('No directories in unzipped backup', ['path' => $tempPath]);
                File::deleteDirectory($tempPath);
                return Command::FAILURE;
            }
            $backupDir = $unzippedDirs[0];

            foreach ($sourceDirs as $originalDir) {
                $baseName = basename($originalDir);
                $tempSource = $backupDir . '/' . $baseName;

                if (File::exists($tempSource)) {
                    if (File::exists($originalDir)) {
                        File::deleteDirectory($originalDir);
                        $this->info('Removed existing directory: ' . $originalDir);
                    }
                    File::copyDirectory($tempSource, $originalDir);
                    $this->info('Restored files from ' . $tempSource . ' to ' . $originalDir);
                } else {
                    $this->warn('Backup directory not found for: ' . $baseName);
                }
            }

            File::deleteDirectory($tempPath);
            $this->info('Files restored successfully from: ' . $backupZip);
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Files restore exception', ['error' => $e->getMessage()]);
            try {
                if (isset($tempPath) && File::exists($tempPath)) {
                    File::deleteDirectory($tempPath);
                }
            } catch (Exception $cleanupException) {
                Log::error('Cleanup failed', ['error' => $cleanupException->getMessage()]);
            }
            return Command::FAILURE;
        }
    }
}
