<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Image;

class PredictionController extends Controller
{
   public function predict(Request $request)
{
    $request->validate([
        'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
    ]);

    $file = $request->file('image');
    $originalName = $file->getClientOriginalName();

    // store ng pic sa public/storage/images
    $storedPath = $file->store('images', 'public');

    // Forward the file to Flask
    $response = Http::attach(
        'image',
        fopen($file->getRealPath(), 'r'),
        $originalName
    )->post('http://127.0.0.1:5000/predict');

    Log::info('Flask response', ['status' => $response->status(), 'body' => $response->body()]);

    if (! $response->successful()) {
        return response()->json([
            'error' => 'Prediction service error',
            'details' => $response->body()
        ], $response->status());
    }

    $data = $response->json();

    $prediction = $data['class'] ?? $data['prediction'] ?? null;
    $confidence = $data['confidence'] ?? null;

    if (! $prediction) {
        Log::error('Missing prediction key in Flask response', ['response' => $data]);
        return response()->json(['error' => 'Invalid response from prediction service'], 500);
    }
    // pra di mag save pag unknown
    if (strtolower($prediction) === 'unknown') {
        Log::warning('Prediction was Unknown, not saving to DB', ['prediction' => $prediction]);
        return response()->json([
            'prediction' => $prediction,
            'confidence_level' => $confidence,
            'message' => 'Prediction result is Unknown, record not saved.'
        ]);
    }

    //  Save to DB with user_id
    $image = Image::create([
        'filename'   => $originalName,
        'prediction' => $prediction,
        'confidence_level' => $confidence,
        'path'       => $storedPath,
        'user_id'    => optional($request->user())->id,
    ]);

    Log::info('Image record created', ['id' => $image->id, 'attributes' => $image->toArray()]);

    return response()->json([
        'prediction' => $prediction,
        'confidence_level' => $confidence,
        'record'     => $image,
    ]);
}


    public function list(Request $request)
{
    $perPage = 10;
    $user = $request->user();

    if (! $user) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $query = Image::query()
        ->join('users', 'images.user_id', '=', 'users.id'); 

    if ($user->role !== 'admin') {
        $query->where('images.user_id', $user->id);
    }

    $images = $query->orderBy('images.created_at', 'desc')
        ->select(
            'images.path',
            'images.prediction',
            'images.confidence_level',
            'images.created_at',
            'users.username' 
        )
        ->paginate($perPage);

    return response()->json($images);
}



   public function show(Request $request, $id)
{
    $user = $request->user();

    if (! $user) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $query = Image::query();

    if ($user->role !== 'admin') {
        $query->where('user_id', $user->id);
    }

    $img = $query->findOrFail($id);

    return response()->json([
        'path' => $img->path,
        'prediction' => $img->prediction,
        'confidence_level' => $img->confidence_level,
        'created_at' => $img->created_at,
        'url' => $img->path ? asset('storage/' . $img->path) : null,
    ]);
}



    public function stats(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Image::query();

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }
        $counts = $query->select('prediction', DB::raw('COUNT(*) as total'))
            ->groupBy('prediction')
            ->get();

        $overall = $counts->sum('total');

        return response()->json([
            'overall' => $overall,
            'breakdown' => $counts,
        ]);
    }
    public function monthlyStats(Request $request)
{
    $user = $request->user();

    if (! $user) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $query = Image::query();

    if ($user->role !== 'admin') {
        $query->where('user_id', $user->id);
    }

    // Group by month and prediction
    $counts = $query->select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
            'prediction',
            DB::raw('COUNT(*) as total')
        )
        ->groupBy('month', 'prediction')
        ->orderBy('month')
        ->get();

    return response()->json([
        'breakdown' => $counts
    ]);
}

}
