<?php

namespace App\Http\Controllers;

use App\Http\Resources\StudentResource;
use App\Models\School;
use App\Models\Student;
use App\Navigation\NavigationItem;
use Illuminate\Http\Request;
use Inertia\Response;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, School $school): Response
    {
        $title = __('Students');
        $filters = $request->currentFilters();
        $students = $school->students()
            ->filter($filters)
            ->orderBy('last_name')
            ->paginate(25);

        return inertia('students/Index', [
            'title' => $title,
            'students' => StudentResource::collection($students),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->labeled(__('Students'))
                    ->to(route('students.index'))
                    ->isCurrent()
            ),
            'availableFilters' => (new Student)->availableFiltersToArray(),
            'currentFilters' => (object) $filters,
        ])->withViewData(compact('title'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student): Response
    {
        $title = $student->name;
        $student->load([
            'school',
            'sections',
            'sections.course',
            'sections.teacher',
            'sections.altTeacher',
        ]);

        return inertia('students/Show', [
            'title' => $title,
            'student' => new StudentResource($student),
            'breadcrumbs' => $this->withBreadcrumbs(
                NavigationItem::make()
                    ->labeled(__('Students'))
                    ->to(route('students.index')),
                NavigationItem::make()
                    ->labeled($student->name)
                    ->to(route('students.show', $student))
                    ->isCurrent(),
            ),
        ])->withViewData(compact('title'));
    }
}
