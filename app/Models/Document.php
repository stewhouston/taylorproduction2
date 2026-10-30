<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\Decorators\DocumentDecoratorJson;

#[Table('documents')]
class Document extends Model
{
    protected $fillable = [
      'estimate_id', 'name', 'description', 'cost', 'quantity', 'type', 'size',
      'finish', 'report_description', 'specification', 'is_custom', 'is_pending',
      'document_type_id', 'active_rate_set_id', 'is_digital_only', 'display_messages',
      'cover_type', 'created_by', 'last_modified_by'
    ];

    public function estimate() 
    {
    	return $this->belongsTo(Estimate::class);
    }

    public function production() 
    {
        return $this->hasOne(ProductionDocument::class);
    }

    public function components() 
    {
        return $this->hasMany(Component::class);
    }

    public function document_type() {
      return $this->belongsTo(DocumentType::class);
    }

    public function debug() 
    {
        return new DocumentDecoratorJson($this);
    }

    public function production_components() 
    {

        return $this->hasManyThrough(ProductionComponent::class, Component::class);
    }

    public function getDisplayMessages(): array 
    {
      try {
        return (array) json_decode($this->display_messages, 1);
      } catch (\Exception $e) {
        return [];
      }
    }

    public function addDisplayMessage(string $message, array $params = []) 
    {
      $params = array_merge([
        'clear' => false,
        'unique' => true
      ], $params);
      $messages = $params['clear'] ? [] : $this->getDisplayMessages();
      if ($params['unique'] && in_array($message, $messages)) {
        return;
      }
      $messages[] = $message;
      $this->display_messages = json_encode($messages);
      $this->save();
    }

    public function removeDisplayMessage(string $message) 
    {
      $messages = $this->getDisplayMessages();
      if (!in_array($message, $messages)) {
        return;
      }
      $updated_messages = array_reduce($messages, function(array $acc, string $_message) use ($message) {
        if ($message === $_message) {
          return $acc;
        }
        $acc[] = $_message;
        return $acc;
      }, []);
      if (count($messages) === count($updated_messages)) {
        return;
      }
      $this->display_messages = json_encode($updated_messages);
      $this->save();
    }
}
