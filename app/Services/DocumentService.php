<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentTypeName;
use App\Models\Curve;
use App\Models\SellRate;
use App\Models\ProductionDocument;
use App\Models\ProductionComponent;
use App\Objects\PrintBind;
use App\Objects\PrintConfig;
use App\Objects\PrintCost;
use App\Objects\PrintHours;
use App\Objects\PrintReport;
use App\Objects\PrintSell;
use App\Objects\PrintStock;
use App\Objects\ProductionHours;
use Illuminate\Http\Request;

class DocumentService 
{
  const HIGH_IMPRESSION_COUNT_ALERT = "High Impression Count, check with Taylor for pricing and alternate production methods.";
  const CUSTOM_DOCUMENT_PENDING_ALERT = "Custom document(s) for this estimate are currently pending.";
  const COLOR_TRIVOR_PRICING_MESSAGE = "Print pricing is based on a maximum overall document coverage of 25%. Overall document coverage greater than 25% may be subject to price increases.";

  protected $estimate;
  protected $document;

  public function __construct(Document $document, Estimate $estimate) 
  {
    $this->document = $document;
    $this->estimate = $estimate;
  }

  public function create($request, Estimate $estimate, array $opts = []) 
  {
      $opts = array_merge([
        'active_rate_set_id' => null
      ], $opts);

      $this->estimate = $estimate;
      $doc_type = DocumentType::find($request->input('document_type_id'));
      $active_rate_set_id = $opts['active_rate_set_id'] ?: PricingRateSet::companyActive($estimate->company_id, 'id');
      $attrs = [
        'active_rate_set_id' => $active_rate_set_id,
        'company_id' => $estimate->company_id,
        'curve_fields' => Curve::getNamedMap($estimate->company_id)->toArray(),
        'print_o_rates' => SellRate::getTypeRatesCollection('PrintOther', $active_rate_set_id),
      ];

      switch ($doc_type->slug) {
        case "single-sheet":
            $document = $this->startSingleSheetLogic($request, $attrs);
        break;
        case "multi-sheet":
            $document = $this->startMultiSheetLogic($request, $attrs);
        break;
        case "folder":
            $document = $this->startFolderLogic($request, $attrs);
        break;
        case "custom":
            $document = $this->startCustomLogic($request, $attrs, $estimate);
        break;
        default:
            $document = $this->startBookLogic($request, $attrs);
        break;
      }

      return $document;
  }

  public function destroy(int $document_id, Estimate $estimate, array $params = []): bool
  {
      $document = Document::find($document_id);
      $custom_documents = $estimate->customDocuments();
      // $pending_documents = $estimate->pendingDocuments();
      // $is_only_pending_document = (bool) $document->is_pending == 0 && count($pending_documents) <= 1;
      $is_only_custom_document = (bool) ($document->is_custom && count($custom_documents) <= 1);
      $estimate->last_modified_by = $params['last_modified_by'];
      if ($document->is_custom && $is_only_custom_document) {
        $estimate->clearAlert(DocumentService::CUSTOM_DOCUMENT_PENDING_ALERT);
      }
      $destroy = $document->delete();
      $estimate->save();
      return $destroy;
  }

  public function update($request, int $id, array $opts = []): Document 
  {
    $opts = array_merge([
      'active_rate_set_id' => null
    ], $opts);

    //try {
      $this->document = Document::find($id);
      $estimate_company_id = (int) $this->document->estimate->company_id;
      $active_rate_set_id = $opts['active_rate_set_id'] ?? PricingRateSet::companyActive($estimate_company_id, 'id');

      $attrs = [
        'active_rate_set_id' => $active_rate_set_id,
        'company_id' => $estimate_company_id,
        'curve_fields' => Curve::getNamedMap($estimate_company_id)->toArray(),
        'print_o_rates' => SellRate::getTypeRatesCollection('PrintOther', $active_rate_set_id),
      ];

      switch ($this->document->document_type->slug) {
        case "single-sheet":
          $document = $this->startSingleSheetUpdateLogic($request, $attrs);
        break;
        case "multi-sheet":
          $document = $this->startMultiSheetUpdateLogic($request, $attrs);
        break;
        case "folder":
          $document = $this->startFolderUpdateLogic($request, $attrs);
        break;
        case "custom":
          if (!empty($opts['is_cost_recalc'])) {
            // No systematic cost changes on custom documents as per the rate tables
            $document = $this->document;
          } else {
            $document = $this->startCustomLogic($request, $attrs, $this->document->estimate);
          }
        break;
        default: // "saddlestich" or "perfect-bind"
          $document = $this->startBookUpdateLogic($request, $attrs);
        break;
      }
    return $document;
  }

  protected function requestValue($request, string $field, $default = null) {
    if ($request instanceof Request) return $request->input($field);
    if (is_array($request)) return $request[$field] ?? $default;
    return $default;
  }

  public static function createProductionDocument(Document $document, array $prod_doc_data): ProductionDocument 
  {
    $defaults = [
      'print_hours' => 0,
      'print_cost' => 0,
      'print_sell' => 0,
      'fold_hours' => 0,
      'fold_cost' => 0,
      'fold_sell' => 0,
      'cut_hours' => 0,
      'cut_cost' => 0,
      'cut_sell' => 0,
      'saddlestitch_hours' => 0,
      'saddlestitch_cost' => 0,
      'saddlestitch_sell' => 0,
      'perfect_bind_hours' => 0,
      'perfect_bind_cost' => 0,
      'perfect_bind_sell' => 0,
      'bind_hours' => 0,
      'bind_cost' => 0,
      'bind_sell' => 0,
      'hours' => 0,
      'hour_range' => '',
      'cost' => 0,
      'sell' => 0,
      'weight' => 0,
      'custom_description_1' => '',
      'custom_description_2' => ''
    ];
    $prod_doc = new ProductionDocument(array_merge($defaults, ['document_id' => $document->id], $prod_doc_data));
    $prod_doc->save();
    return $prod_doc;
  }

  protected function getBookComponent($type, $components) {
    foreach($components as $comp) {
      if ($comp->type == $type) return $comp;
    }
    return null;
  }
  
  // Organizes component data for books (or non-books) following ad-hoc logic used in the Saddlestitch and Perfect Bind templates. Used now as a replacement for those as well as used in the price recalculation procedure.
  public function getBookComponentValues(Document $document = null, $defaults = []): array {
    $document = $document ?? $this->document;
    $components = $document->components;
    $defaults = array_merge([
      'cover_stock' => 'none',
      'print_side1' => 'none',
      'print_side2' => 'none',
      'bleed' => 0, // cover bleed
      'text_stock' => null, // should always be populated
      'text_print' => null, // should always be populated
      'text_pages' => 0,
      'text_bleed' => 0,
      'is_sequential' => 0,
      'count_color_inserts' => 0,
      'count_imprints' => 0,
    ], $defaults);

    $is_not_book = (bool) in_array($document->type, ['Single Sheet', 'Multi Sheet', 'Folder']);

    if ($document->is_custom) {
      return $defaults; // @todo handle this if required in the future
    }
    else if ($is_not_book) {
      $comp = $components->first();
      return [
        'text_stock' => $comp->stock,
        'print_side1' => $comp->print_side1,
        'print_side2' => $comp->print_side2,
        'bleed' => $comp->bleed,
        'text_pages' => $comp->pages
      ];
    }

    if ($components->count() === 1) {
      $comp = $components->first();
      return array_merge($defaults, [
        'text_stock' => $comp->stock,
        'text_print' => $comp->print_side1,
        'text_pages' => $comp->pages
      ]);
    }
    // Custom documents
    else if ($components->count() === 0) {
      return array_merge($defaults, []);
    }
    else {
      $comp1 = $this->getBookComponent('Cover', $components);
      $comp2 = $this->getBookComponent('Text', $components);
      $add_pages = !!in_array($document->finish, ['Saddlestitch', 'Perfect Bind']) ? 4 : 0;
      if ($comp1->stock === '6 pg cover on 8pt Ctd Cover') {
        $add_pages = 6;
      }
      return array_merge($defaults, [
        'cover_stock' => $comp1->stock,
        'print_side1' => $comp1->print_side1,
        'print_side2' => $comp1->print_side2,
        'bleed' => $comp1->bleed,
        'text_stock' => $comp2->stock,
        'text_print' => $comp2->print_side1,
        'text_pages' => (intval($comp2->pages) + $add_pages),
        'text_bleed' => 0,
        'is_sequential' => $comp1->is_sequential ?: $comp2->is_sequential,
        'count_color_inserts' => $comp2->count_color_inserts,
        'count_imprints' => $comp1->count_imprints,
      ]);
    }
  }

