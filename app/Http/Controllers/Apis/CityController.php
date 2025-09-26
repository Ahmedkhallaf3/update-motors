<?php

namespace App\Http\Controllers\Apis;

use App\Models\city;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
  public function index(Request $request): JsonResponse
{
    $country_id = $request->query('country_id'); // استلام country_id من الـ request

    $cities = City::when($country_id, function ($query) use ($country_id) {
        return $query->where('country_id', $country_id);
    })->get();

    return response()->json(['cities' => $cities], 200);
}


    /**
     * Store a newly created city in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string',
            'name_en' => 'required|string',
            'country_id' => 'required|exists:countries,id',
        ]);

        $city = City::create($validated);

        return response()->json(['message' => 'City created successfully', 'city' => $city], 201);
    }

    /**
     * Display the specified city.
     */
    public function show($id): JsonResponse
    {
        $city = City::findOrFail($id);
        return response()->json(['city' => $city], 200);
    }

    /**
     * Update the specified city in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string',
            'name_en' => 'required|string',
            'country_id' => 'required|exists:countries,id',
        ]);

        $city = City::findOrFail($id);
        $city->update($validated);

        return response()->json(['message' => 'City updated successfully', 'city' => $city], 200);
    }

    /**
     * Remove the specified city from storage.
     */
    public function destroy($id): JsonResponse
    {
        $city = city::findOrFail($id);
        $city->delete();

        return response()->json(['message' => 'City deleted successfully'], 200);
    }
}

