<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RouteStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'station_id',
        'sequence_order',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
