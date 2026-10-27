<?php

namespace App\Services;

use App\Models\Shipto;
use App\Models\StatHoliday;
use \Moment\Moment;
use Illuminate\Database\Eloquent\Collection;

class ShippingService {
  public static function getStatHolidaysDb() {
      return StatHoliday::orderBy('id', 'desc')->get();
  }

  public function getShiptos($company_visibility_id = 1, array $clause_params = []): Collection {
      $clause_params = array_merge(['is_active' => 1], $clause_params);
      $shiptos_lookup = Shipto::where('company_visibility_id', $company_visibility_id);
      if ($clause_params['is_active'] != '*') {
        $shiptos_lookup = $shiptos_lookup->where('is_active', $clause_params['is_active']);
      }
      return $shiptos_lookup->orderBy('company', 'ASC')->get();
    }

  /**
   * Used to determine if an instance of Moment is being used in place of a null/false return value so as to work
   *  within the restrictions of PHP type hinting.
   */
  public static function isEmptyMoment($moment_instance): bool {
    if (empty($moment_instance)) {
      return true;
    }
    if (is_string($moment_instance)) {
      return preg_match("/^0000\-00\-00/", $moment_instance);
    }
    // $moment->setYear(0) idiom
    return intval($moment_instance->format('U')) < -62138;
  }
}