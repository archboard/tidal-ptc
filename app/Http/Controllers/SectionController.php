<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\SectionResource;
use App\Models\School;
use App\Models\Section;
use App\Navigation\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, School $school)
    {
        $this->authorize(Permission::viewAny, Section::class);

        $sections = $school->sections()
            ->filter($request->currentFilters())
            ->with('course', 'teacher', 'altTeacher')
            ->withCount('students')
            ->join('courses', 'courses.id', '=', 'sections.course_id')
            ->orderBy('courses.name')
            ->paginate(25);
        $title = __('Sections');

        return inertia('sections/Index', [
            'title' => $title,
            'sections' => SectionResource::collection($sections),
            'model_alias' => Str::toModelAlias(Section::class),
        ])->withViewData(compact('title'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Section $section)
    {
        $this->authorize(Permission::view, $section);

        $section->load('course', 'teacher', 'altTeacher', 'students');
        $title = __('Section :number of :course_name', [
            'number' => $section->section_number,
            'course_name' => $section->course->name,
        ]);

        return inertia('sections/Show', [
            'title' => $title,
            'section' => new SectionResource($section),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->to(route('sections.index'))
                    ->labeled(__('Sections')),
                NavigationItem::make()
                    ->to(route('sections.show', $section))
                    ->labeled($section->display)
                    ->isCurrent(),
            ),
        ])->withViewData(compact('title'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Section $section)
    {
        $this->authorize(Permission::update, $section);

        $title = __('Edit Section :number of :course_name', [
            'number' => $section->section_number,
            'course_name' => $section->course->name,
        ]);

        return inertia('sections/Edit', [
            'title' => $title,
            'section' => new SectionResource($section),
            'endpoint' => route('sections.update', $section),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->to(route('sections.index'))
                    ->labeled(__('Sections')),
                NavigationItem::make()
                    ->to(route('sections.show', $section))
                    ->labeled($section->display),
                NavigationItem::make()
                    ->to(route('sections.edit', $section))
                    ->labeled(__('Edit'))
                    ->isCurrent(),
            ),
        ])->withViewData(compact('title'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Section $section)
    {
        $this->authorize(Permission::update, $section);

        $data = $request->validate([
            'alt_user_id' => ['nullable', Rule::exists('users', 'id')->where('school_id', $section->school_id)],
            'can_book' => ['nullable', 'boolean'],
        ]);

        $section->update($data);

        return $this->toSuccess($request, __('Section updated successfully.'));
    }
}
