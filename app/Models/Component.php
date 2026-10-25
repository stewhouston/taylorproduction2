<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    protected $fillable = [
      'document_id', 'type', 'print_side1', 'print_side2', 'bleed', 'pages', 'stock', 'report_description',
      'is_sequential', 'count_imprints', 'count_color_inserts',
    ];

    public function document() {
    	return $this->belongsTo(Document::class);
    }

    public function production() {
        return $this->hasOne(ProductionComponent::class);
    }
}
