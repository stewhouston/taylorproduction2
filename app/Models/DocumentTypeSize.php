<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

#[Table('document_type_sizes')]
class DocumentTypeSize extends Model
{
    protected $fillable = [
        'name',
    ];

    public function types()
    {
        return $this->belongsToMany(DocumentType::class, 'types_sizes', 'size_id', 'type_id');
    }
}
