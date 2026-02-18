<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Station extends Model
{
    use HasFactory;

    protected $fillable = [

        'name',
        'address',
        'lat',
        'lng',
        'user_id'
    ];

    public function routeStations()
    {
        return $this->hasMany(RouteStation::class);
    }
    public function users(){
        return $this->belongsTo(User::class);
    }
    
}
