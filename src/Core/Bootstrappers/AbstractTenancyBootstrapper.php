<?php

namespace IbraheemGhazi\OmniTenancy\Core\Bootstrappers;

use IbraheemGhazi\OmniTenancy\Contracts\TenancyBootstrapper;

abstract class AbstractTenancyBootstrapper implements TenancyBootstrapper
{
    protected bool $once = true;
    private static array $_bootstrapped = [];
    public final function bootstrap(bool $force = false): void
    {
        if(!$force && $this->once && $this->hasBootstrapped()){
            return;
        }
        try{
            $this->handle();
        } finally {
            $this->markAsBootstrapped();
        }
    }
    abstract protected function handle(): void;

    private function hasBootstrapped(): bool
    {
        return in_array(static::class, static::$_bootstrapped);
    }

    private function markAsBootstrapped(): void
    {
        static::$_bootstrapped[] = static::class;
    }
}
