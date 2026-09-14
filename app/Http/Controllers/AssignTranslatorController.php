<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Enums\Permission;
use App\Models\TimeSlot;
use App\Models\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignTranslatorController extends Controller
{
    public function __invoke(Request $request, TimeSlot $timeSlot): JsonResponse|RedirectResponse
    {
        $this->authorize(Permission::update, $timeSlot);

        $data = $request->validate([
            'translator_id' => ['nullable', Rule::exists('translators', 'id')->where('school_id', $timeSlot->school_id)->whereNull('deleted_at')],
        ]);

        /** @var Translator|null $translator */
        $translator = $data['translator_id'] ? Translator::find($data['translator_id']) : null;

        if ($translator) {
            validator([], [])->after(function (Validator $validator) use ($translator, $timeSlot) {
                if (! $translator->active) {
                    $validator->errors()->add('translator_id', __('This translator is not active.'));
                }
                if (! $translator->speaksLanguage($timeSlot->language)) {
                    $validator->errors()->add('translator_id', __('This translator does not speak the requested language.'));
                }
                if (! $translator->isAvailableFor($timeSlot)) {
                    $validator->errors()->add('translator_id', __('This translator is already assigned to an overlapping conference.'));
                }
            })->validate();
        }

        $previous = $timeSlot->translator;
        $timeSlot->update(['translator_id' => $translator?->id]);

        if ($previous && $previous->isNot($translator)) {
            ActivityEvent::translator_unassigned->log($timeSlot, ['translator_id' => $previous->id, 'translator' => $previous->name, 'language' => $timeSlot->language?->value]);
        }
        if ($translator && ! $previous?->is($translator)) {
            ActivityEvent::translator_assigned->log($timeSlot, ['translator_id' => $translator->id, 'translator' => $translator->name, 'language' => $timeSlot->language?->value]);
        }

        return $this->toSuccess($request, $translator ? __('Translator assigned.') : __('Translator unassigned.'));
    }
}
