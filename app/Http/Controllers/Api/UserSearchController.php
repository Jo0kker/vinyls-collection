<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $request->input('q');
        $currentUserId = Auth::id();

        $users = User::query()
            ->where('id', '!=', $currentUserId)
            ->whereNotNull('email_verified_at')
            ->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                    ->orWhere('email', 'ilike', "%{$query}%");
            })
            ->select(['id', 'name', 'email', 'avatar'])
            ->limit(10)
            ->get();

        return response()->json($users);
    }
}
