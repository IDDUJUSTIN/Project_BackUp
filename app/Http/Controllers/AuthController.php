<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use App\Models\Auditlogs;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name'     => ['required', 'string', 'max:255', 'regex:/^[A-Za-z\s]+$/'],
            'middle_name'    => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z\s]+$/'],
            'last_name'      => ['required', 'string', 'max:255', 'regex:/^[A-Za-z\s]+$/'],
            'contact_number' => ['required', 'string', 'max:11', 'regex:/^[0-9]+$/'],
            'email'          => ['required', 'email', 'unique:users,email'],
            'username'       => ['required', 'string', 'max:255'],
            'password'       => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
        ]);


        $user = User::create([
            'first_name'     => $data['first_name'],
            'middle_name'    => $data['middle_name'],
            'last_name'      => $data['last_name'],
            'contact_number' => $data['contact_number'],
            'email'          => $data['email'],
            'username'       => $data['username'],
            'password'       => bcrypt($data['password']),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $validated['username'])
            ->where('email', $validated['email'])
            ->first();

        if (!$user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ], 401);
        }
        AuditLogs::create([
            'user_id' => $user->id,
            'activitylogs' => 'User logged in: ' . $user->email,
            'action' => 'LOGIN'
        ]);
        
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user'    => $user,
            'token'   => $token,
        ], 200);
    }
    public function show(Request $request)
    {
        $user = $request->user();

        $fullName = trim($user->first_name . ' ' . ($user->middle_name ? $user->middle_name . ' ' : '') . $user->last_name);

        return response()->json([
            'fullname' => $fullName,
            'contact_number' => $user->contact_number,
            'username' => $user->username,
        ]);
    }
    public function allUsers(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Forbidden - Admins only'], 403);
        }

        $users = User::where('id', '!=', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(['first_name', 'middle_name', 'last_name', 'username', 'email', 'contact_number', 'created_at']);

        $users = $users->map(function ($u) {
            $fullName = trim($u->first_name . ' ' . ($u->middle_name ? $u->middle_name . ' ' : '') . $u->last_name);
            return [
                'fullname'       => $fullName,
                'username'       => $u->username,
                'email'          => $u->email,
                'contact_number' => $u->contact_number,
                'created_at'     => $u->created_at,
            ];
        });

        return response()->json([
            'message' => 'All users retrieved successfully',
            'users'   => $users,
        ]);
    }




    public function update(Request $request)
    {
        $user = $request->user();


        $validated = $request->validate([
            'first_name'      => 'sometimes|string|max:255',
            'middle_name'     => 'nullable|string|max:255',
            'last_name'       => 'sometimes|string|max:255',
            'username'        => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'contact_number'  => 'sometimes|string|max:20',
        ]);


        $user->update($validated);


        $fullName = trim($user->first_name . ' ' . ($user->middle_name ? $user->middle_name . ' ' : '') . $user->last_name);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'fullname'       => $fullName,
                'email'          => $user->email,
                'username'       => $user->username,
                'contact_number' => $user->contact_number,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
