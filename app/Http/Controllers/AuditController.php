<?php

namespace App\Http\Controllers;


use App\Models\Auditlogs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class AuditController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Forbidden - Admins only'], 403);
        }

        // Admins see all logs, with user relationship
        $logs = Auditlogs::with('user')
            ->latest()
            ->get();

        return response()->json(['data' => $logs]);
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
