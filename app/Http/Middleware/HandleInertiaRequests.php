<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Resources\SchoolResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Navigation\NavigationItem;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'layouts.app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     *
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array
     */
    public function share(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();
        $tenant = $request->tenant();
        $school = $request->school();

        return array_merge(parent::share($request), [
            'user' => function () use ($user) {
                if ($user) {
                    return new UserResource($user);
                }

                return new \stdClass();
            },
            'permissions' => fn () => $user && $school
                ? $user->permissions
                : new \stdClass(),
            'school' => fn () => new SchoolResource($school),
            'breadcrumbs' => [],
            'adminSchools' => function () use ($user, $tenant) {
                if (! $user) {
                    return [];
                }

                $schools = $user->isA(Role::DISTRICT_ADMIN->value)
                    ? $tenant->schools()
                        ->active()
                        ->orderBy('name')
                        ->get()
                    : $user->adminSchools()
                        ->get();

                return SchoolResource::collection($schools);
            },
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
            'navigation' => function () use ($user, $request): array {
                if (! $user) {
                    return [];
                }

                $nav = [
                    NavigationItem::make()
                        ->labeled(__('Dashboard'))
                        ->to('/')
                        ->isCurrent($request->is('/'))
                        ->withIcon('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>'),
                ];

                if ($user->hasCachedPermission('student', Permission::viewAny)) {
                    $nav[] = NavigationItem::make()
                        ->labeled(__('Students'))
                        ->to('/students')
                        ->isCurrent($request->routeIs('students.*'))
                        ->withIcon('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" /></svg>');
                }

                if ($user->hasCachedPermission('section', Permission::viewAny)) {
                    $nav[] = NavigationItem::make()
                        ->labeled(__('Sections'))
                        ->to('/sections')
                        ->isCurrent($request->routeIs('sections.*'))
                        ->withIcon('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" /></svg>');
                }

                if ($user->hasCachedPermission('course', Permission::viewAny)) {
                    $nav[] = NavigationItem::make()
                        ->labeled(__('Courses'))
                        ->to('/courses')
                        ->isCurrent($request->routeIs('courses.*'))
                        ->withIcon('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg>');
                }

                if ($user->hasCachedPermission('user', Permission::viewAny)) {
                    $nav[] = NavigationItem::make()
                        ->labeled(__('Users'))
                        ->to('/users')
                        ->isCurrent($request->routeIs('users.*'))
                        ->withIcon('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>');
                }

                return array_map(fn (NavigationItem $item) => $item->toArray(), $nav);
            },
            'secondaryNav' => function () use ($user, $school, $request): array {
                if (! $user) {
                    return [];
                }

                $nav = [
                    NavigationItem::make()
                        ->labeled(__('Personal settings'))
                        ->isCurrent($request->routeIs('settings.personal.edit'))
                        ->to(route('settings.personal.edit')),
                ];

                if ($user->can('edit school settings') && $school) {
                    $nav[] = NavigationItem::make()
                        ->labeled(__('School settings'))
                        ->isCurrent($request->routeIs('settings.school.edit'))
                        ->to(route('settings.school.edit'));
                }

                if ($user->can('edit tenant settings')) {
                    $nav[] = NavigationItem::make()
                        ->labeled(__('Tenant settings'))
                        ->isCurrent($request->routeIs('settings.tenant.edit'))
                        ->to(route('settings.tenant.edit'));
                }

                $nav[] = NavigationItem::make()
                    ->labeled(__('Sign out'))
                    ->to(url('/logout'))
                    ->asButton()
                    ->method('post');

                return array_map(fn (NavigationItem $item) => $item->toArray(), $nav);
            },
            'filterKey' => fn () => config('model-filters.filter_key'),
        ]);
    }
}
