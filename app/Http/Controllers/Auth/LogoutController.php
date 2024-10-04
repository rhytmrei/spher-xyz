<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        auth('api')->logout();

        $cookie = Cookie::forget('auth_token');

        return response()->json(['message' => 'Logged out successfully'])->withCookie($cookie);
    }
}
