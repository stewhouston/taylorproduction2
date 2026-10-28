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
}
