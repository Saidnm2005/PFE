<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Route extends Model
{
    use HasFactory;
    protected $fillable = [
        'driver_id',
        'total_distance',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function routeStations()
    {
        return $this->hasMany(RouteStation::class)->orderBy('sequence_order');
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }
   
}
