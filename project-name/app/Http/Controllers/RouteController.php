<?php
namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\RouteStation;
use App\Models\Station;
use App\Models\User;
use App\Services\NearestNeighborService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RouteController extends Controller
{
public function generate(User $user)
{
    $stationsData = $user->stations()
        ->whereIn('status', ['pending', 'assigned'])
        ->get();

    if ($stationsData->isEmpty()) {
        return response()->json(['message' => 'No pending stations found'], 404);
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Build locations array for TSP
    |    Index 0 = Depot (User)
    |--------------------------------------------------------------------------
    */

    $locations = [];
    $idMap = []; // Maps TSP index => real station_id

    // Depot (index 0)
    $locations[] = [
        'lat' => (float) $user->lat,
        'lng' => (float) $user->lng,
    ];

    // Stations start from index 1
    foreach ($stationsData as $index => $station) {

        $locations[] = [
            'lat' => (float) $station->lat,
            'lng' => (float) $station->lng,
        ];

        // Store mapping: TSP index → real DB station id
        $idMap[$index + 1] = $station->id;
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Call Optimization Service
    |--------------------------------------------------------------------------
    */

    $response = Http::post('http://127.0.0.1:4444/optimize', [
        'locations'   => $locations,
        'depot_index' => 0
    ]);

    if ($response->failed()) {
        return response()->json(['error' => 'Optimization failed'], 500);
    }

    $result = $response->json();

    if (!isset($result['route'])) {
        return response()->json(['error' => 'Invalid optimization response'], 500);
    }

    $optimizedRoute = $result['route']; // Example: [0, 3, 1, 2, 0]

    /*
    |--------------------------------------------------------------------------
    | 3. Save Route + Stations (Transaction Safe)
    |--------------------------------------------------------------------------
    */

    return DB::transaction(function () use ($optimizedRoute, $user, $result, $idMap) {

        $route = Route::create([
            'driver_id'      => $user->id,
            'name'           => 'Route ' . now()->format('Y-m-d H:i'),
            'total_distance' => $result['total_distance_meters'] ?? 0,
        ]);

        foreach ($optimizedRoute as $position => $nodeIndex) {

            // Skip depot (index 0)
            if ($nodeIndex == 0) {
                continue;
            }

            // Convert TSP index back to real station ID
            $stationId = $idMap[$nodeIndex];

            RouteStation::create([
                'route_id'       => $route->id,
                'station_id'     => $stationId,
                'sequence_order' => $position,
            ]);

            // Update station status
            Station::where('id', $stationId)
                ->update(['status' => 'assigned']);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Return Ordered Route With Depot First
        |--------------------------------------------------------------------------
        */

        $savedStations = RouteStation::where('route_id', $route->id)
            ->orderBy('sequence_order')
            ->get();

        $response = collect();

        // Add depot first
        $response->push([
            'sequence_order' => 0,
            'station' => [
                'id'   => $user->id,
                'name' => $user->name,
                'lat'  => $user->lat,
                'lng'  => $user->lng,
            ]
        ]);

        foreach ($savedStations as $routeStation) {

            $station = Station::find($routeStation->station_id);

            $response->push([
                'sequence_order' => $routeStation->sequence_order,
                'station' => $station
            ]);
        }

        return response()->json($response->values());
    });
}

public function show(Route $route)
{
   $route_id = $route->id;
   $route=RouteStation::where('route_id', $route_id)->orderBy('sequence_order')->get();
    return response()->json($route);
}
public function affiche($routes)
{
    // Use map to transform the collection into the desired format
    return $routes->map(function($route) {
        return [
            'sequence_order' => $route->sequence_order,
            'station' => Station::findOrFail($route->station_id)
        ];
    });
}
}