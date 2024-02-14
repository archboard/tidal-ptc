<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\School;
use App\Navigation\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, School $school)
    {
        $title = __('Courses');
        $courses = $school->courses()
            ->filter($request->currentFilters())
            ->withCount('sections')
            ->orderBy('name')
            ->paginate(25);

        return inertia('courses/Index', [
            'title' => $title,
            'courses' => CourseResource::collection($courses),
            'model_alias' => Str::toModelAlias(Course::class),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->labeled(__('Courses'))
                    ->to(route('courses.index'))
                    ->isCurrent()
            ),
        ])->withViewData(compact('title'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        $course->load([
            'sections',
            'sections.students',
            'sections.teacher',
            'sections.altTeacher',
        ]);
        $title = $course->name;

        return inertia('courses/Show', [
            'title' => $title,
            'course' => new CourseResource($course),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->labeled(__('Courses'))
                    ->to(route('courses.index')),
                NavigationItem::make()
                    ->labeled($course->name)
                    ->to(route('courses.show', $course))
                    ->isCurrent(),
            ),
        ])->withViewData(compact('title'));
    }
}