  protected function startBookUpdateLogic($request, array $attrs = []): Document {
    $all_request_fields = ($request instanceof Request) ? $request->all() : $request;
    $size              = $this->requestValue($request, 'size');
    $finish            = $this->requestValue($request, 'finish');
    $quantity          = $this->requestValue($request, 'quantity');
    $name              = $this->requestValue($request, 'name');
    $description       = $this->requestValue($request, 'description');
    $cover_type        = $this->requestValue($request, 'cover_type');
    $cover_stock       = $this->requestValue($request, 'cover_stock');
    $cover_print_side1 = $this->requestValue($request, 'print_side1');
    $cover_print_side2 = $this->requestValue($request, 'print_side2');
    $text_bleed        = $this->requestValue($request, 'text_bleed');
    $bleed             = $this->requestValue($request, 'bleed');
    $text_stock        = $this->requestValue($request, 'text_stock');
    $print_side1       = $this->requestValue($request, 'text_print');
    $print_side2       = $this->requestValue($request, 'text_print');
    $count_imprints    = $this->requestValue($request, 'count_imprints');
    $count_color_inserts = $this->requestValue($request, 'count_color_inserts');
    $is_sequential     = (bool) json_decode($this->requestValue($request, 'is_sequential', '0'));
    $is_digital_only   = (bool) json_decode($this->requestValue($request, 'is_digital_only', '0'));
    $pages             = 0;
    $data              = [];

    // For new option `cover_type` = 'self', cover stock inherits from text detail.
    if ($cover_type === 'self') {
      $cover_stock = 'none';
      $cover_print_side1 = 'none';
      $cover_print_side2 = 'none';
      $bleed = 0;
    }
    // Returns an Eloquent Collection object that represents a `component` row. Will be an empty Collection if no Cover exists for the given document.
    $cover_component = $this->document->components()->where('type', 'Cover')->get();
    $form_has_cover_component = (bool) ($cover_stock != 'none' && $cover_print_side1 != 'none');
    // Determine Self Or Plus Cover
    list($type, $component_count) = $this->getDocumentTypeAndComponentCount($cover_stock, $cover_print_side1);
    // convert document type short name (e.g. 'MD&A' to full name)
    $dtn = DocumentTypeName::where('short', $name)->get()->first();
    $report_description = PrintReport::buildDocumentDescription($dtn->name, $description, $type, $size, $finish);
    $document_user_meta = array(
      'last_modified_by' => $request['user_id'] ?? $request->user()->id
    );
    $this->document->update(array_merge(
      $all_request_fields,
      $document_user_meta,
      ['specification' => ''],
      compact('cover_type', 'report_description', 'type', 'is_digital_only'),
      ['active_rate_set_id' => $attrs['active_rate_set_id']]
    ));
    // if cover exists update (or delete) it
    if ($cover_component->count() === 1) {
      if ($cover_stock === 'none') {
        $cover_component->first()->delete();
        $data = [
          'cover_cut_required'          => false,
          'cover_run_sheets'            => 0,
          'cover_component_weight'      => 0,
          'cover_print_hours'           => 0,
          'cover_print_cost'            => 0,
          'cover_component_print_sell'  => 0
        ];
      } else {
        $component_type = 'Cover';
        $pages = !!($cover_stock === '6 pg cover on 8pt Ctd Cover') ? 6 : 4;
        $component_report_description = PrintReport::buildComponentDescription(
          $component_count, $component_type, $pages, $cover_print_side1, $cover_print_side2, $bleed, $cover_stock,
          $is_sequential, $count_color_inserts, $count_imprints
        );
        $cover_component->first()->update([
          'type'               => $component_type,
          'print_side1'        => $cover_print_side1,
          'print_side2'        => $cover_print_side2,
          'bleed'              => $bleed,
          'pages'              => $pages,
          'stock'              => $cover_stock,
          'report_description' => $component_report_description,
          'count_imprints'     => $count_imprints,
          'is_sequential'      => $is_sequential,
        ]);
        $data = $this->componentConfig(
          $this->document, $cover_print_side1, $cover_print_side2, $size, $pages,
          $finish, $component_type, $type, $quantity, $bleed, $cover_stock, $component_count,
          $is_sequential, $count_imprints, 0, $attrs
        );
        $data['cover_cut_required']     = $data['cut_required'];
        $data['cover_run_sheets']       = $data['run_sheets'];
        $data['cover_component_weight'] = $data['component_weight'];
        $data['cover_print_hours']      = $data['print_hours'];
        $data['cover_print_cost']       = $data['print_cost'];
        $component_count                = $data['document_component_count'];
        $productionComponent[0] = $cover_component->first()->production;
        $productionComponent[0]->update($data);
        $cover_component_id = $cover_component->first()->id;
      }
    }
    // Stew: a Cover was not created when the estimate was created, but a Cover has not been specified on the edit page.
    // Create a new component row for the Cover.
    elseif ($cover_component->count() === 0 && boolval($form_has_cover_component)) {
      $document_fields = [
        'size' => $size,
        'finish' => $finish,
        'type' => $type,
        'quantity' => $quantity
      ];
      $component_fields = [
        'type'           => 'Cover',
        'print_side1'    => $cover_print_side1,
        'print_side2'    => $cover_print_side2,
        'bleed'          => $bleed,
        'pages'          => $pages,
        'stock'          => $cover_stock,
        'count_imprints' => $count_imprints,
        'is_sequential'  => $is_sequential,
      ];
      list($cover_component, $production_component, $data) = $this->createCoverComponent(
        $this->document, $document_fields, $component_count, $component_fields, $attrs
      );
      $pages = 4;
    }
    // Non-existent cover
    else {
      $data['cover_cut_required'] = false;
      $data['cover_run_sheets'] = 0;
      $data['cover_component_weight'] = 0;
      $data['cover_print_hours'] = 0;
      $data['cover_print_cost'] = 0;
      $data['cover_component_print_sell'] = 0;
    }
    // process text component
    $component_type = 'Text';
    $print_side1    = $all_request_fields['text_print'];
    $print_side2    = $print_side1; //for books - text always duplex so s2 col = s1 col
    $pages          = ($all_request_fields['text_pages'] - $pages); //text pages is pages less cover pages
    /* Saddlestitch pages must be a multiple of 4. Perfet Bind text pages must be multiple of 2
    * Check if multiple of 4 for Saddlestitch and if not, round up to next multiple of 4
    * Check if multiple of 2 for Perfect bind text pages and if not, round up to next multiple of 2
    */
    $pages = PrintReport::applyPagesModuloBooks($finish, $pages);
    $bleed = 0; // bleed not offered in version one for text of books
    $text_component_o_fields = [
      'count_imprints' => 0,
      'is_sequential' => false,
    ];
    if (!$form_has_cover_component) {
      $text_component_o_fields = [
        'is_sequential' => $is_sequential,
        'count_imprints' => $count_imprints,
      ];
    }
    $component_report_description = PrintReport::buildComponentDescription(
      $component_count, $component_type, $pages, $print_side1, $print_side2, $bleed, $text_stock,
      $is_sequential, $count_color_inserts, $text_component_o_fields['count_imprints']
    );
    $text_component = $this->document->components()->where('type', 'Text')->get();
    $text_component->first()->update(array_merge([
      'type'               => $component_type,
      'print_side1'        => $print_side1,
      'print_side2'        => $print_side2,
      'bleed'              => $bleed,
      'pages'              => $pages,
      'stock'              => $text_stock,
      'report_description' => $component_report_description,
      'count_color_inserts' => $count_color_inserts,
    ], $text_component_o_fields));
    // Takes all of the variables and calculates values for the `production_component` row.
    $data2 = $this->componentConfig(
      $this->document, $print_side1, $print_side2, $size, $pages, $finish, $component_type,
      $type, $quantity, $bleed, $text_stock, $component_count, $text_component_o_fields['is_sequential'],
      $text_component_o_fields['count_imprints'], $count_color_inserts, $attrs
    );
    // Include the new array the returned elements from the componentConfig first pass
    $data = array_merge($data, $data2);
    $production_component_row = (new ProductionComponent)->intersectFillables($data);
    $text_component->first()->production()->update($production_component_row);
    $documentData = $this->documentConfig(
      $data['offline_bind_required'], $data['text_cut_required'], $data['cover_cut_required'],
      $data['text_run_sheets'], $data['cover_run_sheets'], $finish, $quantity, $type, $pages,
      $data['text_print_hours'], $data['cover_print_hours'], $data['text_print_cost'],
      $data['cover_print_cost'], $data['text_component_print_sell'],
      $data['cover_component_print_sell'], $data['text_component_weight'],
      $data['cover_component_weight'], $data['run_sheet_sqin'], $data['product_sqin'], $attrs
    );
    $this->document->production()->first()->update($documentData);
    $this->synchronizeAlertsAfterUpdate($this->document, $request);
    return $this->document;
  }

