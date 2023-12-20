<?php

namespace App\Http\Controllers;

use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Illuminate\Http\Request;

class GetLanguagesController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $languages = Language::query()
            ->orderBy('name')
            ->get();

        return LanguageResource::collection($languages);
    }
}
