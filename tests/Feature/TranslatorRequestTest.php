<?php

use App\Enums\Language;
use App\Enums\Permission;
use App\Enums\UserType;
use App\Models\Student;
use App\Models\TimeSlot;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn()->setSchool();
    $this->school->update(['allow_translator_requests' => true, 'timezone' => 'Asia/Shanghai']);
    $this->school->languages()->create(['language' => Language::JAPANESE, 'request_max' => 3, 'overlap_max' => 1]);
    $this->school->languages()->create(['language' => Language::KOREAN, 'request_max' => 0, 'overlap_max' => 0]);

    $this->guardian = seedUser(['user_type' => UserType::guardian]);
    $this->japanese = seedTimeSlot(['student_id' => Student::factory()->create()->id, 'reserved_by' => $this->guardian->id, 'language' => Language::JAPANESE, 'translator_notes' => 'Bring forms']);
    $this->korean = seedTimeSlot(['student_id' => Student::factory()->create()->id, 'reserved_by' => $this->guardian->id, 'language' => Language::KOREAN, 'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addMinutes(15)]);
    seedTimeSlot(['student_id' => Student::factory()->create()->id]);
    seedTimeSlot(['language' => Language::JAPANESE]);
    TimeSlot::factory()->create(['school_id' => $this->tenant->schools->firstWhere('id', '!=', $this->school->id)->id, 'user_id' => $this->user->id, 'student_id' => Student::factory()->create()->id, 'language' => Language::JAPANESE]);
});

it('requires permission', function () {
    $this->get(route('translators.index'))->assertForbidden();
});

it('lists reserved slots with a language in the current school', function () {
    $this->givePermission(Permission::viewAny, TimeSlot::class)
        ->get(route('translators.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('translators/Index')
            ->has('requests', 2)
            ->where('requests.0.id', $this->japanese->id)
            ->where('requests.0.translator_notes', 'Bring forms')
            ->where('requests.0.reserved_by.email', $this->guardian->email)
            ->where('capacity.0.used', 1)
            ->where('capacity.0.request_max', 3));
});

it('filters by language and date', function () {
    $this->givePermission(Permission::viewAny, TimeSlot::class)
        ->get(route('translators.index', ['language' => ['ko']]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('requests', 1)->where('requests.0.id', $this->korean->id));

    $this->get(route('translators.index', ['from' => now()->addDays(2)->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('requests', 1)->where('requests.0.id', $this->korean->id));
});

it('exports a csv', function () {
    $response = $this->givePermission(Permission::viewAny, TimeSlot::class)
        ->get(route('translators.index', ['export' => 'csv', 'language' => ['ja']]));

    $response->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=translator-requests-'.$this->school->today()->toDateString().'.csv');

    $rows = array_map('str_getcsv', array_filter(explode("\n", $response->streamedContent())));
    expect($rows)->toHaveCount(2)
        ->and($rows[0][0])->toBe('Date')
        ->and($rows[1][3])->toBe('Japanese')
        ->and($rows[1][8])->toBe($this->guardian->email)
        ->and($rows[1][11])->toBe('Bring forms');
});

it('lets staff save translator notes on a reserved slot', function () {
    $this->putJson(route('time-slots.update', $this->japanese), makeTimeSlotRequest([
        'starts_at' => $this->japanese->starts_at->toIso8601ZuluString(),
        'ends_at' => $this->japanese->ends_at->toIso8601ZuluString(),
        'translator_notes' => 'Assigned: Ms. Sato',
    ]))->assertOk();

    expect($this->japanese->refresh()->translator_notes)->toBe('Assigned: Ms. Sato');
});
