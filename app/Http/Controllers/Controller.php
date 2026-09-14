<?php

namespace App\Http\Controllers;

use App\Navigation\NavigationItem;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

    public function authorize(\BackedEnum|string $ability, mixed $arguments = []): Response
    {
        [$ability, $arguments] = $this->parseAbilityAndArguments(
            $ability instanceof \BackedEnum ? $ability->value : $ability,
            $arguments
        );

        return app(Gate::class)->authorize($ability, $arguments);
    }

    protected function backToClient(Request $request, string $level, string $message): JsonResponse|RedirectResponse
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

    protected function toSuccess(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $this->backToClient($request, 'success', $message);
    }
}
