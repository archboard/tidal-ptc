<?php

namespace App\Http\Controllers;

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

    /** @return array<int|string, array<string, mixed>> */
    protected function withBreadcrumbs(NavigationItem ...$item): array
    {
        return array_map(fn (NavigationItem $item) => $item->toArray(), $item);
    }

    public function authorize(\BackedEnum|string $ability, mixed $arguments = []): \Illuminate\Auth\Access\Response
    {
        [$ability, $arguments] = $this->parseAbilityAndArguments(
            $ability instanceof \BackedEnum ? $ability->value : $ability,
            $arguments
        );

        return app(Gate::class)->authorize($ability, $arguments);
    }

    protected function backToClient(Request $request, string $level, string $message): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
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

    protected function toSuccess(Request $request, string $message): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        return $this->backToClient($request, 'success', $message);
    }
}
