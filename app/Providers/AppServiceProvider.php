<?php

namespace App\Providers;

use App\Enums\UserType;
use App\Models\School;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use GrantHolle\PowerSchool\Auth\UserFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        JsonResource::withoutWrapping();
        Date::use(CarbonImmutable::class);

        $currentTenant = fn (): Tenant => Tenant::current() ?? new Tenant();

        $currentSchool = function (): School {
            /** @var User $user */
            $user = auth()->user();

            if ($user && $school = $user->school) {
                return $school;
            }

            return new School();
        };

        $this->app->bind(Tenant::class, $currentTenant);
        $this->app->bind(School::class, $currentSchool);

        Request::macro('tenant', $currentTenant);
        Request::macro('school', $currentSchool);

        Relation::morphMap([
            'user' => \App\Models\User::class,
            'student' => \App\Models\Student::class,
            'tenant' => \App\Models\Tenant::class,
            'school' => \App\Models\School::class,
            'section' => \App\Models\Section::class,
            'course' => \App\Models\Course::class,
        ]);

        // Add the tenant_id to the identifying attributes when looking up a user
        UserFactory::findUserUsing(function (Collection $data, string $model, array $attributes) {
            $tenant = Tenant::current();
            $userType = UserType::fromData($data);

            $user = $model::firstOrNew([
                ...$attributes,
                'tenant_id' => $tenant->id,
                'sis_key' => $userType->getSisKeyFromData($data),
            ]);
            $user->user_type = $userType;

            return $user;
        });
    }
}