  protected function startFolderUpdateLogic($request, $attrs = []): Document {
    $all_request_fields = ($request instanceof Request)
      ? $request->all() : $request;
    $size            = $request['size'] ?? $request->input('size');
    $finish          = $request['finish'] ?? $request->input('finish');
    $quantity        = $request['quantity'] ?? $request->input('quantity');
    $name            = $request['name'] ?? $request->input('name');
    $description     = $request['description'] ?? $request->input('description');
    $bleed           = $request['bleed'] ?? $request->input('bleed');
    $stock           = $request['text_stock'] ?? $request->input('text_stock');
    $print_side1     = $request['print_side1'] ?? $request->input('print_side1');
    $print_side2     = $request['print_side2'] ?? $request->input('print_side2');
    $pages           = 4;
    $data            = [];
    $type            = 'Folder';
    $component_type  = 'Text';
    $component_count = 1;
    $is_digital_only = (bool) json_decode($request['is_digital_only'] ?? $request->input('is_digital_only') ?: '0');
    // convert document type short name (e.g. 'MD&A' to full name)
    $dtn = DocumentTypeName::where('short', $name)->get()->first();
    // Determine Self Or Plus Cover
    $report_description = PrintReport::buildDocumentDescription($dtn->name, $description, $type, $size, $finish);
    $document_user_meta = [
      'last_modified_by' => $request['user_id'] ?? $request->user()->id
    ];
    $this->document->update(array_merge(
      $all_request_fields,
      $document_user_meta,
      ['specification' => ''],
      compact('report_description', 'type', 'is_digital_only'),
      ['active_rate_set_id' => $attrs['active_rate_set_id']]
    ));
    $component_report_description = PrintReport::buildComponentDescription(
      $component_count, $component_type, $pages, $print_side1, $print_side2, $bleed, $stock
    );
    $component = $this->document->components->first();
    $component->update([
      'type'               => $component_type,
      'print_side1'        => $print_side1,
      'print_side2'        => $print_side2,
      'bleed'              => $bleed,
      'pages'              => $pages,
      'stock'              => $stock,
      'report_description' => $component_report_description,
    ]);
    $data = $this->componentConfig(
      $this->document,
      $print_side1, $print_side2, $size, $pages, $finish, $component_type, $type,
      $quantity, $bleed, $stock, $component_count, 0, 0, 0, $attrs
    );
    $component->production->update($data);
    $documentData = $this->documentConfig(
      $data['offline_bind_required'], $data['text_cut_required'], $data['cover_cut_required'],
      $data['text_run_sheets'], $data['cover_run_sheets'], $finish, $quantity, $type, $pages,
      $data['text_print_hours'], $data['cover_print_hours'], $data['text_print_cost'],
      $data['cover_print_cost'], $data['text_component_print_sell'], $data['cover_component_print_sell'],
      $data['text_component_weight'], $data['cover_component_weight'], $data['run_sheet_sqin'],
      $data['product_sqin'], $attrs
    );
    $this->document->production->update($documentData);
    $this->synchronizeAlertsAfterUpdate($this->document, $request);
    return $this->document;
  }

  protected function startMultiSheetUpdateLogic($request, array $attrs = []): Document {
    $all_request_fields = ($request instanceof Request)
      ? $request->all() : $request;
    $size            = $request['size'] ?? $request->input('size');
    $finish          = $request['finish'] ?? $request->input('finish');
    $quantity        = $request['quantity'] ?? $request->input('quantity');
    $name            = $request['name'] ?? $request->input('name');
    $description     = $request['description'] ?? $request->input('description');
    $bleed           = $request['bleed'] ?? $request->input('bleed');
    $stock           = $request['text_stock'] ?? $request->input('text_stock');
    $print_side1     = $request['print_side1'] ?? $request->input('print_side1');
    $print_side2     = $request['print_side2'] ?? $request->input('print_side2');
    $pages           = $request['text_pages'] ?? $request->input('text_pages');
    $data            = [];
    $type            = 'Multi Sheet';
    $component_type  = 'Text';
    $component_count = 1;
    $is_digital_only = (bool) json_decode($request['is_digital_only'] ?? $request->input('is_digital_only') ?: '0');
    /**
    * Check if both sides print. if so pages must be even number. Check and adjust if not even
    * @todo this might need to go in function..we put similar function for books in report description
    */
    if ($print_side1 !== 'none' && $print_side2 !== 'none') {
      $mod = ($pages % 2);
      if ($mod > 0) {
        $pages += 1;
      }
    }
    // convert document type short name (e.g. 'MD&A' to full name)
    $dtn = DocumentTypeName::where('short', $name)->get()->first();
    // Determine Self Or Plus Cover
    $report_description = PrintReport::buildDocumentDescription($dtn->name, $description, $type, $size, $finish);
    $document_user_meta = [
      'last_modified_by' => $request['user_id'] ?? $request->user()->id
    ];
    $this->document->update(array_merge(
      $all_request_fields,
      $document_user_meta,
      ['specification' => ''],
      compact('report_description', 'type', 'is_digital_only'),
      ['active_rate_set_id' => $attrs['active_rate_set_id']]
    ));
    $component_report_description = PrintReport::buildComponentDescription(
      $component_count, $component_type, $pages, $print_side1, $print_side2, $bleed, $stock
    );
    $component = $this->document->components->first();
    $component->update([
      'type'               => $component_type,
      'print_side1'        => $print_side1,
      'print_side2'        => $print_side2,
      'bleed'              => $bleed,
      'pages'              => $pages,
      'stock'              => $stock,
      'report_description' => $component_report_description,
    ]);
    $data = $this->componentConfig(
      $this->document,
      $print_side1, $print_side2, $size, $pages, $finish, $component_type, $type,
      $quantity, $bleed, $stock, $component_count, 0, 0, 0, $attrs
    );
    $component->production->update($data);
    $data['cover_cut_required']     = 0;
    $data['cover_run_sheets']       = 0;
    $data['cover_component_weight'] = 0;
    $data['cover_print_hours']      = 0;
    $data['cover_print_cost']       = 0;
    $component_count                = 1;
    $documentData = $this->documentConfig(
      $data['offline_bind_required'], $data['text_cut_required'], $data['cover_cut_required'],
      $data['text_run_sheets'], $data['cover_run_sheets'], $finish, $quantity, $type, $pages,
      $data['text_print_hours'], $data['cover_print_hours'], $data['text_print_cost'],
      $data['cover_print_cost'], $data['text_component_print_sell'], $data['cover_component_print_sell'],
      $data['text_component_weight'], $data['cover_component_weight'], $data['run_sheet_sqin'],
      $data['product_sqin'], $attrs
    );
    $this->document->production->update($documentData);
    $this->synchronizeAlertsAfterUpdate($this->document, $request);
    return $this->document;
  }

