<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Head\Facades\Head;
use App\Models\Estimate;


class Estimates extends Controller
{
    public function index() {
        Head::title('Estimates - Taylor Estimating');

        return view('estimates.index', [
           'estimates' => Estimate::orderByDesc('id')->simplePaginate(50)
        ]);
    }

    public function create() {
        Head::title('Create Estimate - Taylor Estimating');

        return view('estimates.create', [
           
        ]);
    }
}
