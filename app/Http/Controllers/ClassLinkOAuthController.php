<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClassLinkOAuthController extends Controller
{
    public function authenticate()
    {
        //
    }

    public function login(Request $request)
    {
        ray($request->all());

        return response();
    }
}
