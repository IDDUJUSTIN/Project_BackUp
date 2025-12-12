<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Location; 

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

      
        $user = $request->user(); 
        $savedLocation = Location::create([
            'user_id'   => $user->id,
            'province'  => $province,
            'city'      => $city,
            'barangay'  => $barangay,
            'latitude'  => $lat,
            'longitude' => $lon,
        ]);

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

    public function update(Request $request, $id)
    {
        $request->validate([
            'province' => 'required|string',
            'city'     => 'required|string',
            'barangay' => 'required|string',
        ]);

        $location = Location::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$location) {
            return response()->json([
                'error' => 'Location not found or access denied',
                'id' => $id,
                'user_id' => auth()->id()
            ], 404);
        }
        $location->province = $request->province;
        $location->city     = $request->city;
        $location->barangay = $request->barangay;

        $apiKey = config('services.openweather.key');
        $locationString = "{$request->city},{$request->province},PH";

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

        $location->latitude  = $lat;
        $location->longitude = $lon;
        $location->save();

        $weatherUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
        $weatherRes = Http::withOptions(['verify' => false])->get($weatherUrl);

        if (!$weatherRes->ok()) {
            return response()->json(['error' => 'Weather API failed'], $weatherRes->status());
        }

        $weatherData = $weatherRes->json();

        return response()->json([
            'message' => 'Location updated successfully',
            'id'      => $location->id,
            'location' => [
                'province'  => $location->province,
                'city'      => $location->city,
                'barangay'  => $location->barangay,
                'latitude'  => $location->latitude,
                'longitude' => $location->longitude,
            ],
            'weather' => [
                'condition'   => $weatherData['weather'][0]['description'] ?? null,
                'temperature' => $weatherData['main']['temp'] ?? null,
                'humidity'    => $weatherData['main']['humidity'] ?? null,
                'wind_speed'  => $weatherData['wind']['speed'] ?? null,
            ]
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
                'id'          => $loc->id, // ✅ include this
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
