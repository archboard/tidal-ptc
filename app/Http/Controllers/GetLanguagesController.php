<?php

namespace App\Http\Controllers;

use App\Enums\Language;
use App\Http\Resources\LanguageResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GetLanguagesController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $languages = Language::collect()->sortBy('name');

        return LanguageResource::collection($languages);
    }
}
