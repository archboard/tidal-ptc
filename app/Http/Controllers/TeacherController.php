<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Navigation\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $this->authorize(Permission::viewAny, User::class);

        $request->addFilter('teacher', true);

        $filters = $request->currentFilters();
        $teachers = User::query()
            ->filter($filters)
            ->withCount('sections', 'altSections', 'timeSlots')
            ->paginate(25);

        return inertia('teachers/Index', [
            'users' => UserResource::collection($teachers),
            'title' => __('Teachers'),
            'availableFilters' => (new User)->availableFiltersToArray(),
            'currentFilters' => (new User)->activeFiltersToArray($filters),
            'model_alias' => Str::toModelAlias(User::class),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->labeled(__('Teachers'))
                    ->to(route('teachers.index'))
                    ->isCurrent()
            ),
        ]);
    }
}
