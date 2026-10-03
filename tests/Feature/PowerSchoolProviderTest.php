<?php

use App\Models\School;
use App\SisProviders\PowerSchoolProvider;

function sisSchool(int $id, int $schoolNumber): array
{
    return ['id' => $id, 'name' => "School {$schoolNumber}", 'school_number' => $schoolNumber, 'low_grade' => 0, 'high_grade' => 12];
}

function syncSchoolsFromSis(array $sisSchools): void
{
    $provider = Mockery::mock(PowerSchoolProvider::class, [test()->tenant])->makePartial();
    $provider->shouldReceive('getAllSchools')->andReturn(collect($sisSchools));

    $provider->syncSchools();
}

/** @return array<int, bool> active flag keyed by sis_id */
function activeBySisId(): array
{
    return School::query()->pluck('active', 'sis_id')->map(fn ($active) => (bool) $active)->all();
}

beforeEach(function () {
    $this->asCloud();
    $this->tenant->schools()->delete();
});

it('imports every school on the first cloud sync and activates the lowest school numbers up to the limit', function () {
    $this->tenant->update(['school_limit' => 2]);

    syncSchoolsFromSis([sisSchool(1, 300), sisSchool(2, 100), sisSchool(3, 200)]);

    expect(activeBySisId())->toEqual([1 => false, 2 => true, 3 => true]);
});

it('brings new schools in inactive once the limit is used', function () {
    $this->tenant->update(['school_limit' => 2]);
    syncSchoolsFromSis([sisSchool(1, 100), sisSchool(2, 200)]);

    syncSchoolsFromSis([sisSchool(1, 100), sisSchool(2, 200), sisSchool(3, 50)]);

    expect(activeBySisId())->toEqual([1 => true, 2 => true, 3 => false]);
});

it('keeps the district\'s choice for existing schools on re-sync', function () {
    $this->tenant->update(['school_limit' => 2]);
    syncSchoolsFromSis([sisSchool(1, 100), sisSchool(2, 200)]);
    School::where('sis_id', 1)->update(['active' => false]);

    syncSchoolsFromSis([sisSchool(1, 100), sisSchool(2, 200)]);

    expect(activeBySisId())->toEqual([1 => false, 2 => true]);
});

it('activates every school without a limit', function () {
    syncSchoolsFromSis([sisSchool(1, 100), sisSchool(2, 200), sisSchool(3, 300)]);

    expect(activeBySisId())->toEqual([1 => true, 2 => true, 3 => true]);
});
