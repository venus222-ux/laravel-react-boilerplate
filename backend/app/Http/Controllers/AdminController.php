<?php

namespace App\Http\Controllers;

use App\Models\MongoLog;
use App\Models\User;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'stats' => [
                'logins_today' => MongoLog::where('action', 'login.success')
                    ->whereDate('created_at', now())
                    ->count(),

                'failed_logins_today' => MongoLog::where('action', 'login.failed')
                    ->whereDate('created_at', now())
                    ->count(),

                'active_users' => MongoLog::where('action', 'login.success')
                    ->where('created_at', '>=', now()->subMinutes(15))
                    ->distinct('user_id')
                    ->count(),
            ],

            'recent_activity' => MongoLog::orderBy('created_at', 'desc')
                ->limit(20)
                ->get(),

            'failed_attempts' => MongoLog::where('action', 'login.failed')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ]);
    }


    //Read and Delete Users
    public function users()
{
    // Return users with their roles (using Spatie)
    $users = User::with('roles')->get();
    return response()->json($users);
}

public function deleteUser($id)
{
    $user = User::findOrFail($id);

    // Prevent admin from deleting themselves
    if ($user->id === auth()->id()) {
        return response()->json(['message' => 'Cannot delete your own account'], 403);
    }

    $user->delete();
    return response()->json(['message' => 'User deleted successfully']);
}
}
