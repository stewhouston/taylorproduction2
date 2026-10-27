<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('shipto')]
class Shipto extends Model
{
    protected $fillable = [
        'company_visibility_id', 'company', 'addr1', 'addr2', 'city', 'prov_state', 'postal_zip', 'is_active', 'zone', 'created_at', 'updated_at',  'modified_by'
    ];

    public function zoneRow() {
        return $this->hasOne(Zone::class, 'zone', 'zone');
    }
}
