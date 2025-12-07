<?php

namespace App\Http\Controllers;

use App\Models\User_Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserActionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Check if the authenticated user is an admin
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $userActions = User_Action::all();
        return response()->json($userActions);
    }
    public function search(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json(['error' => 'user_id is required'], 400);
        }

        $actions = User_Action::where('user_id', $userId)->paginate(10);

        if ($actions->isEmpty()) {
            return response()->json(['error' => 'No matching logs found'], 404);
        }

        return response()->json($actions);
    }
}
