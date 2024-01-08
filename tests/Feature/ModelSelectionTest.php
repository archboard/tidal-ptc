<?php

beforeEach(function () {
    logIn()->setSchool();
});

it('can select a model', function () {
    $user = seedUser();

    $this->postJson(route('selection.toggle', $user->getMorphClass()), ['selectable_id' => $user->id])
        ->assertOk()
        ->assertJsonStructure(['level', 'message']);

    $this->assertTrue(
        $this->user->selectedModels()
            ->where('school_id', $this->user->school_id)
            ->where('selectable_type', $user->getMorphClass())
            ->where('selectable_id', $user->id)
            ->exists()
    );
    $this->assertEquals(1, $this->user->selectedModels()->count());
});

it('can deselect a model', function () {
    $user = seedUser();
    $this->user->toggleSelectedModelInstance($user);

    $this->assertTrue(
        $this->user->selectedModels()
            ->where('school_id', $this->user->school_id)
            ->where('selectable_type', $user->getMorphClass())
            ->where('selectable_id', $user->id)
            ->exists()
    );
    $this->assertEquals(1, $this->user->selectedModels()->count());

    $this->postJson(route('selection.toggle', $user->getMorphClass()), ['selectable_id' => $user->id])
        ->assertOk()
        ->assertJsonStructure(['level', 'message']);

    $this->assertTrue(
        $this->user->selectedModels()
            ->where('school_id', $this->user->school_id)
            ->where('selectable_type', $user->getMorphClass())
            ->where('selectable_id', $user->id)
            ->doesntExist()
    );
    $this->assertEquals(0, $this->user->selectedModels()->count());
});

it('can select all models without a filter', function () {
    seedUser();
    seedUser();
    seedUser();
    $otherUser = seedUser();
    $otherUser->update(['school_id' => App\Models\School::factory()
        ->create(['tenant_id' => $this->tenant->id])->id]);

    $this->post(route('selection.toggle', 'user'))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertEquals(
        $this->school->users()->count(),
        $this->user->selectedModels()
            ->where('selectable_type', 'user')
            ->where('school_id', $this->user->school_id)
            ->count()
    );
});

it('can select all models with a filter', function () {
    seedUser(['last_name' => 'McDuck']);
    seedUser(['last_name' => 'McDuck']);
    seedUser();

    $this->assertTrue(
        $this->user->selectedModels()
            ->where('selectable_type', 'user')
            ->where('school_id', $this->user->school_id)
            ->doesntExist()
    );

    $this->post(route('selection.toggle', ['user', 'search' => 'mcduck']))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertEquals(
        $this->school->users()->where('last_name', 'McDuck')->count(),
        $this->user->selectedModels()
            ->where('selectable_type', 'user')
            ->where('school_id', $this->user->school_id)
            ->count()
    );
});

it('can select all models with a filter and previous selection', function () {
    seedUser(['last_name' => 'McDuck']);
    seedUser(['last_name' => 'McDuck']);
    seedUser();
    $this->user->selectAllModel(\App\Models\User::class);

    $this->assertEquals(
        $this->school->users()->count(),
        $this->user->selectedModels()
            ->where('selectable_type', 'user')
            ->where('school_id', $this->user->school_id)
            ->count()
    );

    $this->post(route('selection.toggle', ['user', 'search' => 'mcduck']))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertEquals(
        $this->school->users()->where('last_name', 'McDuck')->count(),
        $this->user->selectedModels()
            ->where('selectable_type', 'user')
            ->where('school_id', $this->user->school_id)
            ->count()
    );
});
