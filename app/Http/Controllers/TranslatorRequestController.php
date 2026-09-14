<?php

namespace App\Http\Controllers;

use App\Enums\Language;
use App\Enums\Permission;
use App\Http\Resources\TimeSlotResource;
use App\Http\Resources\TranslatorResource;
use App\Models\SchoolLanguage;
use App\Models\TimeSlot;
use App\Models\Translator;
use App\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            'translator_id' => ['nullable', Rule::in(['unassigned', ...Translator::query()->pluck('id')->all()])],
            'export' => ['nullable', 'in:csv'],
        ]);
        $translator = is_numeric($filters['translator_id'] ?? null) ? Translator::find($filters['translator_id']) : null;

        $requests = TimeSlot::query()
            ->where('school_id', $school->id)
            ->reserved()
            ->whereNotNull('language')
            ->when($filters['language'] ?? null, fn (Builder $query, array $languages) => $query->whereIn('language', $languages))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->where('starts_at', '>=', $school->dateToApp($from)))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->where('starts_at', '<', $school->dateToApp($to)->addDay()))
            ->when(($filters['translator_id'] ?? null) === 'unassigned', fn (Builder $query) => $query->whereNull('translator_id'))
            ->when($translator, fn (Builder $query, Translator $translator) => $query->where('translator_id', $translator->id))
            ->with('user', 'student', 'reservedBy', 'translator')
            ->orderBy('starts_at')
            ->get();

        if (($filters['export'] ?? null) === 'csv') {
            return $this->csv($requests, $school->today()->toDateString(), $translator);
        }

        $upcoming = TimeSlot::query()
            ->where('school_id', $school->id)
            ->reserved()
            ->notExpired()
            ->whereNotNull('language')
            ->selectRaw('language, count(*) as used, count(translator_id) as assigned')
            ->groupBy('language')
            ->get()
            ->keyBy(fn (TimeSlot $row) => (string) $row->language?->value);

        return inertia('translators/Index', [
            'title' => __('Translator requests'),
            'requests' => TimeSlotResource::collection($requests),
            'filters' => $filters,
            'translators' => TranslatorResource::collection(Translator::query()->active()->orderBy('name')->get()),
            'capacity' => $school->languages->map(fn (SchoolLanguage $language) => [
                'value' => $language->language->value,
                'label' => $language->language->name(),
                'used' => (int) ($upcoming[$language->language->value]->used ?? 0),
                'assigned' => (int) ($upcoming[$language->language->value]->assigned ?? 0),
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

    /**
     * All assignments, or one translator's schedule when given.
     *
     * @param  Collection<int, TimeSlot>  $requests
     */
    protected function csv($requests, string $date, ?Translator $translator = null): StreamedResponse
    {
        $school = request()->school();
        $filename = $translator
            ? 'translator-schedule-'.Str::slug($translator->name)."-{$date}.csv"
            : "translator-requests-{$date}.csv";

        return response()->streamDownload(function () use ($requests, $school, $translator) {
            $out = fopen('php://output', 'w');
            throw_unless($out, RuntimeException::class, 'Could not open output stream');
            fputcsv($out, ['Date', 'Start', 'End', 'Language', ...($translator ? [] : ['Translator']), 'Staff', 'Student', 'Contact', 'Contact email', 'Where', 'Contact notes', 'Translator notes']);

            foreach ($requests as $slot) {
                $starts = $school->dateFromApp($slot->starts_at);
                fputcsv($out, [
                    $starts->toDateString(),
                    $starts->format('H:i'),
                    $school->dateFromApp($slot->ends_at)->format('H:i'),
                    $slot->language?->name(),
                    ...($translator ? [] : [$slot->translator?->name]),
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
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
