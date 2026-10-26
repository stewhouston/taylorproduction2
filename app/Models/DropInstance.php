<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

#[Table('drop_instances')]
class DropInstance extends Model
{
    protected $fillable = [
        'documents_qty', 'shipto_id', 'delivery_date', 'created_at', 'updated_at', 'created_by', 'last_modified_by', 'alerts', 'drop_weight_total', 'estimate_id',
        'drop_cost', 'drop_handling_cost', 'updated_directly_at', 'custom_ship_address', 'token'
    ];

    public function shipto() {
        return $this->hasOne(Shipto::class, 'id', 'shipto_id');
    }

    public function estimate() {
        return $this->belongsTo(Estimate::class);
    }
}
