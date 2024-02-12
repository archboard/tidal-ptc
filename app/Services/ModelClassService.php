<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Relations\Relation;

class ModelClassService
{
    public static function toAlias(string $model): string
    {
        return class_exists($model)
            ? (new $model())->getMorphClass()
            : $model;
    }

    public static function toClassName(string $model): string
    {
        $className = class_exists($model)
            ? $model
            : Relation::getMorphedModel($model);

        throw_if(is_null($className), new \Exception("Model class not found for alias: {$model}"));

        return $className;
    }
}
