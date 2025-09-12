<?php

namespace IbraheemGhazi\OmniTenancy\Core\Bootstrappers;

use IbraheemGhazi\OmniTenancy\Console\Commands\TenantRunCommand;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

final class TenancyConsoleBootstrapper extends AbstractTenancyBootstrapper
{

    protected static array $forceBypassComands = [
        'tenant:tinker',
        'tenant:migrate',
        'tenant:seed',
        'tenant:route:cache',
        'tenant:route:clear',
        'tenant:event:cache',
        'tenant:event:clear',
        'tenant:backup:full',
        'tenant:backup:files',
        'tenant:backup:database',
        'tenant:restore:full',
        'tenant:restore:files',
        'tenant:restore:database',
    ];


    public function handle(): void
    {
        if (App::runningInConsole()) {
            Event::listen(CommandStarting::class, [$this, 'checkTenantCommand']);
        }
    }

    public function checkTenantCommand(CommandStarting $event): void
    {
        $commandName = $event->command;

        if(in_array($commandName, static::getForceBypassCommands())){
            return;
        }

        if(isset($_SERVER['TENANCY_BYPASS']) && $_SERVER['TENANCY_BYPASS'] === '1'){
            echo "\n  USE \"TENANCY_BYPASS\" WITH CAUTION\n";
            return;
        }

        if ($this->isRestrictedCommand($commandName) && !$this->isRunningThroughTenantCommand()) {
            fwrite(STDERR, "\033[41m\033[37m ERROR \033[0m \033[31mCommand '$commandName' must be run through tenant context.\033[0m\n");
            fwrite(STDERR, "\033[33mUse: php artisan tenant:run {tenant} $commandName\033[0m\n");
            exit(1);
        }

        if(in_array($commandName,['route:cache', 'route:clear'])){
            fwrite(STDERR, "\033[41m\033[37m ERROR \033[0m \033[31mCommand '$commandName' is disabled by the tenancy package.\033[0m\n");
            fwrite(STDERR, "\033[33mUse: php artisan tenant:$commandName {tenant}\033[0m\n");
            fwrite(STDERR, "\033[33m-- More Information can be found at the documentation.\033[0m\n");
            exit(1);
        }
    }

    private function isRestrictedCommand(string $command): bool
    {
        foreach (array_keys(TenantRunCommand::getAllowedCommands()) as $restricted) {
            if (Str::startsWith($command, $restricted)) {
                return true;
            }
        }

        return false;
    }

    private function isRunningThroughTenantCommand(): bool
    {
        return Tenancy::context()->hasTenant();
    }

    public static function getForceBypassCommands(){
        return static::$forceBypassComands;
    }
}
