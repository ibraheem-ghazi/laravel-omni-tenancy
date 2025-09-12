<?php

namespace IbraheemGhazi\OmniTenancy\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;

final class SingleDatabaseTenancy
{
    use Macroable;

    private static array $models = [];

    public static string $columnName = 'tenant_id';

    /**
     * @param callable-string<Model> $model
     * @return void
     */
    public static function addModel(string $model): void
    {
        self::$models[] = $model;
        self::$models = array_unique(self::$models);
    }

    public static function setModels(array $models): void
    {
        self::$models = array_unique(array_merge(self::$models, $models));
    }

    public static function clearModels(): void
    {
        self::$models = [];
    }

    public static function getModels(): array
    {
        return self::$models;
    }
}
