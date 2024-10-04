<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        $configTTL = auth()->factory()->getTTL();

        $ttl = $request->get('remember') === true ? ($configTTL * 7) : $configTTL;

        if (! $token = auth()->setTTL($ttl)->attempt($credentials)) {
            return response()->json(['error' => 'User not found'], 401);
        }

        $cookie = Cookie::make(
            name: 'auth_token',
            value: $token,
            minutes: $ttl,
            domain: env('FRONTEND_DOMAIN'),
            sameSite: 'Lax'
        );

        return response()->json(['result' => 'success', 'token' => $token])->cookie($cookie);
    }
}
