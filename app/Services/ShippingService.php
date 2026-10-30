<?php

namespace App\Services;

use App\Models\Shipto;
use App\Models\StatHoliday;
use App\Models\Estimate;
use App\Models\DropInstance;
use App\Models\Zone;
use App\Models\Document;
use App\Models\ProductionDocument;
use App\Models\Curve;
use \Moment\Moment;
use Illuminate\Database\Eloquent\Collection;
use App\Support\TaylorUtility;

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

  // Creates or updates a drop instance with new/updated drop data. Responsible for calculating and storing the `drop_weight_total` and `drop_cost` values.
    public function processDropInstanceProcedure(int $estimate_id, int $shipto_id, int $drop_instance_id, array $drop_data, int $estimate_company_id, int $user_id, $opts = []): DropInstance 
    {
      $opts = array_merge([
        'updateLastModifiedBy' => true
      ], $opts);

      $estimate = Estimate::find($estimate_id);
      $zone = Shipto::find($shipto_id)->zoneRow;

      // confirm all documents in drop instance exists; remove any that may have been deleted
      foreach($drop_data as $document_id => $ship_qty) {
        $document_exists = Document::find($document_id);
        if (empty($document_exists)) {
          unset($drop_data[$document_id]);
        }
      }

      $drop_weight_total = $this->getDropWeightLbs($drop_data);

      $drop_instance_fields = [
        'estimate_id' => $estimate_id,
        'documents_qty' => json_encode($drop_data),
        'drop_weight_total' => $drop_weight_total,
        'drop_cost' => $this->determineDropCost($zone, $drop_weight_total),
        'drop_handling_cost' => 00,
        'shipto_id' => $shipto_id,
        'alerts' => ''
      ];

      $drop_instance_fields['drop_handling_cost'] = (float) ($drop_instance_fields['drop_cost'] * $zone->handling);

      if ($opts['updateLastModifiedBy']) {
        $drop_instance_fields['last_modified_by'] = $user_id;
      }

      if (!empty($opts['updated_directly']) && $opts['updated_directly'] === true) {
        $drop_instance_fields['updated_directly_at'] = date("Y-m-d H:i:s");
      }

      // dropInstanceId provided; update existing drop_instance row
      if (!empty($drop_instance_id) && !is_nan(intval($drop_instance_id))) {
        $drop_instance = $this->updateDropInstance($drop_instance_id, $drop_instance_fields);
      }
      // no dropInstanceId provided; create new drop_instance row
      else {
        $drop_instance = $this->createDropInstance(array_merge($drop_instance_fields, ['created_by' => $user_id]));
      }

      $non_gta_weight_threshold = (float) Curve::getFieldValue('Alert_NonGTA_Weight_Threshold_Pounds', $estimate_company_id);
      $drop_weight_threshold_exceeded = (bool) ($drop_weight_total > $non_gta_weight_threshold);
      $is_non_gta = (bool) !preg_match('/^GTA/', $zone->zone);

      if ($drop_weight_threshold_exceeded && $is_non_gta) {
        $estimate->addAlert("Check with Taylor for shipping options with lower rates.");
      }

      EstimateService::evaluateEstimateAlerts($estimate);

      return $drop_instance;
    }

  public function createDropInstance($fields): DropInstance {
      $drop_instance = new DropInstance($fields);

      $drop_instance->token = TaylorUtility::makeToken();
      $drop_instance->created_at = date("Y-m-d H:i:s");
      $drop_instance->updated_at = date("Y-m-d H:i:s");

      $drop_instance->save();

      // not needed until/if dates can be set when drops are created (not currently the case)
      //EstimateService::updateFilesReqDate($estimate_id);

      return $drop_instance;
  }

  public function updateDropInstance($drop_instance_id, $fields)/*:DropInstance*/ {
    $drop_instance = DropInstance::find($drop_instance_id);
    foreach($fields as $field => $value) {
      $drop_instance->{$field} = $value;
    }
    if (empty($drop_instance->token)) {
      $drop_instance->token = TaylorUtility::makeToken();
    }
    $drop_instance->save();
    EstimateService::updateFilesReqDate($drop_instance->estimate_id);
    return $drop_instance;
  }

  public function getDocumentWeight($document_id, $opts = ['qty' => 1]):float {
//    $document = $this->getDocument($document_id);
      $production_document = $this->getProductionDocument($document_id);

      // This could potentially cause errors in shipping cost calculation if a 0 value propagates into final price
      if (empty($production_document)) return 0;

      return $production_document->weight * $opts['qty'];
    }

  // @param $documents_qty_map is an associative array where ["$document_id" => "$qty", ...]
  public function getDropWeightLbs($documents_qty_map): float 
  {
      $total_documents_weight = 0;
      foreach($documents_qty_map as $document_id => $qty) {
          $total_documents_weight += $this->getDocumentWeight($document_id, ['qty' => $qty]);
      }
      return $total_documents_weight;
  }

  public function getProductionDocument($document_id) {
    return ProductionDocument::where('document_id', $document_id)->get()->first();
  }

  public function determineDropCost(Zone $zone, float $drop_lbs, array $params = []): float 
  {
    // Should cost trail debug be required in a future release (note float union type)
    /*$params = array_merge([
      'debug' => false
    ], $params);*/

    if ($drop_lbs == 0) {
      return 0;
    }

    $ship_cost = $zone['base_rate'];

    if ($drop_lbs > 1) {
      $ship_cost += (($drop_lbs-1) * $zone['additional_lb']);
    }

    if ($drop_lbs > $zone['carton_lbs']) {
      $ship_cost += + (ceil((($drop_lbs-$zone['carton_lbs']) / $zone['carton_lbs'])) * $zone['additional_carton']);

      if ($drop_lbs > $zone['skid_lbs']) {
        $ship_cost += (ceil($drop_lbs/$zone['skid_lbs']) * $zone['additional_skid']);
      }
    }

    $ship_cost = round($ship_cost, 2);

    return $ship_cost;
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

        // 2026 strip microseconds
        if (strpos($drop_instance->delivery_date, '.') !== false) {
          $delivery_date = array_first(explode('.', $drop_instance->delivery_date));
        } else {
          $delivery_date = $drop_instance->delivery_date;
        }

        $instance_delivery_date = new Moment($delivery_date);

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

    public static function isStatHoliday(Moment $date): bool 
    {
      $stat_holidays = static::getStatHolidaysDb();
      foreach($stat_holidays as $stat_holiday) {
        if ($stat_holiday->date_string === $date->format('j-M-Y')) {
          return true;
        }
      }
      return false;
    }

    public static function zoneIsGTA(string $zone): bool 
    {
      return (bool) preg_match('/^GTA/', $zone);
    }

    public static function addCustomShippingPendingAlert($estimate) 
    {
      $estimate->addAlert('Shipping rate pending for custom delivery zone. Check with Taylor for rate.');
    }
}