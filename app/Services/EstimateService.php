<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Estimate;
use App\Models\Company;
use App\Models\PricingRateSet;
use App\Models\DropInstance;
use App\Models\User;
use App\Models\Document;
use App\Support\TaylorUtility;
use \Moment\Moment;

class EstimateService {
    protected $estimate;
    const INVALID_ESTIMATE_MESSAGE = "Estimate not valid. Previously applied adjustments may no longer be applicable. Contact Taylor-Demers.";

    public function __construct(Estimate $estimate) 
    {
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

    public function update(Request $request, Estimate $estimate) 
    {
      $this->estimate = $estimate;
      $data = array_merge($request->all(), [
        'created_by' => $request->user()->id,
        'last_modified_by' => $request->user()->id,
        'company_id' => $this->estimate->company_id
      ]);
      return $this->estimate->update($data);
    }

    public function destroy($id) 
    {
      DropInstance::where('estimate_id', $id)->delete();
      return $this->estimate->destroy($id);
    }

    public function markAsPrintOrder(Estimate $estimate, User $user) 
    {
    $estimate->is_print_order = 1;
    $estimate->print_order_token = TaylorUtility::makeToken();
    $estimate->marked_print_order_at = new \DateTime();
    $estimate->marked_print_order_by = $user->id;
    $estimate->save();
  }

  public function unmarkAsPrintOrder(Estimate $estimate) 
  {
    $estimate->is_print_order = 0;
    $estimate->print_order_token = "";
    $estimate->marked_print_order_at = null;
    $estimate->marked_print_order_by = null;
    $estimate->save();
  }

  public function updateActiveRateSet(Estimate $estimate, PricingRateSet $rate_set) 
  {
    $estimate->active_rate_set_id = $rate_set->id;
    foreach($estimate->documents as $document) {
      $document->active_rate_set_id = $rate_set->id;
      $document->save();
    }
    $estimate->save();
    return true;
  }

  public static function evaluateEstimateAlerts(Estimate $estimate) 
  {
    if ($estimate->hasAdjustments() || $estimate->adjust_count >= 1) {
      $estimate->addAlert(EstimateService::INVALID_ESTIMATE_MESSAGE);
      $estimate->save();
    }
  }

  public static function updateFilesReqDate(int $estimate_id) {
    $estimate = Estimate::find($estimate_id);

    if (empty($estimate->dropInstances)) {
      return null;
    }

    $files_req_date = static::determineFilesReqDate($estimate_id);

    // A drop may not exist or an error occurred somewhere
    if (ShippingService::isEmptyMoment($files_req_date)) {
      return null;
    }
    // An alert will be added to the estimate in conjunction to this (special requested case)
    /* else if (self::hasPerfectBindDocument($estimate)) {
        $estimate->files_required_by = null;

        return $estimate->save();
    } */
    else {
      $estimate->files_required_by = $files_req_date->format('Y-m-d H:i:s');

      // Clear any custom set adjustments for the files required by field as a new one (more conservative) has been generated
      $estimate->files_require_adjustment = null;
      $estimate->file_require_adjust_reason = null;

      return $estimate->save();
    }
  }

  public static function determineFilesReqDate(int $estimate_id): Moment {
    $estimate = Estimate::find($estimate_id);
    $earliest_drop_instance = ShippingService::getEarliestDeliveryDrop($estimate);

    if (empty($earliest_drop_instance)) {
      $m = new Moment();
      return $m->setYear(0); // null/false idiom
    }

    // 2026 strip microseconds
    if (strpos($earliest_drop_instance->delivery_date, '.') !== false) {
      $d = array_first(explode('.', $earliest_drop_instance->delivery_date));
    } else {
      $d = $earliest_drop_instance->delivery_date;
    }

    $earliest_drop_delivery_date = new Moment($d);
    $earliest_drop_delivery_date->setImmutableMode(true);

    if (ShippingService::zoneIsGTA($earliest_drop_instance->shipto->zone)) {
      $ship_date = $earliest_drop_delivery_date;
    }
    else {
      $ship_date = $earliest_drop_delivery_date->subtractDays(1);
    }

    $ship_date = $ship_date->setHour(0);

    while (ShippingService::isStatHoliday($ship_date) || static::dateIsWeekend($ship_date)
    //  || static::dateIsMonday($ship_date)
    ) {
      $ship_date = $ship_date->subtractDays(1);
    }

    $production_hours_total = $estimate->documents->reduce(function(float $acc, Document $document) {
      $acc += $document->production->hours ?? 0;
      return $acc;
    }, 0);

    $production_hours_total *= .75;
    $production_hours_total = round($production_hours_total, 0);

    // Log::info("\$ship_date = {$ship_date->format()}; \$production_hours_total = $production_hours_total");

    $day_of_week = intval($ship_date->format('w'));

    if ($production_hours_total <= 10) {
      $file_req_date = $ship_date->subtractDays(1)->addHours(16);
    }
    elseif ($production_hours_total <= 16) {
      $file_req_date = $ship_date->subtractDays(1)->addHours(16)
          ->subtractHours($production_hours_total - 10);
    }
    elseif ($production_hours_total <= 28) {
      $sub_days = ($day_of_week === 1 /* MONDAY */) ? 4 : 2;
      $file_req_date = $ship_date->subtractDays($sub_days)->addHours(16);
    }
    elseif ($production_hours_total <= 34) {
      $sub_days = ($day_of_week === 1 /* MONDAY */) ? 4 : 2;
      $file_req_date = $ship_date->subtractDays($sub_days)->addHours(16)
          ->subtractHours($production_hours_total - 28);
    }
    elseif ($production_hours_total <= 44) {
      $sub_days = ($day_of_week <= 2 /* MONDAY|TUESDAY */) ? 5 : 3;
      $file_req_date = $ship_date->subtractDays($sub_days)->addHours(16);
    }
    elseif ($production_hours_total <= 50) {
      $sub_days = ($day_of_week <= 2 /* MONDAY|TUESDAY */) ? 5 : 3;
      $file_req_date = $ship_date->subtractDays($sub_days)->addHours(16)
          ->subtractHours($production_hours_total - 44);
    }
    elseif ($production_hours_total <= 56) {
      $sub_days = ($day_of_week <= 3 /* MON-WEDNESDAY */) ? 6 : 4;
      $file_req_date = $ship_date->subtractDays($sub_days)->addHours(16);
    }
    elseif ($production_hours_total <= 62) {
      $sub_days = ($day_of_week <= 3 /* MON-WEDNESDAY */) ? 6 : 4;
      $file_req_date = $ship_date->subtractDays($sub_days)->addHours(16)
          ->subtractHours($production_hours_total - 56);
    }
    elseif ($production_hours_total <= 68) {
      $sub_days = ($day_of_week <= 4 /* MON-THURSDAY */) ? 7 : 5;
      $file_req_date = $ship_date->subtractDays($sub_days)->addHours(16);
    }
    elseif ($production_hours_total <= 74) {
      $sub_days = ($day_of_week <= 4 /* MON-THURSDAY */) ? 7 : 5;
      $file_req_date = $ship_date->subtractDays($sub_days)->addHours(16)
          ->subtractHours($production_hours_total - 68);
    }
    elseif ($production_hours_total <= 82) {
      $sub_days = ($day_of_week <= 5 /* MON-FRIDAY */) ? 8 : 6;
      $file_req_date = $ship_date->subtractDays($sub_days)->addHours(16);
    }
    elseif ($production_hours_total <= 88) {
      $sub_days = ($day_of_week <= 5 /* MON-FRIDAY */) ? 8 : 6;
      $file_req_date = $ship_date->subtractDays($sub_days)->addHours(16)
          ->subtractHours($production_hours_total - 82);
    }
    elseif ($production_hours_total > 88) {
      $file_req_date = $ship_date->subtractDays(8)->addHours(16);
    }

    while (ShippingService::isStatHoliday($file_req_date) || static::dateIsWeekend($file_req_date)) {
      $file_req_date = $file_req_date->subtractDays(1);
    }

    return $file_req_date;
  }

  protected static function dateIsWeekend(Moment $date): bool 
  {
    $day_of_week = intval($date->format('w'));
    $is_saturday = (bool) ($day_of_week === 6);
    $is_sunday = (bool) ($day_of_week === 0);
    return $is_saturday || $is_sunday;
  }
}