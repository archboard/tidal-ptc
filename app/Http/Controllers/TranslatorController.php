<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\TranslatorRequest;
use App\Http\Resources\TranslatorResource;
use App\Models\TimeSlot;
use App\Models\Translator;
use App\Navigation\NavigationItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class TranslatorController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize(Permission::viewAny, TimeSlot::class);

        return inertia('translators/Manage', [
            'title' => __('Translators'),
            'translators' => TranslatorResource::collection(
                Translator::query()
                    ->withCount(['assignments as upcoming_count' => fn ($query) => $query->where('starts_at', '>', now())])
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get()
            ),
            'languages' => $request->school()->languages->map(fn ($language) => ['value' => $language->language->value, 'label' => $language->language->name()])->values(),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()->to(route('translators.index'))->labeled(__('Translator requests')),
                NavigationItem::make()->to(route('translator-profiles.index'))->isCurrent()->labeled(__('Translators')),
            ),
        ]);
    }

    public function store(TranslatorRequest $request): JsonResponse|RedirectResponse
    {
        Translator::create([
            ...$request->validated(),
            'tenant_id' => $request->tenant()->id,
            'school_id' => $request->school()->id,
        ]);

        return $this->toSuccess($request, __('Translator added.'));
    }

    public function update(TranslatorRequest $request, Translator $translatorProfile): JsonResponse|RedirectResponse
    {
        $translatorProfile->update($request->validated());

        return $this->toSuccess($request, __('Translator updated.'));
    }

    public function destroy(Request $request, Translator $translatorProfile): JsonResponse|RedirectResponse
    {
        $this->authorize(Permission::update, TimeSlot::class);
        $translatorProfile->delete();

        return $this->toSuccess($request, __('Translator removed.'));
    }
}