  protected function startSingleSheetUpdateLogic($request, array $attrs = []): Document {
    $all_request_fields = ($request instanceof Request)
      ? $request->all() : $request;
    $size              = $request['size'] ?? $request->input('size');
    $finish            = $request['finish'] ?? $request->input('finish');
    $quantity          = $request['quantity'] ?? $request->input('quantity');
    $name              = $request['name'] ?? $request->input('name');
    $description       = $request['description'] ?? $request->input('description');
    $bleed             = $request['bleed'] ?? $request->input('bleed');
    $stock             = $request['text_stock'] ?? $request->input('text_stock');
    $print_side1       = $request['print_side1'] ?? $request->input('print_side1');
    $print_side2       = $request['print_side2'] ?? $request->input('print_side2');
    $data              = [];
    $type              = 'Single Sheet';
    $component_type    = 'Text';
    $component_count   = 1;
    $is_digital_only   = (bool) json_decode($request['is_digital_only'] ?? $request->input('is_digital_only') ?: '0');

    // convert document type short name (e.g. 'MD&A' to full name)
    $dtn = DocumentTypeName::where('short', $name)->get()->first();
    // Determine Self Or Plus Cover
    $report_description = PrintReport::buildDocumentDescription($dtn->name, $description, $type, $size, $finish);
    $document_user_meta = array(
      'last_modified_by' => $request['user_id'] ?? $request->user()->id
    );
    $this->document->update(array_merge(
      $all_request_fields,
      $document_user_meta,
      ['specification' => ''],
      compact('report_description', 'type', 'is_digital_only'),
      ['active_rate_set_id' => $attrs['active_rate_set_id']]
    ));
    $pages = ($print_side2 == 'none') ? 1 : 2;
    $component_report_description = PrintReport::buildComponentDescription(
      $component_count, $component_type, $pages, $print_side1, $print_side2, $bleed, $stock
    );
    $component = $this->document->components->first();
    $component->update([
      'type'               => $component_type,
      'print_side1'        => $print_side1,
      'print_side2'        => $print_side2,
      'bleed'              => $bleed,
      'pages'              => $pages,
      'stock'              => $stock,
      'report_description' => $component_report_description,
    ]);
    $data = $this->componentConfig(
      $this->document, $print_side1, $print_side2, $size, $pages, $finish, $component_type,
      $type, $quantity, $bleed, $stock, $component_count, 0, 0, 0, $attrs
    );
    $component->production->update($data);
    $data['cover_cut_required']     = 0;
    $data['cover_run_sheets']       = 0;
    $data['cover_component_weight'] = 0;
    $data['cover_print_hours']      = 0;
    $data['cover_print_cost']       = 0;
    $component_count                = 1;
    $documentData = $this->documentConfig(
      $data['offline_bind_required'], $data['text_cut_required'], $data['cover_cut_required'],
      $data['text_run_sheets'], $data['cover_run_sheets'], $finish, $quantity, $type, $pages,
      $data['text_print_hours'], $data['cover_print_hours'], $data['text_print_cost'],
      $data['cover_print_cost'], $data['text_component_print_sell'], $data['cover_component_print_sell'],
      $data['text_component_weight'], $data['cover_component_weight'], $data['run_sheet_sqin'],
      $data['product_sqin'], $attrs
    );
    $this->document->production->update($documentData);
    $this->synchronizeAlertsAfterUpdate($this->document, $request);
    return $this->document;
  }

  protected function startCustomLogic($request, $attrs = [], $estimate): Document 
  {
    $document_id     = intval($request->input('document_id') ?? 0);
    $quantity        = $request->input('quantity');
    $name            = $request->input('name');
    $description     = $request->input('description');
    $specification   = $request->input('specification');
    $type            = 'Custom';
    $dtn = DocumentTypeName::where('short', $name)->get()->first();
    $report_description = "$name - Custom";
    $document_user_meta = [
      'created_by' => $request->user()->id,
      'last_modified_by' => $request->user()->id
    ];
    $fields = array_merge($request->all(), $document_user_meta, compact('report_description', 'type'), [
      'is_pending' => 1,
      'is_custom' => 1,
      'active_rate_set_id' => $attrs['active_rate_set_id'],
      'is_digital_only' => (bool) json_decode($request->input('is_digital_only') ?: '0'),
      'size' => '',
      'finish' => '',
    ]);
    if (empty($document_id)) { // Create
      $document = $this->estimate->documents()->create($fields);
    } else { // Update
      $document = Document::find($document_id);
      foreach($fields as $field => $value) {
        if (in_array($field, ['_method', '_token', 'is_custom_document', 'document_id', 'type'])) continue;
        $document->{$field} = $value;
      }
      $document->save();
    }
    $estimate->addAlert(self::CUSTOM_DOCUMENT_PENDING_ALERT);
    return $document;
  }

