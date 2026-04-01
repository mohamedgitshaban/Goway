<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;
        protected $fillable = [
        'trip_id',
        'rated_user_id',
        'rated_by_user_id',
        'rated_by',
        'rating',
        'comment',
    ];


    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function ratedUser()
    {
        return $this->belongsTo(User::class, 'rated_user_id');
    }

    public function ratedByUser()
    {
        return $this->belongsTo(User::class, 'rated_by_user_id');
    }
}
