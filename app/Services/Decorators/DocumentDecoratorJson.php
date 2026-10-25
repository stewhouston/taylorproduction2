<?php

namespace App\Services\Decorators;
use App\Models\Document;

class DocumentDecoratorJson
{
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function __toString()
    {
        return json_encode([
            ['document'          => $this->getDocument()],
            ['document_details'  => $this->getProductionDetails()],
            ['components'        => $this->getComponents()],
            ['component_details' => $this->getComponentsDetails()],
        ]);
    }

    protected function getComponentsDetails()
    {
        return $this->document->production_components->map(function($component) {
            return collect($component)->except('created_at', 'updated_at');
        })->toArray();
    }

    protected function getComponents()
    {
        return $this->document->components->map(function($component) {
            return collect($component)->except('created_at', 'updated_at');
        })->toArray();
    }

    protected function getProductionDetails()
    {
        return collect($this->document->production)
                    ->except('created_at', 'updated_at')
                    ->toArray();
    }

    protected function getDocument()
    {
        return [
            'id'       => $this->document->id,
            'quantity' => $this->document->quantity, 
            'cost'     => $this->document->production->cost,
        ];
    }
}