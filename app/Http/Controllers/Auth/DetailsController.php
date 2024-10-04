<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class DetailsController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'status' => 'success',
            'user' => auth()->user(),
        ]);
    }
}
