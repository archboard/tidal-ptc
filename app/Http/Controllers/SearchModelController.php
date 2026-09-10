<?php

namespace App\Http\Controllers;

use App\Models\Contracts\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SearchModelController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $model)
    {
        $modelClass = Str::toModelClass($model);

        $results = $modelClass::query()
            ->when(is_a($modelClass, Filterable::class, true), function (Builder $builder) use ($request, $modelClass) {
                if (is_a($modelClass, Filterable::class, true)) {
                    (new $modelClass)->scopeFilter($builder, $request->all());
                }
            })
            ->with(Arr::wrap($request->input('with', [])))
            ->limit(10)
            ->get();
        $resourceClass = Str::toApiResourceClass($modelClass);

        return $resourceClass::collection($results);
    }
}
