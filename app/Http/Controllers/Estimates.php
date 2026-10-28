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
            'all_rate_sets' => $all_rate_sets
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
}
