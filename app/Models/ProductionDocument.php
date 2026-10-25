<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

#[Table('production_documents')]
class ProductionDocument extends Model
{
    protected $fillable = [
        'print_hours', 'print_cost', 'print_sell', 'fold_hours', 'fold_cost', 'fold_sell', 'cut_hours', 'cut_cost', 
        'cut_sell', 'saddlestitch_hours', 'saddlestitch_cost', 'saddlestitch_sell', 'perfect_bind_hours', 'perfect_bind_cost', 
        'perfect_bind_sell', 'bind_hours', 'bind_cost', 'bind_sell', 'hours', 'hour_range', 'cost', 'sell', 'weight', 
        'document_id', 'custom_description_1', 'custom_description_2'
    ];

    public function document() {
    	return $this->belongsTo(Document::class, 'document_id');
    }
}
