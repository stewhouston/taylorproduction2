<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// @todo port from legacy app
class DocumentDecoratorJson {
    public function __construct(Document $document) {
        return 'todo';
    }

    public function __toString() {
        return '["todo"]';
    }
}

#[Table('documents')]
class Document extends Model
{
    protected $fillable = [
      'estimate_id', 'name', 'description', 'cost', 'quantity', 'type', 'size',
      'finish', 'report_description', 'specification', 'is_custom', 'is_pending',
      'document_type_id', 'active_rate_set_id', 'is_digital_only', 'display_messages',
      'cover_type', 'created_by', 'last_modified_by'
    ];

    public function estimate() {
    	return $this->belongsTo(Estimate::class);
    }

    public function production() {
        return $this->hasOne(ProductionDocument::class);
    }

    public function debug() {
        return new DocumentDecoratorJson($this);
    }
}
