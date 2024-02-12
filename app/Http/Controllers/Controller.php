<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Navigation\NavigationItem;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function withBreadcrumbs(NavigationItem ...$item): array
    {
        return array_map(fn (NavigationItem $item) => $item->toArray(), $item);
    }

    public function authorize($ability, $arguments = [])
    {
        [$ability, $arguments] = $this->parseAbilityAndArguments($ability?->value ?? $ability, $arguments);

        return app(Gate::class)->authorize($ability, $arguments);
    }

    protected function backToClient(Request $request, string $level, string $message)
    {
        if ($request->inertia() || ! $request->wantsJson()) {
            session()->flash($level, $message);

            return back();
        }

        return response()->json([
            'level' => $level,
            'message' => $message,
        ]);
    }

    protected function toSuccess(Request $request, string $message)
    {
        return $this->backToClient($request, 'success', $message);
    }
}
