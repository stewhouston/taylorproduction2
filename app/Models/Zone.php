<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = [
        'zone', 'ship_hours', 'base_rate', 'additional_lb', 'carton_lbs', 'additional_carton', 'skid_lbs', 'additional_skid', 'handling', 'is_active', 'modified_at', 'modified_by'
    ];
}
