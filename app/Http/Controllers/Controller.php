<?php

namespace App\Http\Controllers;

use App\Navigation\NavigationItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function withBreadcrumbs(NavigationItem ...$item): array
    {
        return array_map(fn (NavigationItem $item) => $item->toArray(), $item);
    }
}