  protected function startFolderLogic($request, array $attrs = []): Document 
  {
    $size            = $request->input('size');
    $finish          = $request->input('finish');
    $quantity        = $request->input('quantity');
    $name            = $request->input('name');
    $description     = $request->input('description');
    $bleed           = $request->input('bleed');
    $stock           = $request->input('text_stock');
    $print_side1     = $request->input('print_side1');
    $print_side2     = $request->input('print_side2');
    $pages           = 4;
    $data            = [];
    $type            = 'Folder';
    $component_type  = 'Text';
    $component_count = 1;
    $is_digital_only = (bool) json_decode($request->input('is_digital_only') ?: '0');
    // convert document type short name (e.g. 'MD&A' to full name)
    $dtn = DocumentTypeName::where('short', $name)->get()->first();
    // Determine Self Or Plus Cover
    $report_description = PrintReport::buildDocumentDescription($dtn->name, $description, $type, $size, $finish);
    $document_user_meta = array(
      'created_by' => $request->user()->id,
      'last_modified_by' => $request->user()->id
    );
    $document = $this->estimate->documents()->create(array_merge(
      $request->all(),
      $document_user_meta,
      ['specification' => ''],
      compact('report_description', 'type', 'is_digital_only'),
      ['active_rate_set_id' => $attrs['active_rate_set_id']]
    ));
    $component_report_description = PrintReport::buildComponentDescription(
      $component_count, $component_type, $pages, $print_side1, $print_side2, $bleed, $stock
    );
    $component = $document->components()->create([
      'type'               => $component_type,
      'print_side1'        => $print_side1,
      'print_side2'        => $print_side2,
      'bleed'              => $bleed,
      'pages'              => $pages,
      'stock'              => $stock,
      'report_description' => $component_report_description,
    ]);
    $data = $this->componentConfig(
      $document, $print_side1, $print_side2, $size, $pages, $finish, $component_type,
      $type, $quantity, $bleed, $stock, $component_count, 0, 0, 0, $attrs
    );
    $component->production()->create($data);
    $documentData = $this->documentConfig(
      $data['offline_bind_required'], $data['text_cut_required'], $data['cover_cut_required'],
      $data['text_run_sheets'], $data['cover_run_sheets'], $finish, $quantity, $type, $pages,
      $data['text_print_hours'], $data['cover_print_hours'], $data['text_print_cost'],
      $data['cover_print_cost'], $data['text_component_print_sell'], $data['cover_component_print_sell'],
      $data['text_component_weight'], $data['cover_component_weight'], $data['run_sheet_sqin'],
      $data['product_sqin'], $attrs
    );
    $document->production()->create($documentData);
    return $document;
  }

  protected function startSingleSheetLogic($request, array $attrs = []): Document 
  {
    $size              = $request->input('size');
    $finish            = $request->input('finish');
    $quantity          = $request->input('quantity');
    $name              = $request->input('name');
    $description       = $request->input('description');
    $bleed             = $request->input('bleed');
    $stock             = $request->input('text_stock');
    $print_side1       = $request->input('print_side1');
    $print_side2       = $request->input('print_side2');
    $data              = [];
    $type              = 'Single Sheet';
    $component_type    = 'Text';
    $component_count   = 1;
    $is_digital_only = (bool) json_decode($request->input('is_digital_only') ?: '0');
    // convert document type short name (e.g. 'MD&A' to full name)
    $dtn = DocumentTypeName::where('short', $name)->get()->first();
    // Determine Self Or Plus Cover
    $report_description = PrintReport::buildDocumentDescription($dtn->name, $description, $type, $size, $finish);
    $document_user_meta = [
      'created_by' => $request->user()->id,
      'last_modified_by' => $request->user()->id
    ];
    $document = $this->estimate->documents()->create(array_merge(
      $request->all(), $document_user_meta, compact('report_description', 'type', 'is_digital_only'),
      ['specification' => ''],
      ['active_rate_set_id' => $attrs['active_rate_set_id']]
    ));
    $pages = ($print_side2 == 'none') ? 1 : 2;
    $component_report_description = PrintReport::buildComponentDescription(
      $component_count, $component_type, $pages, $print_side1, $print_side2, $bleed, $stock
    );
    $component = $document->components()->create([
      'type'               => $component_type,
      'print_side1'        => $print_side1,
      'print_side2'        => $print_side2,
      'bleed'              => $bleed,
      'pages'              => $pages,
      'stock'              => $stock,
      'report_description' => $component_report_description,
    ]);
    $data = $this->componentConfig(
      $document, $print_side1, $print_side2, $size, $pages, $finish, $component_type,
      $type, $quantity, $bleed, $stock, $component_count, 0, 0, 0, $attrs
    );
    $comp_prod = $component->production()->create($data);
    $data['cover_cut_required']     = 0;
    $data['cover_run_sheets']       = 0;
    $data['cover_component_weight'] = 0;
    $data['cover_print_hours']      = 0;
    $data['cover_print_cost']       = 0;
    $component_count                = 1;
    $documentData = $this->documentConfig(
      $data['offline_bind_required'], $data['text_cut_required'], $data['cover_cut_required'],
      $data['text_run_sheets'], $data['cover_run_sheets'], $finish, $quantity, $type, $pages,
      $data['text_print_hours'], $data['cover_print_hours'], $data['text_print_cost'],
      $data['cover_print_cost'], $data['text_component_print_sell'], $data['cover_component_print_sell'],
      $data['text_component_weight'], $data['cover_component_weight'], $data['run_sheet_sqin'],
      $data['product_sqin'], $attrs
    );
    $doc_prod = $document->production()->create($documentData);
    return $document;
  }

  protected function startMultiSheetLogic($request, array $attrs = []): Document 
  {
    $size            = $request->input('size');
    $finish          = $request->input('finish');
    $quantity        = $request->input('quantity');
    $name            = $request->input('name');
    $description     = $request->input('description');
    $bleed           = $request->input('bleed');
    $stock           = $request->input('text_stock');
    $print_side1     = $request->input('print_side1');
    $print_side2     = $request->input('print_side2');
    $pages           = $request->input('text_pages');
    $data            = [];
    $type            = 'Multi Sheet';
    $component_type  = 'Text';
    $component_count = 1;
    $is_digital_only = (bool) json_decode($request->input('is_digital_only') ?: '0');
    if ($print_side1 !== 'none' && $print_side2 !== 'none' && $pages % 2 > 0) {
      $pages += 1;
    }
    // convert document type short name (e.g. 'MD&A' to full name)
    $dtn = DocumentTypeName::where('short', $name)->get()->first();
    // Determine Self Or Plus Cover
    $report_description = PrintReport::buildDocumentDescription($dtn->name, $description, $type, $size, $finish);
    $document_user_meta = [
      'created_by' => $request->user()->id,
      'last_modified_by' => $request->user()->id
    ];
    $document = $this->estimate->documents()->create(array_merge(
      $request->all(),
      $document_user_meta,
      ['specification' => ''],
      compact('report_description', 'type', 'is_digital_only'),
      ['active_rate_set_id' => $attrs['active_rate_set_id']]
    ));
    $component_report_description = PrintReport::buildComponentDescription(
      $component_count, $component_type, $pages, $print_side1, $print_side2, $bleed, $stock
    );
    $component = $document->components()->create([
      'type'               => $component_type,
      'print_side1'        => $print_side1,
      'print_side2'        => $print_side2,
      'bleed'              => $bleed,
      'pages'              => $pages,
      'stock'              => $stock,
      'report_description' => $component_report_description,
    ]);
    $data = $this->componentConfig(
      $document, $print_side1, $print_side2, $size, $pages, $finish, $component_type,
      $type, $quantity, $bleed, $stock, $component_count, 0, 0, 0, $attrs
    );
    $component->production()->create($data);
    list(
      $data['cover_cut_required'], $data['cover_run_sheets'], $data['cover_component_weight'], $data['cover_print_hours'], $data['cover_print_cost']
    ) = [0, 0, 0, 0, 0];
    $component_count                = 1;
    $document_data = $this->documentConfig(
      $data['offline_bind_required'], $data['text_cut_required'], $data['cover_cut_required'],
      $data['text_run_sheets'], $data['cover_run_sheets'], $finish, $quantity, $type, $pages,
      $data['text_print_hours'], $data['cover_print_hours'], $data['text_print_cost'],
      $data['cover_print_cost'], $data['text_component_print_sell'], $data['cover_component_print_sell'],
      $data['text_component_weight'], $data['cover_component_weight'], $data['run_sheet_sqin'],
      $data['product_sqin'], $attrs
    );
    $document->production()->create($document_data);
    return $document;
  }

