<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get authenticated user profile.
     */
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Update profile fields.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'documents' => 'sometimes|string|max:500', // URL or file path
        ]);

        $user->update($data);

        return response()->json(['message' => 'Updated', 'user' => $user]);
    }
}
