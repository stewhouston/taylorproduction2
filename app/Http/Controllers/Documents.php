<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Head\Facades\Head;
use App\Services\DocumentService;
use App\Models\Estimate;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentTypeName;
use App\Models\Curve;

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

    public function productionDetails(Request $request, int $estimate_id, $document_id) {
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
}
