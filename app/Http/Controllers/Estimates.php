<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Head\Facades\Head;
use App\Models\Estimate;
use App\Models\Document;
use App\Models\Company;
use App\Models\DocumentType;
use App\Models\PricingRateSet;
use App\Models\Curve;
use App\Models\DropInstance;
use App\Models\Shipto;
use App\Services\EstimateService;
use App\Services\ShippingService;
use \Moment\Moment;

class Estimates extends Controller
{
    public function __construct(
        protected EstimateService $estimateService,
        protected ShippingService $shippingService
    ) {

    }

    public function index(Request $request) {
        Head::title('Estimates - Taylor Estimating');

        if ($request->user()->isAdmin()) {
            $estimates = Estimate::orderBy('id', 'DESC');
        }
        else {
            $estimates = Estimate::where('company_id', $request->user()->company_id)->orderBy('id', 'DESC');
        }

        $estimates = $estimates->where('is_archived', 0)->where('client_reference_number', '!=', '');

        if (!empty($request->query('search'))) {
            $estimates = $estimates->where(function($query) use ($request) {
                return $query->orWhere('id', '=', $request->query('search'))
                    ->orWhere('client_reference_number', '=', $request->query('search'))
                    ->orWhere('client_reference_description', 'like', '%' . $request->query('search') . '%');
            });
        }

        return view('estimates.index', [
           'estimates' => $estimates->simplePaginate(30)->withQueryString(),
           'search_term' => $request->query('search')
        ]);
    }

    public function archives(Request $request) {
        Head::title('Archives - Taylor-Demers Estimating');

        if ($request->user()->isAdmin()) {
            $estimates = Estimate::orderBy('id', 'DESC');
        }
        else {
            $estimates = Estimate::where('company_id', $request->user()->company_id)->orderBy('id', 'DESC');
        }

        $estimates = $estimates->where('is_archived', 1)->where('client_reference_number', '!=', '');

        if (!empty($request->query('search'))) {
            $estimates = $estimates->where(function($query) use ($request) {
                return $query->orWhere('id', '=', $request->query('search'))
                    ->orWhere('client_reference_number', '=', $request->query('search'))
                    ->orWhere('client_reference_description', 'like', '%' . $request->query('search') . '%');
            });
        }

        return view('estimates.index', [
           'estimates' => $estimates->simplePaginate(30)->withQueryString(),
           'search_term' => $request->query('search')
        ]);
    }

    public function show(Request $request, $id) {
        $estimate = Estimate::find($id);
      
        if (empty($estimate)) {
            return response('Estimate Not Found', 404);
        }

        Head::title("{$estimate->client_reference_number} - {$estimate->client_reference_description} - Estimate - Taylor-Demers Estimating");
        $documents = Document::where('estimate_id', $id)->orderBy('created_at', 'ASC')->get();
        $document_types = DocumentType::orderBy('order_index', 'asc')->get();
        $costs = $estimate->getAllCosts();
        $drop_instances = DropInstance::where('estimate_id', $estimate->id)->get();
        $curve_fields = Curve::getFields(['Tax_HST_Percent', 'Delivery_Rush_Fee_Dollars'], $estimate->company_id)->toArray();
        $shiptos = $this->shippingService->getShiptos($estimate->company_id);
        $est_active_rate_set = PricingRateSet::getEstimateRateSet($estimate);
        $uses_inactive_rate_set = (bool) (empty($est_active_rate_set) || !PricingRateSet::isActiveRateSet($est_active_rate_set, $estimate->company_id));
        $all_rate_sets = PricingRateSet::all()->sortByDesc('created_at');
        $stat_holidays_db = ShippingService::getStatHolidaysDb();

        return view('estimates.show', [
            'estimate' => $estimate,
            'documents' => $documents,
            'document_types' => $document_types,
            'costs' => $costs,
            'drop_instances' => $drop_instances,
            'curve_fields' => $curve_fields,
            'shiptos' => $shiptos,
            'est_active_rate_set' => $est_active_rate_set,
            'uses_inactive_rate_set' => $uses_inactive_rate_set,
            'all_rate_sets' => $all_rate_sets,
            'stat_holidays_db' => $stat_holidays_db
        ]);
    }

    public function create(Request $request) {
        Head::title('Create Estimate - Taylor-Demers Estimating');

        $stat_holidays_db = ShippingService::getStatHolidaysDb();
        $all_companies = Company::getScopePermissible($request->user(), [
            'filter' => ['TDMP']
        ]);
        $user_company_id = (int) $request->user()->company->id;
        if ($request->user()->isAdmin() && $request->user()->hasPermission('price_management')) {
            $pricing_rate_sets = PricingRateSet::all()->sortByDesc('created_at');
        } else {
            $pricing_rate_sets = PricingRateSet::where('company_id', $user_company_id)->get();
        }

        return view('estimates.create', compact('stat_holidays_db', 'all_companies', 'user_company_id', 'pricing_rate_sets'));
    }

    public function edit(Request $request, int $id) {
        Head::title('Edit Estimate - Taylor-Demers Estimating');

        $estimate = Estimate::find($id);

        $stat_holidays_db = ShippingService::getStatHolidaysDb();

        return view('estimates.edit', compact('stat_holidays_db', 'estimate'));
    }

    public function store(Request $request) {
        $request->validate([
            'client_reference_number' => 'required',
            'client_reference_description' => 'required',
            'client_reference_mail_date' => 'required'
        ]);
        try {
            $estimate = $this->estimateService->create($request);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            return redirect()->back();
        }
        return redirect(route('estimate.show', $estimate->id));
    }

