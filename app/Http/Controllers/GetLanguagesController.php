<?php

namespace App\Http\Controllers;

use App\Enums\Language;
use App\Http\Resources\LanguageResource;
use Illuminate\Http\Request;

class GetLanguagesController extends Controller
{
    public function __invoke(Request $request)
    {
        $languages = Language::collect()->sortBy('name');

        return LanguageResource::collection($languages);
    }
}
