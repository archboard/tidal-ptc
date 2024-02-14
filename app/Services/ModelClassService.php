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

    public static function toApiResourceClass(string $model): string
    {
        $className = class_basename(static::toClassName($model));
        $namespace = 'App\\Http\\Resources\\'.$className.'Resource';

        throw_unless(class_exists($namespace), new \Exception("Resource class not found for model: {$className}"));

        return $namespace;
    }
}
