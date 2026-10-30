<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Contracts\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class SearchModelController extends Controller
{
    /**
     * Model aliases that may be searched.
     *
     * @var array<int, string>
     */
    private const SEARCHABLE = ['user', 'student', 'section', 'course'];

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $model): AnonymousResourceCollection
    {
        abort_unless(in_array($model, self::SEARCHABLE, true), 404);

        $modelClass = Str::toModelClass($model);

        $this->authorize(Permission::viewAny, $modelClass);

        $results = $modelClass::query()
            ->when(is_a($modelClass, Filterable::class, true), function (Builder $builder) use ($request, $modelClass) {
                if (is_a($modelClass, Filterable::class, true)) {
                    (new $modelClass)->scopeFilter($builder, $request->all());
                }
            })
            ->limit(10)
            ->get();
        $resourceClass = Str::toApiResourceClass($modelClass);

        return $resourceClass::collection($results);
    }
}
