<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteLocation extends Model
{
    use HasFactory;
     protected $fillable = [
        'title',
        'lat',
        'long',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(Client::class , 'user_id');
    }
}
