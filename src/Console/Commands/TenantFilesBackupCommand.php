<?php

namespace IbraheemGhazi\OmniTenancy\Console\Commands;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class TenantFilesBackupCommand extends Command
{

    protected $signature = 'tenant:backup:files';
    protected $description = 'Backup application files';

    public function handle(): int
    {
        try {

            if(!$tenant = Tenancy::context()->getCurrentTenant()){
                $this->error("YOU MUST BE WITHIN TENANT CONTEXT, Run php artisan tenant:run TENANT_ID " . $this->signature);
                return Command::FAILURE;
            }

            $sourceDirs = config('tenancy.backup.source_directories', [
                config('filesystems.disks.public.root'),
                storage_path('app/uploads'),
            ]);

            assert(is_array($sourceDirs), 'tenancy.backup.source_directories must be array');

            $backupPath = storage_path("app/backups/tenant-{$tenant->getId()}/assets");
            $backupDir = $backupPath . '/assets_' . date('Y-m-d_H-i-s');

            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            mkdir($backupDir, 0755, true);

            foreach ($sourceDirs as $source) {
                if (File::exists($source)) {
                    $destination = $backupDir . '/' . basename($source);
                    File::copyDirectory($source, $destination);
                    $this->info('Copied assets from ' . $source . ' to ' . $destination);
                } else {
                    $this->warn('Source directory not found: ' . $source);
                }
            }

            $zipFile = $backupPath . '/assets_' . $tenant->getId() . '_' . date('Y-m-d_H-i-s') . '.zip';
            $zipCommand = sprintf('zip -r %s %s', escapeshellarg($zipFile), escapeshellarg($backupDir));
            exec($zipCommand, $output, $returnVar);

            if ($returnVar === 0) {
                $this->info('Assets backup zipped successfully: ' . $zipFile);
                File::deleteDirectory($backupDir);
            } else {
                $this->error('Asset backup zipping failed!');
                Log::error('Asset backup zipping failed', ['output' => $output]);
            }
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Assets backup exception', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
    }
}
