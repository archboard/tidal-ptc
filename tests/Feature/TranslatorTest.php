<?php

use App\Enums\Language;
use App\Enums\Permission;
use App\Enums\UserType;
use App\Models\Activity;
use App\Models\Student;
use App\Models\TimeSlot;
use App\Models\Translator;

beforeEach(function () {
    logIn()->setSchool();
    $this->school->update(['allow_translator_requests' => true]);
    $this->school->languages()->create(['language' => Language::JAPANESE, 'request_max' => 0, 'overlap_max' => 0]);
    $this->school->languages()->create(['language' => Language::KOREAN, 'request_max' => 0, 'overlap_max' => 0]);

    $this->translator = Translator::factory()->create(['languages' => [Language::JAPANESE, Language::KOREAN]]);
    $this->slot = seedTimeSlot(['student_id' => Student::factory()->create()->id, 'language' => Language::JAPANESE]);
});

function assign(TimeSlot $slot, ?Translator $translator)
{
    return test()->putJson(route('time-slots.translator', $slot), ['translator_id' => $translator?->id]);
}

it('requires update permission to manage translators', function () {
    $this->get(route('translator-profiles.index'))->assertForbidden();
    $this->postJson(route('translator-profiles.store'), [])->assertForbidden();
    assign($this->slot, $this->translator)->assertForbidden();
});

it('creates, updates and removes translators', function () {
    $this->givePermission(Permission::update, TimeSlot::class);

    $this->postJson(route('translator-profiles.store'), [
        'first_name' => 'Yuki', 'last_name' => 'Sato', 'email' => 'yuki@example.com', 'languages' => ['ja'],
    ])->assertOk();

    $created = Translator::where('last_name', 'Sato')->sole();
    expect($created->languages->all())->toEqual([Language::JAPANESE])
        ->and($created->school_id)->toBe($this->school->id);

    $this->putJson(route('translator-profiles.update', $created), [
        'first_name' => 'Yuki', 'last_name' => 'Sato', 'languages' => ['ja', 'ko'], 'active' => false,
    ])->assertOk();
    expect($created->refresh()->active)->toBeFalse()
        ->and($created->languages)->toHaveCount(2);

    $this->deleteJson(route('translator-profiles.destroy', $created))->assertOk();
    expect(Translator::find($created->id))->toBeNull();
});

it('rejects languages the school does not offer', function () {
    $this->givePermission(Permission::update, TimeSlot::class)
        ->postJson(route('translator-profiles.store'), ['first_name' => 'A', 'last_name' => 'B', 'languages' => ['ar']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('languages.0');
});

it('assigns and unassigns a translator with activity logging', function () {
    $this->givePermission(Permission::update, TimeSlot::class);

    assign($this->slot, $this->translator)->assertOk();
    expect($this->slot->refresh()->translator_id)->toBe($this->translator->id)
        ->and(Activity::where('event', 'translator_assigned')->where('subject_id', $this->slot->id)->exists())->toBeTrue();

    assign($this->slot, null)->assertOk();
    expect($this->slot->refresh()->translator_id)->toBeNull()
        ->and(Activity::where('event', 'translator_unassigned')->exists())->toBeTrue();
});

it('rejects invalid assignments', function (string $case) {
    $this->givePermission(Permission::update, TimeSlot::class);

    $translator = match ($case) {
        'wrong language' => Translator::factory()->create(['languages' => [Language::KOREAN]]),
        'inactive' => Translator::factory()->create(['active' => false]),
        'other school' => Translator::factory()->create(['school_id' => $this->tenant->schools->firstWhere('id', '!=', $this->school->id)->id]),
        'overlapping' => tap($this->translator, fn ($t) => seedTimeSlot([
            'student_id' => Student::factory()->create()->id,
            'language' => Language::JAPANESE,
            'translator_id' => $t->id,
            'starts_at' => $this->slot->starts_at->addMinutes(5),
            'ends_at' => $this->slot->ends_at->addMinutes(5),
        ])),
    };

    assign($this->slot, $translator)->assertUnprocessable()->assertJsonValidationErrors('translator_id');
    expect($this->slot->refresh()->translator_id)->toBeNull();
})->with(['wrong language', 'inactive', 'other school', 'overlapping']);

it('allows reassigning the same translator to the same slot', function () {
    $this->slot->update(['translator_id' => $this->translator->id]);

    $this->givePermission(Permission::update, TimeSlot::class);
    assign($this->slot, $this->translator)->assertOk();
    expect(Activity::where('event', 'translator_assigned')->count())->toBe(0);
});

it('clears the translator when a reservation is cancelled and does not carry it on reschedule', function () {
    $guardian = seedUser(['user_type' => UserType::guardian]);
    $this->slot->update(['reserved_by' => $guardian->id, 'translator_id' => $this->translator->id]);
    $guardian->students()->attach($this->slot->student_id);
    $target = seedTimeSlot(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addMinutes(15), 'allow_translator_requests' => true]);

    $this->givePermission(Permission::ownTimeSlots);
    $this->givePermission(Permission::update, TimeSlot::class)
        ->putJson(route('reservations.update', $this->slot), ['time_slot_id' => $target->id, 'language' => 'ja'])
        ->assertOk();

    expect($this->slot->refresh()->translator_id)->toBeNull()
        ->and($target->refresh()->translator_id)->toBeNull()
        ->and($target->language)->toBe(Language::JAPANESE);

    $target->update(['translator_id' => $this->translator->id]);
    $this->deleteJson(route('reservations.destroy', $target))->assertOk();
    expect($target->refresh()->translator_id)->toBeNull();
});
