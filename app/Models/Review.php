<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'booking_id',
        'customer_id',
        'vendor_id',
        'rating',
        'comment',
        'vendor_reply',
        'replied_at',
        'is_visible',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
        'is_visible' => 'boolean',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}