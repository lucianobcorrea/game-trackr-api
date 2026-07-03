<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function me()
    {
        $user = auth()->user();
        return response()->json([
            'user' => $user,
        ], 200);
    }
}
