<?php

namespace App\Services;

use App\Models\Shipto;
use App\Models\StatHoliday;
use App\Models\Estimate;
use App\Models\DropInstance;
use App\Models\Zone;
use \Moment\Moment;
use Illuminate\Database\Eloquent\Collection;

class ShippingService {
  public static function getStatHolidaysDb() 
  {
      return StatHoliday::orderBy('id', 'desc')->get();
  }

  public function getZones() 
  {
      return Zone::all();
  }

  public function getShiptos($company_visibility_id = 1, array $clause_params = []): Collection 
  {
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
  public static function isEmptyMoment($moment_instance): bool 
  {
    if (empty($moment_instance)) {
      return true;
    }
    if (is_string($moment_instance)) {
      return preg_match("/^0000\-00\-00/", $moment_instance);
    }
    // $moment->setYear(0) idiom
    return intval($moment_instance->format('U')) < -62138;
  }

  public static function getEarliestDeliveryDrop(Estimate $estimate): ?DropInstance 
  {
      if (empty($estimate->dropInstances) || $estimate->dropInstances->count() == 0) {
        return null;
      }
      // far future date as default for simplified comparison below
      $cmp_moment = new Moment('2999-01-01T00:00:00');
      // $earliest_drop_instance;

      foreach($estimate->dropInstances as $drop_instance) {
        if (empty($drop_instance->delivery_date)) {
          continue;
        }

        $instance_delivery_date = new Moment($drop_instance->delivery_date);

        // Deliveries being shipped outside of the GTA require an extra day. Compensate here for comparison.
        $delivery_is_far = (bool) !static::zoneIsGTA($drop_instance->shipto->zone);
        if ($delivery_is_far) {
          $instance_delivery_date->subtractDays(1);
        }

        $date_diff = $cmp_moment->from($instance_delivery_date);

        if ($date_diff->getDirection() === "future" || ($delivery_is_far && $date_diff->getSeconds() <= 0)) {
          $earliest_drop_instance = $drop_instance;
          $cmp_moment = $instance_delivery_date;
          // Log::info("ShippingService::getEarliestDeliveryDrop \$earliest_drop_instance->delivery_date = {$earliest_drop_instance->delivery_date}; \$cmp_moment = {$cmp_moment->format()}");
        }
      }

      if (empty($earliest_drop_instance)) {
        return null;
      }

      return $earliest_drop_instance;
    }

    public static function zoneIsGTA(string $zone): bool 
    {
      return (bool) preg_match('/^GTA/', $zone);
    }
}