  protected function startBookLogic($request, $attrs = []): Document 
  {
    $all_request_fields = ($request instanceof Request) ? $request->all() : (array) $request;
    // $document_type = DocumentType::find($request->input('document_type_id'));
    $size              = $request->input('size');
    $finish            = $request->input('finish');
    $quantity          = $request->input('quantity');
    $name              = $request->input('name'); // document short name
    $description       = $request->input('description');
    $cover_type        = $request->input('cover_type');
    $cover_stock       = $request->input('cover_stock');
    $cover_print_side1 = $request->input('print_side1');
    $cover_print_side2 = $request->input('print_side2');
    $bleed             = $request->input('bleed');
    $text_bleed        = $request->input('text_bleed');
    $text_stock = $stock = $request->input('text_stock');
    $print_side1       = $request->input('text_print');
    $print_side2       = $request->input('text_print'); // for books - text always duplex so s2 col = s1 col
    $count_imprints    = $request->input('count_imprints');
    $count_color_inserts = $request->input('count_color_inserts');
    $is_sequential     = (bool) json_decode($request['is_sequential'] ?? $request->input('is_sequential') ?: '0');
    $is_digital_only   = (bool) json_decode($request->input('is_digital_only') ?: '0');
    $pages             = 0;
    $data              = [];
    // For new option `cover_type` = 'self', cover stock inherits from text detail.
    if ($cover_type === 'self') {
      $cover_stock = 'none';
      $cover_print_side1 = 'none';
      $cover_print_side2 = 'none';
      $bleed = 0;
    }
    // convert document type short name (e.g. 'MD&A' to full name)
    $dtn = DocumentTypeName::where('short', $name)->get()->first();
    // Determine Self Or Plus Cover
    list($type, $component_count) = $this->getDocumentTypeAndComponentCount($cover_stock, $cover_print_side1);
    $report_description = PrintReport::buildDocumentDescription($dtn->name, $description, $type, $size, $finish);
    $document_user_meta = [
      'created_by' => $request->user()->id,
      'last_modified_by' => $request->user()->id
    ];

    $document = $this->estimate->documents()->create(array_merge(
      $all_request_fields,
      ['specification' => ''], // unused field 2026?
      $document_user_meta,
      compact('cover_type', 'report_description', 'type', 'is_digital_only'),
      ['active_rate_set_id' => $attrs['active_rate_set_id']]
    ));
    // if cover exists insert cover component
    if ($component_count == 2) {
      $component_type = 'Cover';
      $pages = !!($cover_stock === '6 pg cover on 8pt Ctd Cover') ? 6 : 4;
      $component_report_description = PrintReport::buildComponentDescription(
        $component_count, $component_type, $pages, $cover_print_side1, $cover_print_side2, $bleed, $cover_stock,
        $is_sequential, $count_color_inserts, $count_imprints
      );
      $cover_component = [
        'type'               => $component_type,
        'print_side1'        => $cover_print_side1,
        'print_side2'        => $cover_print_side2,
        'bleed'              => $bleed,
        'pages'              => $pages,
        'stock'              => $cover_stock,
        'report_description' => $component_report_description,
        'count_imprints'     => $count_imprints,
        'is_sequential'      => $is_sequential,
      ];
      $component[] = $document->components()->create($cover_component);
      $data = $this->componentConfig(
        $document, $cover_print_side1, $cover_print_side2, $size, $pages, $finish, $component_type,
        $type, $quantity, $bleed, $cover_stock, $component_count, $is_sequential, $count_imprints, 0, $attrs
      );
      $data['cover_cut_required']     = $data['cut_required'];
      $data['cover_run_sheets']       = $data['run_sheets'];
      $data['cover_component_weight'] = $data['component_weight'];
      $data['cover_print_hours']      = $data['print_hours'];
      $data['cover_print_cost']       = $data['print_cost'];
      $component_count                = $data['document_component_count'];
      $productionComponent[] = $component[0]->production()->create($data);
      $cover_component_id = $component[0]->id;
    }
    // process text component
    $component_type = 'Text';
    $pages          = ($request->input('text_pages') - $pages); // text pages is pages less cover pages
    /**
    * Saddlestitch pages must be a multiple of 4. Perfet Bind text pages must be multiple of 2
    * Check if multiple of 4 for Saddlestitch and if not, round up to next multiple of 4
    * Check if multiple of 2 for Perfect bind text pages and if not, round up to next multiple of 2
    */
    $pages = PrintReport::applyPagesModuloBooks($finish, $pages);
    $bleed = 0; // bleed not offered in version one for text of books
    $component_report_description = PrintReport::buildComponentDescription(
      $component_count, $component_type, $pages, $print_side1, $print_side2, $bleed, $stock,
      $is_sequential, $count_color_inserts, $count_imprints
    );
    $text_component = [
      'type'               => $component_type,
      'print_side1'        => $print_side1,
      'print_side2'        => $print_side2,
      'bleed'              => $bleed,
      'pages'              => $pages,
      'stock'              => $stock,
      'report_description' => $component_report_description,
      'count_color_inserts' => $count_color_inserts,
      'is_sequential'      => 0,
      'count_imprints'     => 0,
    ];
    if ($cover_type === 'self') {
      $text_component['is_sequential'] = $is_sequential;
      $text_component['count_imprints'] = $count_imprints;
    }
    $component[] = $document->components()->create($text_component);
    $data2 = $this->componentConfig(
      $document, $print_side1, $print_side2, $size, $pages, $finish, $component_type,
      $type, $quantity, $bleed, $stock, $component_count,
      $text_component['is_sequential'], $text_component['count_imprints'], $count_color_inserts, $attrs
    );
    $data = array_merge($data, $data2);
    end($component)->production()->create($data);
    $documentData = $this->documentConfig(
      $data['offline_bind_required'], $data['text_cut_required'], $data['cover_cut_required'], $data['text_run_sheets'], $data['cover_run_sheets'], $finish, $quantity, $type, $pages, $data['text_print_hours'], $data['cover_print_hours'], $data['text_print_cost'], $data['cover_print_cost'],
      $data['text_component_print_sell'], $data['cover_component_print_sell'], $data['text_component_weight'], $data['cover_component_weight'], $data['run_sheet_sqin'], $data['product_sqin'], $attrs
    );
    $document->production()->create($documentData);
    return $document;
  }

  protected function getDocumentTypeAndComponentCount(string $coverStock, string $coverPrintSide): array 
  {
    if ($coverStock == 'none' && $coverPrintSide == 'none') {
     $type = 'Self Cover Book';
     $componentCount = 1;
    } else {
      $type = 'Plus Cover Book';
      $componentCount = 2;
    }
    return [$type, $componentCount];
  }

