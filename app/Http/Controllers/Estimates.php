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

    public function create() {
        Head::title('Create Estimate - Taylor Estimating');

        return view('estimates.create', [
           
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
}
