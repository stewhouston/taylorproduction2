<?php

namespace App\Objects;

use Illuminate\Database\Eloquent\Collection;
use App\Models\SellRate;

class PrintSell {
  public static function print495SellRate($impressions, $sell_item_description, $device_type, $pricing = [], $curve_map = []) {
    $min_item_rate = $pricing['min_item_rate'];
    $max_item_rate = $pricing['max_item_rate'];

    if ($device_type == 'Colour') {
      $min_impressions = $curve_map['Print_Color_Min_Curve_Impressions'];
      $max_impressions = $curve_map['Print_Color_Max_Curve_Impressions'];
    }
    else if ($device_type == 'Black') {
      $min_impressions = $curve_map['Print_Black_Min_Curve_Impressions'];
      $max_impressions = $curve_map['Print_Black_Max_Curve_Impressions'];
    }
    else {
      throw new \Exception("PrintSell::print495SellRate No impression data for \$device_type `$device_type`");
    }

    /**
     * PrintCURVE rate : item rate ranges from a low to a high rate. The greater the number of impressions the less the item rate
     * This equation determines that rate
     */
    if ($impressions >= $max_impressions) {
      $print_item_rate = $max_item_rate;
    } elseif ($impressions <= $min_impressions) {
      $print_item_rate = $min_item_rate;
    } else{
      $print_item_rate = round($min_item_rate - (($min_item_rate - $max_item_rate) * (($impressions - $min_impressions) / ($max_impressions - $min_impressions))), 4);
    }

    return $print_item_rate;
  }

  public static function printSellRate($sell_item_description, $pricing = []) {
    return $pricing['print_item_rate'];
  }

  public static function printSetUpSellRate($pricing = []) {
    return $pricing['print_setup_rate'];
  }

  public static function printDocSetUpSellRate($pricing = []) {
    return $pricing['document_setup_rate'];
  }

  /**
   * Common cost addition sequence for cover imprints.
   * Used in generating a portion of the computed document `component_sell` value.
   */
  public static function csImprintSequenceSetup(Collection $type_rates, 
    int $count_imprints, int $document_quantity, bool $is_sequential) {
    info("[PrintSell::csImprintSequenceSetup] \$count_imprints: {$count_imprints} / \$document_quantity: {$document_quantity}");

    $cost = 0.0;

    if ($count_imprints > 0) {
      $cost += round(SellRate::getTypeRate($type_rates, 'Imprint_Setup') 
        + (SellRate::getTypeRate($type_rates, 'Per_Imprint') * $count_imprints), 2);
    }

    info("[PrintSell::csImprintSequenceSetup] Cost line 1: {$cost}");

    if ($is_sequential) {
      $cost += round(SellRate::getTypeRate($type_rates, 'Sequential_Numbering') 
        * $document_quantity, 2);
    }
    
    info("[PrintSell::csImprintSequenceSetup] Cost line 2: {$cost}");
    
    return $cost;
  }
}
