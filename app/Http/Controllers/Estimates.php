<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Head\Facades\Head;
use App\Models\Estimate;
use App\Services\EstimateService;


class Estimates extends Controller
{
    public function __construct(protected EstimateService $estimateService) {

    }

    public function index(Request $request) {
        Head::title('Estimates - Taylor Estimating');

        if (!empty($request->query('search'))) {
            $estimates = Estimate::where('is_archived', 0)->orderByDesc('id')->where(function($query) use ($request) {
                return $query->orWhere('id', '=', $request->query('search'))
                    ->orWhere('client_reference_number', '=', $request->query('search'))
                    ->orWhere('client_reference_description', 'like', '%' . $request->query('search') . '%');
            })->simplePaginate(50);
        } else {
            $estimates = Estimate::where('is_archived', 0)->orderByDesc('id')->simplePaginate(50);
        }

        return view('estimates.index', [
           'estimates' => $estimates,
           'search_term' => $request->query('search')
        ]);
    }

    public function archives(Request $request) {
        Head::title('Archives - Taylor Estimating');

        if (!empty($request->query('search'))) {
            $estimates = Estimate::where('is_archived', 1)->orderByDesc('id')->where(function($query) use ($request) {
                return $query->orWhere('id', '=', $request->query('search'))
                    ->orWhere('client_reference_number', '=', $request->query('search'))
                    ->orWhere('client_reference_description', 'like', '%' . $request->query('search') . '%');
            })->simplePaginate(50);
        } else {
            $estimates = Estimate::where('is_archived', 1)->orderByDesc('id')->simplePaginate(50);
        }

        return view('estimates.index', [
           'estimates' => $estimates,
           'search_term' => $request->query('search')
        ]);
    }

    public function create() {
        Head::title('Create Estimate - Taylor Estimating');

        return view('estimates.create', [
           
        ]);
    }

    public function show(Request $request, $id) {
        $estimate = Estimate::find($id);
      
        if (empty($estimate)) {
            return response('Estimate Not Found', 404);
        }

        Head::title("{$estimate->client_reference_number} - {$estimate->client_reference_description} - Estimate - Taylor Estimating");

        return view('estimates.show', [
            'estimate' => $estimate
        ]);
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
