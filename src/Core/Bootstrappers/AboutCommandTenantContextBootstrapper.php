<?php

namespace IbraheemGhazi\OmniTenancy\Core\Bootstrappers;

use IbraheemGhazi\OmniTenancy\Tenancy;
use Exception;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\App;

class AboutCommandTenantContextBootstrapper extends AbstractTenancyBootstrapper
{
    public function handle(): void
    {
        resolve(TenancyConsoleBootstrapper::class)->bootstrap();


        if (App::runningInConsole()) {
            AboutCommand::add('Tenant Context', fn() => $this->getTenantInfo());
        }
    }

    protected function getTenantInfo(): array
    {
        try {
            $tenantContext = Tenancy::context();
            $currentTenant = $tenantContext->getCurrentTenant();

            if (!$currentTenant) {
                return [
                    'Current Tenant' => '<fg=yellow>No tenant context set</>'
                ];
            }

            return [
                'Tenant ID' => $currentTenant->getId() ?? 'N/A',
                'Tenant Name' => $currentTenant->getName() ?? 'N/A',
                'Tenant Hash' => $currentTenant->getHash() ?? 'N/A',
                'Tenant Database' => $currentTenant->getDatabaseName() ?? 'N/A',
                'Tenant Routes Groups' => json_encode($currentTenant->getGroups(), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?? 'N/A',
                'Tenant Status' => $currentTenant->isActive() ? '<fg=green>Active</>' : '<fg=red>Inactive</>',
            ];

        } catch (Exception $e) {
            return [
                'Current Tenant' => '<fg=red>Error: ' . $e->getMessage() . '</>'
            ];
        }
    }
}
