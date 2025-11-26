<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Location; // your model

class GetWeatherController extends Controller
{
    public function show(Request $request)
    {
        $apiKey = config('services.openweather.key');

        $province = $request->input('province');
        $city     = $request->input('city');
        $barangay = $request->input('barangay');

        if (!$province || !$city) {
            return response()->json(['error' => 'Province and City are required'], 400);
        }

        // Always geocode with city + province (barangay is too granular for OpenWeather)
        $locationString = "{$city},{$province},PH";

        $geoUrl = "http://api.openweathermap.org/geo/1.0/direct?q={$locationString}&limit=1&appid={$apiKey}";
        $geoRes = Http::get($geoUrl);
        $geoData = $geoRes->json();

        if (!is_array($geoData) || count($geoData) === 0) {
            return response()->json([
                'error' => 'Location not found',
                'searched_location' => $locationString
            ], 404);
        }

        $lat = $geoData[0]['lat'];
        $lon = $geoData[0]['lon'];

        // Save to DB (user_locations table)
        $user = $request->user(); // Sanctum authenticated user
        $savedLocation = Location::create([
            'user_id'   => $user->id,
            'province'  => $province,
            'city'      => $city,
            'barangay'  => $barangay,
            'latitude'  => $lat,
            'longitude' => $lon,
        ]);

        // Weather request (optional, still return weather info)
        $weatherUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
        $weatherRes = Http::withOptions(['verify' => false])->get($weatherUrl);

        if (!$weatherRes->ok()) {
            return response()->json(['error' => 'Weather API failed'], $weatherRes->status());
        }

        $weatherData = $weatherRes->json();

        return response()->json([
            'message' => 'Location saved successfully',
            'location' => $savedLocation,
            'weather' => [
                'temperature' => $weatherData['main']['temp'] ?? null,
                'condition'   => $weatherData['weather'][0]['description'] ?? null,
                'humidity'    => $weatherData['main']['humidity'] ?? null,
                'wind_speed'  => $weatherData['wind']['speed'] ?? null,
            ],
        ]);
    }
    public function history(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $locations = Location::where('user_id', $user->id)->get();
        $apiKey = config('services.openweather.key');
        $weatherData = [];

        foreach ($locations as $loc) {
            $weatherUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$loc->latitude}&lon={$loc->longitude}&appid={$apiKey}&units=metric";
            $res = Http::withOptions(['verify' => false])->get($weatherUrl);

            if ($res->ok()) {
                $data = $res->json();
                $weatherData[] = [
                    'province'    => $loc->province,
                    'city'        => $loc->city,
                    'barangay'    => $loc->barangay,
                    'latitude'    => $loc->latitude,
                    'longitude'   => $loc->longitude,
                    'condition'   => $data['weather'][0]['description'] ?? null,
                    'temperature' => $data['main']['temp'] ?? null,
                    'humidity'    => $data['main']['humidity'] ?? null,
                    'wind_speed'  => $data['wind']['speed'] ?? null,
                ];
            }
        }

        return response()->json($weatherData);
    }
}
