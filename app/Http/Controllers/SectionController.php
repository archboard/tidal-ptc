<?php

namespace App\Http\Controllers;

use App\Http\Resources\SectionResource;
use App\Models\School;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, School $school)
    {
        $sections = $school->sections()
            ->filter($request->all())
            ->with('course')
            ->withCount('students')
            ->paginate(25);
        $title = __('Sections');

        return inertia('sections/Index', [
            'title' => $title,
            'sections' => SectionResource::collection($sections),
        ])->withViewData(compact('title'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Section $section)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
