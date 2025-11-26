<?php

namespace App\Http\Controllers;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    // Save user farm location
   public function saveLocation(Request $request)
{
    $data = $request->validate([
        'province' => 'required|string',
        'city'     => 'required|string',
    ]);

    $location = Location::where('province', $data['province'])
        ->where('city', $data['city'])
        ->first();

    if (! $location) {
        return response()->json(['error' => 'Location not found'], 404);
    }
    /** @var \App\Models\User $user */
    $user = Auth::user();
    if (! $user) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    // associate the location via foreign key
    $user->location()->associate($location);
    $user->save();

    return response()->json(['message' => 'Location saved successfully']);
}

    // Get weather forecast for user farm
    public function getWeather()
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $location = $user->location;
        if (! $location) {
            return response()->json(['error' => 'No farm location set'], 400);
        }

        $params = [
            'lat'   => $location->latitude,
            'lon'   => $location->longitude,
            'appid' => env('OPENWEATHER_API_KEY'),
            'units' => 'metric',
        ];

        $response = Http::get('https://api.openweathermap.org/data/2.5/forecast', $params);

        if (! $response->successful()) {
            return response()->json([
                'error'   => 'Weather service error',
                'details' => $response->body(),
            ], $response->status());
        }

        return $response->json();
    }
}
