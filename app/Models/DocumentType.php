<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

#[Table('document_types')]
class DocumentType extends Model
{
    protected $fillable = [
        'name', 'slug', 'is_active', 'order_index',
    ];

    public function names() {
        return $this->belongsToMany(DocumentTypeName::class, 'types_names', 'type_id', 'name_id');
    }
}
