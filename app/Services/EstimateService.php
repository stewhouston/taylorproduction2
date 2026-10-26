<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Estimate;
use App\Models\Company;
use App\Models\PricingRateSet;
use App\Models\DropInstance;

class EstimateService {
    protected $estimate;

    public function __construct(Estimate $estimate) {
      $this->estimate = $estimate;
    }

    public function create(Request $request) {
      $request_user = $request->user();
      $created_by = (int) $request_user->id;

      if ($request_user->isAdmin() && $request->input('company_id')) {
        $company_id = (int) $request->input('company_id') ?: $request_user->company->id;
      } else {
        $company_id = (int) $request_user->company->id;
      }

      $rate_set_id = (int) $request->input('pricing_rate_set_id') ?: PricingRateSet::companyActive($company_id, 'id');
      $est_company = Company::find($company_id);

      $data = array_merge($request->all(), compact('created_by', 'company_id'));
      // Format: $COMPANY_NAME-$JOB_NUMBER
      $data['client_reference_number'] =  $est_company->name . "-" . $data['client_reference_number'];
      $data['active_rate_set_id'] = $rate_set_id;
      $data['last_modified_by'] = $request_user->id;

      $estimate = $this->estimate->create($data);
      return $estimate;
    }

    public function destroy($id) {
      DropInstance::where('estimate_id', $id)->delete();
      return $this->estimate->destroy($id);
    }
}