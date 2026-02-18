<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\Request;

class StationController extends Controller
{
   public function index()
{
    return Station::where('user_id', auth()->id())
        ->whereIn('status', ['pending', 'assigned'])
        ->get();
}
    public function store(Request $request)
    {

        return Station::create($request->all());
    }

    public function show(Station $station)
    {
        return $station;
        
    }

    public function update(Request $request, Station $station)
    {
        $station->update($request->all());

        return response()->json('Station updated successfully');
    }

    public function destroy(Station $station)
    {
        $station->delete();
        return response()->json('Station deleted successfully');
    }
}
