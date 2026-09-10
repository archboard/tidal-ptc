<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;

class ClassLinkOAuthController extends Controller
{
    public function authenticate(): void
    {
        //
    }

    public function login(Request $request): ResponseFactory
    {
        return response();
    }
}
