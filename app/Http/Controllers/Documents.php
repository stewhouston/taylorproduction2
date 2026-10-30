<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Head\Facades\Head;
use App\Services\DocumentService;
use App\Services\EstimateService;
use App\Models\Estimate;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentTypeName;
use App\Models\Curve;
use App\Models\ProductionDocument;
use App\Objects\PrintReport;
use Illuminate\Http\JsonResponse;

class Documents extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) 
    {

    }

    public function create(Request $request, int $estimate_id, string $type) 
    {
        Head::title('Create Document - Taylor-Demers Estimating');
        $estimate = Estimate::find($estimate_id);
        $document_type = DocumentType::where('slug', $type)->first();
        $document_type_names = DocumentTypeName::where('status', 'A')->orderBy('name', 'asc')->get();
        $current_user = $request->user();
        $user_company = $current_user->company;
        $_document_type_curve_ref = preg_replace("/\s/", "_", $document_type->name);
        $page_count_curve_entries = Curve::getNamedMap($user_company->id, ['like' => "{$_document_type_curve_ref}_Max_Page_Count"])->toArray();
        return view("estimates.documents.create.{$type}", compact('estimate', 'document_type', 'document_type_names', 'current_user', 'user_company', 'page_count_curve_entries'));
    }

    public function store(Request $request, int $estimate_id) 
    {
        $estimate = Estimate::find($estimate_id);
        $document = $this->documentService->create($request, $estimate, [
            'active_rate_set_id' => $estimate->active_rate_set_id
        ]);
        // @TODO CUSTOM DOCUMENT EMAIL
        /*if ($request->input('is_custom_document')) {
            EstimateService::sendCustomDocumentPendingEmail($estimate, $document);
        }*/
        return redirect(route('estimate.show', $estimate->id));
    }

    public function delete(Request $request, int $estimate_id)
    {
        $estimate = Estimate::find($estimate_id);
        $this->documentService->destroy($request->input('document_id'), $estimate, ['last_modified_by' => $request->user()->id]);
        return redirect(route('estimate.show', $estimate_id));
    }

    public function productionDetails(Request $request, int $estimate_id, $document_id) 
    {
      $documents = [];

      if ($document_id === 'all') {
        $documents_collection = Document::where('estimate_id', $estimate_id)->orderBy('created_at', 'ASC')->get();

        $documents = $documents_collection->map(function($_document, $i) use ($documents_collection) {
          $document = $documents_collection->get($i);

          $cover_component = $document->components()->where('type', 'Cover')->get()->first();
          $text_component =  $document->components()->where('type', 'Text')->get()->first();

          $document['components_filtered'] = array_filter([
            $cover_component,
            $text_component
          ]);

          return $_document;
        });
      }
      // Single document from an estimate
      else {
        $document = Document::find($document_id);

        $document['components_filtered'] = array_filter([
          $document->components()->where('type', 'Cover')->get()->first(),
          $document->components()->where('type', 'Text')->get()->first()
        ]);

        $documents[0] = $document;
      }

      $estimate = $documents[0]->estimate;
      $company = $estimate->company;

      // @todo put this somewhere more systematic
      $run_sheet_sizes = array(
        216 => '12 x 18',
        187 => '11 x 17',
        93.5 => '8.5 x 11',
        108 => '9 x 12'
      );

      return view('estimates.documents.production_details', compact('estimate', 'documents', 'company', 'run_sheet_sizes'));
  }

  public function recalculateCost(Request $request, $estimate_id) {
    $estimate = Estimate::find($estimate_id);
    $rate_set_id = intval($request->input('rate_set_id'));
    $rate_set_id = ($rate_set_id === 0) ? null : $rate_set_id;

    foreach($estimate->documents as $document) {
      $request_fields = array_merge(
        $document->toArray(),
        $this->documentService->getBookComponentValues($document)
      );

      unset($request_fields['id']);
      $request_fields['user_id'] = $request->user()->id;

      $prod_doc = $document->production()->first();

      $request_fields['original_production_document'] = (empty($prod_doc))
        ? [] : serialize($prod_doc->toArray());

      // Invoke update procedure
      $document = $this->documentService->update($request_fields, $document->id, [
        'active_rate_set_id' => $rate_set_id,
        'is_cost_recalc' => true
      ]);
    }

    //return 'OK';
    return redirect(route('estimate.show', $estimate_id));
  }

  public function ajaxSetCustomParameters(Request $request, int $estimate_id) 
  {
    $data = $request->all();
    $estimate = Estimate::find($estimate_id);
    $document = Document::find($data['document_id']);
    $approve = $request->input('approve') == "true";
    $has_valid_params = (bool) 0;

    if (!empty($data['type']) && !empty($data['size']) && !empty($data['finish'])
      && floatval($data['weight']) !== 0 && floatval($data['sell_total']) !== 0
      && floatval($data['sell_bind']) !== 0 && !empty($data['hour_range'])
      && floatval($data['sell_print']) !== 0) {
      $has_valid_params = true;
    } else {
      return new JsonResponse(['error' => 'Required field missing.'], 409);
    }

    if ($has_valid_params) {
      $document->type = $data['type'];
      $document->size = $data['size'];
      $document->finish = $data['finish'];

      $prod_doc = ProductionDocument::where('document_id', $document->id)->first();
      $prod_doc_fields = [
        'hour_range' => $data['hour_range'],
        'hours' => $data['hour_range'],
        'weight' => floatval($data['weight']),
        'sell' => floatval($data['sell_total']),
        'print_sell' => floatval($data['sell_print']),
        'bind_sell' => floatval($data['sell_bind']),
        'custom_description_1' => $data['custom_description_1'],
        'custom_description_2' => $data['custom_description_2']
      ];

      if (empty($prod_doc)) {
        $prod_doc = DocumentService::createProductionDocument($document, $prod_doc_fields);
      }
      else {
        foreach($prod_doc_fields as $field => $value) {
          $prod_doc->{$field} = $value;
        }

        $prod_doc->save();
      }

      $dtn = DocumentTypeName::where('short', $document->name)->get()->first();

      $document->report_description = PrintReport::buildDocumentDescription($dtn->name, $document->description, $document->type, $document->size, $document->finish);

      if ($approve) {
        $document->is_pending = 0;
      } else {
        $document->is_pending = 1;
      }

      $document->last_modified_by = $request->user()->id;
      $document->save();

      if ($estimate->hasPendingDocuments() == false && $approve == true) {
        $estimate->clearAlert(DocumentService::CUSTOM_DOCUMENT_PENDING_ALERT);
      }
      if ($approve == false) {
        $estimate->addAlert(DocumentService::CUSTOM_DOCUMENT_PENDING_ALERT);
      }
      if ($approve) {
        // EstimateService::sendCustomDocumentReviewedEmail($estimate, $document);
        EstimateService::updateFilesReqDate($estimate_id);
      }
    }
    return new JsonResponse($data, 200);
  }
}
