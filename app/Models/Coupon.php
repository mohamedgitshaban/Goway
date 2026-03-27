<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'usage_limit',
        'per_user_limit',
        'starts_at',
        'ends_at',
        'is_active',
        'trip_type_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_active' => 'boolean',
    ];

    public function tripType()
    {
        return $this->belongsTo(TripType::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('times_used')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function totalUsage(): int
    {
        return (int) $this->users()->sum('coupon_user.times_used');
    }

    public function userUsage(User $user): int
    {
        $pivot = $this->users()->where('user_id', $user->id)->first()?->pivot;
        return $pivot?->times_used ?? 0;
    }

    public function isValidFor(User $user, ?TripType $tripType = null): bool
    {
        $now = now();

        if (!$this->is_active) return false;
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at && $now->gt($this->ends_at)) return false;

        if ($this->usage_limit !== null && $this->totalUsage() >= $this->usage_limit) {
            return false;
        }

        if ($this->per_user_limit !== null && $this->userUsage($user) >= $this->per_user_limit) {
            return false;
        }

        if ($this->trip_type_id && $tripType) {
            return $this->trip_type_id === $tripType->id;
        }

        return true;
    }

    public function markUsedBy(User $user): void
    {
        $current = $this->userUsage($user);

        $this->users()->syncWithoutDetaching([
            $user->id => ['times_used' => $current + 1],
        ]);
    }
    public function couponUsers()
    {
        return $this->hasMany(CouponUser::class);
    }
}
