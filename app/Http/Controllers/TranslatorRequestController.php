<?php

namespace App\Http\Controllers;

use App\Enums\Language;
use App\Enums\Permission;
use App\Http\Resources\TimeSlotResource;
use App\Models\SchoolLanguage;
use App\Models\TimeSlot;
use App\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslatorRequestController extends Controller
{
    public function index(Request $request): Response|StreamedResponse
    {
        $this->authorize(Permission::viewAny, TimeSlot::class);
        $school = $request->school();

        $filters = $request->validate([
            'language' => ['nullable', 'array'],
            'language.*' => [Rule::enum(Language::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'export' => ['nullable', 'in:csv'],
        ]);

        $requests = TimeSlot::query()
            ->where('school_id', $school->id)
            ->reserved()
            ->whereNotNull('language')
            ->when($filters['language'] ?? null, fn (Builder $query, array $languages) => $query->whereIn('language', $languages))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->where('starts_at', '>=', $school->dateToApp($from)))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->where('starts_at', '<', $school->dateToApp($to)->addDay()))
            ->with('user', 'student', 'reservedBy')
            ->orderBy('starts_at')
            ->get();

        if (($filters['export'] ?? null) === 'csv') {
            return $this->csv($requests, $school->today()->toDateString());
        }

        $used = TimeSlot::query()
            ->where('school_id', $school->id)
            ->reserved()
            ->notExpired()
            ->whereNotNull('language')
            ->selectRaw('language, count(*) as used')
            ->groupBy('language')
            ->pluck('used', 'language');

        return inertia('translators/Index', [
            'title' => __('Translator requests'),
            'requests' => TimeSlotResource::collection($requests),
            'filters' => $filters,
            'capacity' => $school->languages->map(fn (SchoolLanguage $language) => [
                'value' => $language->language->value,
                'label' => $language->language->name(),
                'used' => (int) ($used[$language->language->value] ?? 0),
                'request_max' => $language->request_max,
                'overlap_max' => $language->overlap_max,
            ])->values(),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->to(route('translators.index'))
                    ->isCurrent()
                    ->labeled(__('Translator requests')),
            ),
        ]);
    }

    /** @param Collection<int, TimeSlot> $requests */
    protected function csv($requests, string $date): StreamedResponse
    {
        $school = request()->school();

        return response()->streamDownload(function () use ($requests, $school) {
            $out = fopen('php://output', 'w');
            throw_unless($out, RuntimeException::class, 'Could not open output stream');
            fputcsv($out, ['Date', 'Start', 'End', 'Language', 'Staff', 'Student', 'Contact', 'Contact email', 'Where', 'Contact notes', 'Translator notes']);

            foreach ($requests as $slot) {
                $starts = $school->dateFromApp($slot->starts_at);
                fputcsv($out, [
                    $starts->toDateString(),
                    $starts->format('H:i'),
                    $school->dateFromApp($slot->ends_at)->format('H:i'),
                    $slot->language?->name(),
                    $slot->user->name,
                    $slot->student?->name,
                    $slot->reservedBy?->name,
                    $slot->reservedBy?->email,
                    $slot->is_online || $slot->requested_online ? 'Online' : $slot->location,
                    $slot->contact_notes,
                    $slot->translator_notes,
                ]);
            }

            fclose($out);
        }, "translator-requests-{$date}.csv", ['Content-Type' => 'text/csv']);
    }
}
