<?php

namespace App\Http\Controllers;

use App\Models\Auditlogs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Forbidden - Admins only'], 403);
        }

        $page = $request->query('page', 1);
        $logs = Auditlogs::with('user')
            ->where('user_id', '!=', $user->id)
            ->latest()
            ->paginate(10, ['*'], 'page', $page);

        return response()->json($logs);
    }

    public function searchByUsername(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            return response()->json(['error' => 'Forbidden - Admins only'], 403);
        }

        $username = $request->query('username');

        if ($username) {
            $logs = Auditlogs::with('user')
                ->whereHas('user', function ($query) use ($username) {
                    $query->where('username', 'like', "%{$username}%");
                })
                ->latest()
                ->paginate(10);

            return response()->json($logs);
        }
    }

    public function show(Auditlogs $auditLogs)
    {
        $auditLogs = Auditlogs::find($auditLogs->id);
        if (!$auditLogs) {
            return response()->json(['message' => 'Audit log not found'], Response::HTTP_NOT_FOUND);
        }
        return response()->json($auditLogs, Response::HTTP_OK);
    }
}