  protected function documentConfig(
    $offline_bind_required, $text_cut_required, $cover_cut_required, $text_run_sheets,
    $cover_run_sheets, $document_finish, $document_quantity, $document_type, $pages,
    $text_print_hours, $cover_print_hours, $text_print_cost, $cover_print_cost,
    $text_component_print_sell, $cover_component_print_sell, $text_component_weight,
    $cover_component_weight, $run_sheet_sqin, $product_sqin, $attrs = []): array 
  {
    list($print_hours, $cut_hours, $fold_hours, $saddlestitch_hours, $perfect_bind_prep_hours, $perfect_bind_hours, $bind_hours) = [0, 0, 0, 0, 0, 0, 0];
    list($print_cost, $cut_cost, $fold_cost, $saddlestitch_cost, $perfect_bind_prep_cost, $perfect_bind_cost, $bind_cost) = [0, 0, 0, 0, 0, 0, 0];
    list($print_sell, $cut_sell, $fold_sell, $stitch_sell, $perfect_bind_sell, $bind_sell) = [0, 0, 0, 0, 0, 0];

    $document_setup_sell = PrintSell::printDocSetUpSellRate([
      'document_setup_rate' => SellRate::getItemRate('document_setup', $attrs['active_rate_set_id'])
    ]);

    if ($offline_bind_required || $text_cut_required  || $cover_cut_required) {
      if ($text_cut_required || $cover_cut_required) {
        $cut_hours = PrintBind::cutHours($text_cut_required, $text_run_sheets, $cover_cut_required, $cover_run_sheets);
        $cut_cost = PrintBind::cutCost($cut_hours,$cover_cut_required,$text_cut_required);
        $cut_sell = PrintBind::cutSell($document_quantity, $cover_cut_required, $text_cut_required, [
          'cut_setup' => SellRate::getItemRate('cut_setup', $attrs['active_rate_set_id']),
          'cut_run_rate' => SellRate::getItemRate('cut_run', $attrs['active_rate_set_id'])
        ]);
      }

      if (boolval($offline_bind_required)) { // checks to see if off line binding other than cutting is required
        if (in_array($document_finish, ['Fold(1/2)', '4pg Folder', '4pg Folder-1/4 Fold', '4pg Folder-Letter Fold', 'Letter Fold'])) {
          $fold_hours = PrintBind::foldHours($document_finish, $run_sheet_sqin, $document_quantity);
          $fold_cost = PrintBind::foldCost($fold_hours);
          $fold_sell = PrintBind::foldSell($document_finish, $document_quantity, [
            'fold_setup' => SellRate::getItemRate('fold_setup', $attrs['active_rate_set_id']),
            'fold_run_rate' => SellRate::getItemRate(PrintBind::sellItem_foldSellRunRate($document_finish), $attrs['active_rate_set_id'])
          ]);
        }
        elseif ($document_finish == 'Perfect Bind') {
          $pb_cut_cover_hours      = PrintBind::pbCutCoverHours();
          $pb_cut_block_hours      = PrintBind::pbCutBlockHours($document_quantity);
          $pb_separate_block_hours = PrintBind::pbSeparateBlockHours($document_quantity);
          $pb_run_hours            = PrintBind::pbRunHours($document_quantity, $pages, $pb_cut_cover_hours);
          $perfect_bind_hours = ($pb_cut_cover_hours + $pb_cut_block_hours + $pb_separate_block_hours + $pb_run_hours);
          $perfect_bind_cost = PrintBind::perfectBindCost($pb_run_hours, $pb_cut_cover_hours, $pages, $document_quantity);
          $perfect_bind_sell = PrintBind::perfectBindSell($perfect_bind_cost, $document_quantity, $pages, [
            'pb_taylor_mu' => SellRate::getItemRate('pb_taylor_mu', $attrs['active_rate_set_id'])
          ]);
        }
      }
    }

    if ($document_finish == 'Saddlestitch') {
      $saddlestitch_hours = PrintBind::stitchHours($pages, $document_type, $product_sqin, $document_quantity);
      $saddlestitch_cost = PrintBind::stitchCost($saddlestitch_hours);
      // 'saddlestitch_inline' | 'saddlestitch_offline' | 'cornerstitch_inline'
      $curve_map_all = Curve::getNamedMap($attrs['company_id'])->toArray();
      $stitch_sell_item_description = PrintBind::sellItem_stitchSellRunRate($pages, $document_finish, $curve_map_all);
      $stitch_sell = PrintBind::stitchSell($document_quantity, $pages, [
        'stitch_run_rate' => SellRate::getItemRate($stitch_sell_item_description, $attrs['active_rate_set_id'])
      ]);
      $stitch_sell += SellRate::getItemRate('saddlestitch_setup', $attrs['active_rate_set_id'], 0);
    }

    if ($document_type === 'Multi Sheet' && $document_finish === 'Corner Stitch') {
      $stitch_sell = PrintBind::stitchSell($document_quantity, $pages, [
        'stitch_run_rate' => SellRate::getItemRate('cornerstitch_inline', $attrs['active_rate_set_id'])
      ]);
    }

    $print_hours = ($text_print_hours + $cover_print_hours);
    $print_cost = ($text_print_cost + $cover_print_cost);
    $print_sell = ($text_component_print_sell + $cover_component_print_sell);
    $bind_hours = ($cut_hours + $fold_hours + $saddlestitch_hours + $perfect_bind_hours);
    $bind_cost = ($cut_cost + $fold_cost + $saddlestitch_cost + $perfect_bind_cost);
    $bind_sell = ($cut_sell + $fold_sell + $stitch_sell + $perfect_bind_sell);
    $hours = $print_hours + $bind_hours;
    $cost = ($print_cost + $bind_cost);
    $sell = ($print_sell + $bind_sell + $document_setup_sell);
    $weight = ($text_component_weight + $cover_component_weight);

    $hour_range = ProductionHours::hourRange($print_hours, $cut_hours, $fold_hours, $saddlestitch_hours, $perfect_bind_hours);

    $saddlestitch_sell = 0; // fix mysql 'undefined' bug 2026. not sure what happened to this variable 

    return compact(
      'print_hours', 'print_cost', 'print_sell', 'fold_hours', 'fold_cost', 'fold_sell',
      'cut_hours', 'cut_cost', 'cut_sell', 'bind_hours', 'bind_cost', 'bind_sell', 'hours',
      'hour_range', 'cost', 'sell', 'weight', 'perfect_bind_sell', 'perfect_bind_cost',
      'perfect_bind_hours', 'saddlestitch_hours', 'saddlestitch_cost', 'saddlestitch_sell'
    );
  }

