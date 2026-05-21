<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverDestinationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'lat',
        'lng',
        'address',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
