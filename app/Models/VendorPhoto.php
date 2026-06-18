<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorPhoto extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'vendor_id',
        'url',
        'caption',
        'sort_order',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}