<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Jobs\SyncSchools;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Uri;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class InstallationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function removeSisConfig(): static
    {
        $this->tenant->update(['sis_config' => null]);

        return $this;
    }

    protected function fakeLicenseValidation(bool $valid = true): static
    {
        Http::fake([
            'archboard.io/verify/*' => Http::response(compact('valid')),
        ]);

        return $this;
    }

    protected function getPowerSchoolInstallationRequest(array $attributes = []): array
    {
        return [
            'name' => $this->faker->company(),
            'domain' => Uri::of(env('APP_URL'))->host(),
            'sis_config.url' => env('POWERSCHOOL_ADDRESS'),
            'sis_config.client_id' => env('POWERSCHOOL_CLIENT_ID'),
            'sis_config.client_secret' => env('POWERSCHOOL_CLIENT_SECRET'),
            ...$attributes,
        ];
    }

    public function test_cant_access_installation_on_cloud()
    {
        $this->asCloud()
            ->get(route('install'))
            ->assertNotFound();
    }

    public function test_cant_access_installation_when_already_installed()
    {
        $this->get(route('install'))
            ->assertNotFound();
    }

    public function test_cant_access_installation_when_user_has_no_permission()
    {
        $this->logIn()
            ->removeSisConfig()
            ->get(route('install'))
            ->assertNotFound();
    }

    public function test_can_view_installation_page_when_not_installed_and_unauthenticated()
    {
        $this->asSelfHosted()
            ->removeSisConfig()
            ->get('/login')
            ->assertRedirect(route('install'));
    }

    public function test_can_view_installation_page_when_not_installed_and_authenticated()
    {
        $this->asSelfHosted()
            ->logIn()
            ->removeSisConfig()
            ->get('/')
            ->assertRedirect(route('install'));
    }

    public function test_installation_page_unauthenticated()
    {
        $this->removeSisConfig()
            ->asSelfHosted()
            ->get(route('install'))
            ->assertViewHas('title')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('title')
                ->where('form', $this->tenant->getInstallationFields()->toInertia())
                ->where('fields', $this->tenant->getInstallationFields()->toResource())
                ->component('Install')
            );
    }

    public function test_installation_page_authenticated()
    {
        $this->removeSisConfig()
            ->asSelfHosted()
            ->logIn()
            ->tapUser(function (User $user) {
                $user->allow()->everything();
            })
            ->get(route('install'))
            ->assertViewHas('title')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('title')
                ->where('form', $this->tenant->getInstallationFields()->toInertia())
                ->where('fields', $this->tenant->getInstallationFields()->toResource())
                ->component('Install')
            );
    }

    public function test_can_successfully_install_without_existing_tenant()
    {
        Queue::fake();

        $this->tenant->delete();
        Tenant::forgetCurrent();

        $data = $this->getPowerSchoolInstallationRequest();

        $this->fakeLicenseValidation()
            ->asSelfHosted()
            ->post(route('install'), $data)
            ->assertSessionHas('success')
            ->assertRedirect(route('install.user'));

        $this->assertDatabaseHas('tenants', Arr::only($data, ['name', 'domain']));
        $tenant = Tenant::firstWhere('domain', $data['domain']);

        $this->assertEquals($tenant->sis_config->toArray(), Arr::undot(Arr::only($data, ['sis_config.url', 'sis_config.client_secret', 'sis_config.client_id']))['sis_config']);

        Queue::assertPushed(SyncSchools::class);
    }

    public function test_can_successfully_install_with_existing_tenant()
    {
        Queue::fake();

        $data = $this->getPowerSchoolInstallationRequest();

        $this->fakeLicenseValidation()
            ->asSelfHosted()
            ->removeSisConfig()
            ->post(route('install'), $data)
            ->assertSessionHas('success')
            ->assertRedirect(route('install.user'));

        $this->assertDatabaseHas('tenants', Arr::only($data, ['name', 'domain']));
        $tenant = Tenant::firstWhere('domain', $data['domain']);

        $this->assertEquals($tenant->sis_config->toArray(), Arr::undot(Arr::only($data, ['sis_config.url', 'sis_config.client_secret', 'sis_config.client_id']))['sis_config']);

        Queue::assertPushed(SyncSchools::class);
    }

    public function test_cant_view_user_selection_when_uninstalled()
    {
        $this->asSelfHosted()
            ->removeSisConfig()
            ->get(route('install.user'))
            ->assertRedirect(route('install'));
    }

    public function test_cant_view_when_admin_user_already_exists()
    {
        $admin = $this->seedUser();
        $admin->assignRole(Role::DISTRICT_ADMIN);

        $this->asSelfHosted()
            ->get(route('install.user'))
            ->assertSessionHas('error')
            ->assertRedirect();
    }

    public function test_can_view_user_import_page()
    {
        $this->asSelfHosted()
            ->get(route('install.user'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('InstallUser')
                ->where('endpoint', route('install.user'))
            );
    }
}