  protected function componentConfig(
    Document $document, $print_side1, $print_side2, $size, $pages, $finish, $component_type,
    $document_type, $document_quantity, $bleed, $stock, $document_component_count,
    bool $is_sequential, int $count_imprints, int $count_color_inserts, $attrs = []
  ): array 
  {
    // determine if print simplex (1 side) or duplex (2 sides)
    $run_type = PrintConfig::runType($print_side2);

    // determine if black or colour device required
    $device_type = PrintConfig::deviceType($print_side1, $print_side2);

    // determines square inches of the finished product
    $product_sqin = PrintConfig::prodSqIn($size);

    // get Page count from user entry---then in some doc types resets it for calculation purposes
    $pages = PrintConfig::pages($pages, $run_type, $finish, $component_type, $document_type);

    // calculates impressions for this component
    $impressions = PrintConfig::impressions($product_sqin, $pages, $document_quantity, $finish, $attrs['curve_fields']);

    if ($impressions > floatval($attrs['curve_fields']['Alert_High_Impression_Count_Threshold'])) {
      $this->estimate->addAlert(static::HIGH_IMPRESSION_COUNT_ALERT);
    }

    // NEW January 2021 calculates $colour_insert_impressions for this component _NEW variable
    $colour_insert_impressions = PrintConfig::colourInsertImpressions($document_quantity, $count_color_inserts);

    // determines device to run on
    $curve_map_all = Curve::getNamedMap($attrs['company_id'])->toArray();
    $device = PrintConfig::device($device_type, $impressions, $document->is_digital_only, $count_color_inserts, $stock, $curve_map_all);

    if (($device_type === 'Colour' || $count_color_inserts > 0) && $device === 'TRIVOR') {
      $document->addDisplayMessage(static::COLOR_TRIVOR_PRICING_MESSAGE);
    } else {
      $document->removeDisplayMessage(static::COLOR_TRIVOR_PRICING_MESSAGE);
    }

    // determines size of sheet to run
    $run_sheet_sqin = PrintConfig::runShtSqIn($component_type, $product_sqin, $bleed, $finish, $device, $stock);

    // determines pages per run sheet
    $pages_per_run_sheet = PrintConfig::pgsPerRunSht($run_type, $run_sheet_sqin, $product_sqin);

    // determines if bindwaste and what % is required
    $bind_waste = PrintConfig::bindWaste($finish, $document_quantity, $curve_map_all);

    //determines # of run sheets required
    $run_sheets = PrintConfig::runSheets($pages, $pages_per_run_sheet, $document_quantity, $bind_waste);

    //determines # of black  & colour clicks to charge
    $black_clicks = PrintConfig::blackClicks($device, $run_sheets, $device_type, $run_type, $print_side1, $print_side2, $run_sheet_sqin);

    $colour_clicks = PrintConfig::colourClicks($run_sheets, $device_type, $run_type, $print_side1, $print_side2);

    $cut_required = PrintConfig::cutRequired($finish, $device, $product_sqin, $pages_per_run_sheet, $run_sheet_sqin, $pages, $bleed);

    // determines if off  line bindery is required Cover always false...text component only value used later
    $offline_bind_required = PrintConfig::offLineBind($finish, $component_type, $pages, $device);

    // gets the basis weight & Basis Size of the stock - needed for weight calculations
    // these two function are located in the 'PrintStock.php' file. */
    $basis_weight = PrintStock::basisWeight($stock); //
    $basis_size   = PrintStock::basisSize($stock); //gets the basis size of the stock-STOCK function
    $caliper      = PrintStock::caliper($stock);

    //calculates weight of component
    $component_weight  = PrintConfig::lbsPerUnit($run_type, $product_sqin, $pages, $basis_weight, $basis_size);
    $component_caliper = PrintConfig::caliper($caliper,$run_type,$pages);

    // creates a description to be used to look up sell price from sell_items table
    $sell_item_description = PrintConfig::sellItemDesc($stock, $run_sheet_sqin, $print_side1, $print_side2, $device, $impressions, $curve_map_all);

    // gets run speed of the print device -
    $print_run_speed = PrintHours::runSpeed($device,$run_sheet_sqin,$run_type);

    // calculates run hours for printing
    // this function is located in the print_hours_function.php' file */
    $print_hours = PrintHours::printRunHrs($offline_bind_required, $finish, $print_run_speed, $run_sheets, $device);

    $stock_cost  = PrintStock::stockCost($stock, $run_sheet_sqin, $run_sheets, $device);
    $click_cost  = PrintCost::clickCost($black_clicks, $colour_clicks, $device);
    $labour_cost = PrintCost::labourCost($print_hours);

    // total the print cost elements
    $print_cost = round($stock_cost + $click_cost + $labour_cost,2);

    if ($device == '495') {
      $print_item_rate = PrintSell::print495SellRate($impressions, $sell_item_description, $device_type, [
        'min_item_rate' => SellRate::getItemRate($sell_item_description . '_495_MIN', $attrs['active_rate_set_id']),
        'max_item_rate' => SellRate::getItemRate($sell_item_description . '_495_MAX', $attrs['active_rate_set_id'])
      ], $curve_map_all);
    } else {
      $print_item_rate = PrintSell::printSellRate($sell_item_description, [
        'print_item_rate' => SellRate::getItemRate($sell_item_description, $attrs['active_rate_set_id'])
      ]);
    }

    $print_setup_sell = PrintSell::printSetUpSellRate([
      'print_setup_rate' => SellRate::getItemRate('print_setup', $attrs['active_rate_set_id'])
    ]);

    if ($document_component_count == 1) {
      $cover_cut_required = false;
      $cover_run_sheets = 0;
      $cover_component_weight = 0;
      $cover_print_hours = 0;
      $cover_print_cost = 0;
      $cover_component_print_sell = 0;
    }

    if ($component_type === 'Cover') {
      // Trivor Logic included 2020-10-09
      if ($device === 'TRIVOR') {
        $component_sell = $print_setup_sell + round($print_item_rate * $impressions, 2);
      } else {
        $component_sell = $print_setup_sell + round($print_item_rate * $run_sheets, 2);
      }

      $component_sell += PrintSell::csImprintSequenceSetup($attrs['print_o_rates'], $count_imprints, $document_quantity, $is_sequential);

      $cover_cut_required          = $cut_required;
      $cover_run_sheets            = $run_sheets;
      $cover_component_weight      = $component_weight;
      $cover_print_hours           = $print_hours;
      $cover_print_cost            = $print_cost;
      $cover_component_print_sell  = $component_sell;
      // there is a good chance this requires refactoring, requires Ian
      $text_cut_required = 0; // 2026 patch
      $text_run_sheets = 0;
      $text_component_weight = 0;
      $text_print_hours = 0;
      $text_print_cost = 0;
      $text_component_print_sell = 0;
      $cover_component_print_sell = $component_sell;
    }
    elseif ($component_type === 'Text') {
      if ($device === 'TRIVOR') {
        $component_sell = $print_setup_sell + round($print_item_rate * ($impressions - $colour_insert_impressions), 2);
        $component_sell += round(SellRate::getTypeRate($attrs['print_o_rates'], 'Colour_Insert_Trivor')
          * $colour_insert_impressions, 2);
      } else {
        $component_sell = $print_setup_sell + round($print_item_rate * $run_sheets, 2);
        $component_sell += round(SellRate::getTypeRate($attrs['print_o_rates'], 'Colour_Insert_Digital')
          * $colour_insert_impressions, 2);
      }

      if ($document_component_count === 1) {
        $component_sell += PrintSell::csImprintSequenceSetup($attrs['print_o_rates'],
          $count_imprints, $document_quantity, $is_sequential);
      }

      $text_cut_required          = $cut_required;
      $text_run_sheets            = $run_sheets;
      $text_component_weight      = $component_weight;
      $text_print_hours           = $print_hours;
      $text_print_cost            = $print_cost;
      $text_component_print_sell  = $component_sell;
      // there is a good chance this requires refactoring, requires Ian
      $cover_component_print_sell = 0; // 2026 patch
      $cover_cut_required = 0;
      $cover_run_sheets = 0;
      $cover_component_weight = 0;
      $cover_print_hours = 0;
      $cover_print_cost = 0;
    }

    return compact(
      'run_type', 'device', 'run_sheet_sqin', 'run_sheets', 'stock_cost', 'impressions',
      'black_clicks', 'colour_clicks', 'click_cost', 'component_weight', 'component_caliper',
      'print_run_speed', 'labour_cost', 'print_hours', 'print_cost', 'cut_required',
      'offline_bind_required', 'sell_item_description', 'text_cut_required', 'text_run_sheets',
      'text_component_weight', 'text_print_hours', 'text_print_cost', 'text_component_print_sell',
      'document_component_count', 'cover_component_print_sell', 'cover_cut_required', 'cover_run_sheets',
      'cover_component_weight', 'cover_print_hours', 'cover_print_cost', 'component_sell', 'product_sqin'
    );
  }

  // The `production_document` for a given document as it existed before any changes occurred.
  private function getOriginalProductionDocument($request) {
    $serialized = $request['original_production_document'] ?? $request->input('original_production_document');
    try {
      return unserialize($serialized);
    }
    catch(\Exception $e) {
      return [];
    }
  }

  // The $updated_document data isn't correct; not reflecting changes that occurred before this method was invoked...
  protected function synchronizeAlertsAfterUpdate($document, $request) {
    $estimate = Estimate::find($document->estimate_id);
    $original_document_production_weight = $this->getOriginalProductionDocument($request);
    $weight_did_change = $document->production->weight != $original_document_production_weight['weight'];
    if ($estimate->uses_custom_addresses && $weight_did_change) {
      ShippingService::addCustomShippingPendingAlert($estimate);
    }
  }
}