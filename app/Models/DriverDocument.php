<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'age',

        // Driver NID
        'nid_front',
        'nid_back',

        // Parent NID (if age < 18)
        'parent_nid_front',
        'parent_nid_back',

        // Optional license
        'license_image',

        // Required criminal record
        'criminal_record',

        // Review status
        'status',
        'reject_reason',
    ];

    protected $casts = [
        'age' => 'integer',
    ];

    /**
     * Relationship: each driver has one document record
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Helper: check if driver is under 18
     */
    public function requiresParentNID(): bool
    {
        return $this->age < 18;
    }

    /**
     * Helper: check if all required documents are present
     */
        public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Helper: check if admin rejected the documents
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Helper: check if documents are still under review
     */
    public function isInReview(): bool
    {
        return $this->status === 'inreview';
    }
}
