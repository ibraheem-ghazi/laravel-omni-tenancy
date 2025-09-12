<?php

namespace IbraheemGhazi\OmniTenancy\Console\Commands;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Events\TenantMaintenanceModeChangedEvent;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Foundation\Events\MaintenanceModeEnabled;
use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Support\Str;
use Throwable;

class TenantSetMaintenanceModeCommand extends Command
{
    protected $signature = 'maintenance
                                 {enabled : Set maintenance mode active or not }
                                 {--redirect= : The path that users should be redirected to}
                                 {--render= : The view that should be prerendered for display during maintenance mode}
                                 {--retry= : The number of seconds after which the request may be retried}
                                 {--refresh= : The number of seconds after which the browser may refresh}
                                 {--secret= : The secret phrase that may be used to bypass maintenance mode}
                                 {--with-secret : Generate a random secret phrase that may be used to bypass maintenance mode}
                                 {--status=503 : The status code that should be used when returning the maintenance mode response}';

    protected $description = 'Put the tenant into maintenance / demo mode';

    /**
     * @throws Throwable
     */
    public function handle(): int
    {

        if (!($tenant = Tenancy::context()->getCurrentTenant())) {
            $this->error("YOU MUST BE WITHIN TENANT context, Run php artisan tenant:run TENANT_ID " . $this->signature);
            return Command::FAILURE;
        }

        $tenant->setMaintenanceMode(
            enable: $this->argument('enabled'),
            secret: $this->getSecret(),
            template: $this->option('render') ? $this->prerenderView() : null,
            redirect: $this->redirectPath(),
            status: $this->option('status'),
            retry: $this->getRetryTime(),
            refresh: $this->option('refresh'),
        );

        $payload = $tenant->getMaintenanceModeData();

        TenantMaintenanceModeChangedEvent::dispatch($tenant, $payload);

        $this->laravel->get('events')->dispatch(new MaintenanceModeEnabled());

        if($payload['enabled'])
            $this->components->info('Application is now in maintenance mode.');
        else
            $this->components->info('Application is now live.');


        if ($payload['enabled'] && $payload['secret'] !== null) {
            $this->components->info('You may bypass maintenance mode via ['. url($payload['secret']) . '].');
        }

        return Command::SUCCESS;
    }

    /**
     * Get the path that users should be redirected to.
     *
     * @return string
     */
    protected function redirectPath(): ?string
    {
        if ($this->option('redirect') && $this->option('redirect') !== '/') {
            return '/'.trim($this->option('redirect'), '/');
        }

        return $this->option('redirect');
    }

    /**
     * Prerender the specified view so that it can be rendered even before loading Composer.
     *
     * @return string
     * @throws Throwable
     */
    protected function prerenderView(): string
    {
        (new RegisterErrorViewPaths)();

        return view($this->option('render'), [
            'retryAfter' => $this->option('retry'),
        ])->render();
    }

    /**
     * Get the number of seconds the client should wait before retrying their request.
     *
     * @return int|null
     */
    protected function getRetryTime(): ?int
    {
        $retry = $this->option('retry');

        return is_numeric($retry) && $retry > 0 ? (int) $retry : null;
    }

    /**
     * Get the secret phrase that may be used to bypass maintenance mode.
     *
     * @return string|null
     */
    protected function getSecret(): ?string
    {
        return match (true) {
            ! is_null($this->option('secret')) => $this->option('secret'),
            $this->option('with-secret') => Str::random(32),
            default => null,
        };
    }
}
