<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTypeName extends Model
{
    protected $fillable = [
        'name', 'short', 'status'
    ];

    public function types() {
        return $this->belongsToMany(DocumentType::class, 'types_names', 'size_id', 'type_id');
    }
}
