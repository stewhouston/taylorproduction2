<?php

namespace App\Objects;

use App\Models\Stock;

class PrintStock {
  static function basisWeight(string $stock): float {
    $basis_weight = Stock::where('description', $stock)->first()->basis_weight;
    return (float) $basis_weight;
  }

  static function basisSize(string $stock): float {
    $basis_size = Stock::where('description', $stock)->first()->basis_size;
    return (float) $basis_size;
  }

  static function caliper(string $stock): float {
    $caliper = Stock::where('description', $stock)->first()->caliper;
    return (float) $caliper;
  }

  static function stockCost(string $stock, float $run_sheet_sqin, int $run_sheets, string $device): float {
    $stock_description = $stock;
    $stock_cost = null;

    // For 'Bondroll' only
    if ($device == '495') {
     $stock_description = $stock . 'roll';
    }

    $stock_collection = Stock::where('description', $stock_description);

    switch($run_sheet_sqin) {
      case 93.5:
        $stock_cost = $stock_collection->first()->cost_93;
        break;
      case 108:
        $stock_cost = $stock_collection->first()->cost_108;
        break;
      case 187:
        $stock_cost = $stock_collection->first()->cost_187;
        break;
      default:
        $stock_cost = $stock_collection->first()->cost_216;
    }
    return ($stock_cost * $run_sheets);
  }
}