    public function update(Request $request, int $id) {
        $estimate = Estimate::find($id);

        $request->validate([
            'client_reference_number' => 'required',
            'client_reference_description' => 'required',
            'client_reference_mail_date' => 'required'
        ]);

        $this->estimateService->update($request, $estimate);

        return redirect(route('estimate.show', $estimate->id));
    }

    public function destroy(Request $request, int $id) {
        $this->estimateService->destroy($id);
        return redirect(route('estimates.index'));
    }

    public function print(int $id) {
        $estimate = Estimate::find($id);
        $costs = $estimate->getAllCosts();
        $has_file_req_adjustment = (bool) (!empty($estimate->files_require_adjustment) && !ShippingService::isEmptyMoment($estimate->files_require_adjustment));
        $est_active_rate_set = PricingRateSet::getEstimateRateSet($estimate);
        $uses_inactive_rate_set = (bool) (empty($est_active_rate_set) || !PricingRateSet::isActiveRateSet($est_active_rate_set, $estimate->company_id));
        return view('estimates.print', compact('estimate', 'costs', 'has_file_req_adjustment', 'uses_inactive_rate_set'));
    }

    public function ajaxSetRushFee(Request $request, int $estimate_id) {
        $estimate = Estimate::find($estimate_id);

        $query = $request->all();

        if (empty($query['has_rush_fee']) && intval($query['has_rush_fee']) !== 0) {
            return response()->json(['error' => '409/Must provide a `has_rush_fee` parameter with a value of either 0 or 1.'], 409);
        }
        elseif (intval($query['has_rush_fee']) === 1) {
            $estimate->has_rush_fee = 1;
        }
        elseif (intval($query['has_rush_fee']) === 0) {
            $estimate->has_rush_fee = 0;
        }

        $estimate->last_modified_by = $request->user()->id;
        $estimate->save();

        return response()->json($estimate->getAllCosts(), 200);
    }

    public function ajaxSetArchiveState(Request $request, $id) {
      $estimate = Estimate::find($id);

      $query = $request->all();

      if (empty($query['is_archived']) && intval($query['is_archived']) !== 0) {
          return response()->json(['error' => '409/Must provide a `is_archived` parameter with a value of either 0 or 1.'], 409);
      }
      elseif (intval($query['is_archived']) === 1) {
          $estimate->status = $query['status'] ?? 'unknown';
          $estimate->is_archived = 1;
          $estimate->save();
      }
      elseif (intval($query['is_archived']) === 0) {
          $estimate->status = 'active';
          $estimate->is_archived = 0;
          $estimate->save();
      }

      return 'ok';
  }

  public function ajaxSetAlerts(Request $request, $estimate_id) 
  {
    $estimate = Estimate::find($estimate_id);

    if ($request->input('clearAll')) {
      $estimate->clearAlerts();
      if (count($estimate->pendingDocuments()) >= 1) {
        $estimate->addAlert(DocumentService::CUSTOM_DOCUMENT_PENDING_ALERT);
      }
      return 'ok';
    }
    else if ($request->input('addAlert')) {
      $estimate->addAlert($request->input('addAlert'));
      return 'ok';
    }
  }

  public function ajaxSetOverrides(Request $request, $estimate_id) 
  {
      $price_adjust_reason = $request->input('priceAdjustReason');
      $price_adjust_value = $request->input('priceAdjustValue');
      $ship_adjust_reason = $request->input('shipAdjustReason');
      $ship_adjust_value = $request->input('shipAdjustValue');
      $files_req_adjust_date = $request->input('filesReqAdjustDate');
      $files_req_adjust_reason = $request->input('filesReqAdjustReason');
      // No idea why urldecode is required. Moved method from GET to POST. If no urldecode, spaces show as %20
      $estimate_comment = urldecode($request->input('estimateComment'));
      $custom_alert_text = urldecode($request->input('customAlert'));

      $estimate = Estimate::find($estimate_id);

      // @todo price sanitize
      $estimate->print_sell_adjustment = $price_adjust_value;
      $estimate->print_sell_adjust_reason = $price_adjust_reason;

      $estimate->drop_sell_adjustment = $ship_adjust_value;
      $estimate->drop_sell_adjust_reason = $ship_adjust_reason;

      // files req date; assume formatted by js
      $estimate->file_require_adjust_reason = $files_req_adjust_reason;

      if ($files_req_adjust_date) {
          $m = new Moment($files_req_adjust_date);
          $m->subtractHours(4); // PHP/SQL seem to have a 4 hour discrepency
          $estimate->files_require_adjustment = $m->format('Y-m-d H:i:s');
      } else {
        $estimate->files_require_adjustment = null;
      }

      $estimate->adjustment_by = $request->user()->id;
      $m = new Moment();
      $m->subtractHours(4); // PHP/SQL seem to have a 4 hour discrepency
      $estimate->adjustment_date = $m->format('Y-m-d H:i:s');

      $estimate->estimate_comment = $estimate_comment;

      if ($estimate->hasAdjustments()) {
        $estimate->adjust_count += 1;
      }
      else {
        $estimate->adjust_count = 0;
      }

      // $estimate->last_modified_by = $request->user()->id;

      $estimate->save();

      if (!empty($custom_alert_text)) {
          // causes double save; not ideal but fine
        $estimate->addAlert($custom_alert_text);
      }

      return response()->json($estimate);
  }
}
