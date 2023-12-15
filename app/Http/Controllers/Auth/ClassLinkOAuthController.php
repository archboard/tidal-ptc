<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
