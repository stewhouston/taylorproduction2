<?php

namespace App\Objects;

class PrintConfig {
 // determines if component runs simplex or duplex
  static function runType(string $print_side2): string {
    if ($print_side2 == 'none'){
      $run_type = 'Simplex';
    } else {   // both sides are printing
      $run_type = 'Duplex';
    }
    return $run_type;
  }

   // determines if a colour or black print device is required
  static function deviceType(string $print_side1, string $print_side2): string {
    if ($print_side1 == 'Black' && ($print_side2 === 'Black' || $print_side2 === 'none')) {
      $device_type = 'Black';
    } else {
      $device_type = 'Colour';
    }
    return $device_type;
  }

// determines the square inches of the finished product
  static function prodSqIn(string $document_size): float {
    if ($document_size == '8 1/2 x 11') {
      $product_sqin = 8.5 * 11;
    } else {
      $product_sqin = 5.5 * 8.5;
    }
    return $product_sqin;
  }

  // Adjusts pages value for calculations of cover and Single sheet simlplex runs
  static function pages(int $pages, string $run_type, string $doc_finish, string $doc_component, string $doc_type): int {
    if ($run_type == 'Simplex' && $doc_component == 'Cover' && in_array($doc_finish, ['Saddlestitch', 'Perfect Bind'])) {
      // $pages confirmed to be even number (divisible by 2)
      $pages = $pages / 2;
    } elseif ($run_type == 'Simplex' and $doc_component == 'Text' and $doc_type == 'Single Sheet') {
      $pages = 1;
    } else {
      $pages = $pages;
    }
    return $pages;
  }

  static function impressions(
    float $product_sqin, int $pages, int $document_quantity, string $finish, array $curve_map
  ): float {
    $impressions = round($product_sqin / 93.5, 2) * $pages * $document_quantity;
    if ($finish === 'Perfect Bind') {
      if ($document_quantity < $curve_map['PB_Waste_Run_Qty_Level1']) {
        $impressions += ($pages * $curve_map['PB_Waste_Books_Needed_Level1']);
      } else {
        $impressions += ($pages * $curve_map['PB_Waste_Books_Needed_Level2']);
      }
    }
    return (float) $impressions;
  }

  // NEW function January 2021 to capture Colour impressions for inserts
  static function colourInsertImpressions(int $document_quantity, int $count_color_inserts): int {
    if ($count_color_inserts > 0) {
      $colour_insert_impressions = $document_quantity * $count_color_inserts;
    } else {
      $colour_insert_impressions = 0;
    }
    return $colour_insert_impressions;
  }

  static function device(
    string $device_type, float $impressions, bool $is_digital_only, int $count_color_inserts, string $stock, array $curve_map
  ): string {
    info("[PrintConfig::device] \$is_digital_only: {$is_digital_only}");

    // 495 has been replaced by TRIVOR October 2020..NEW Logic Jaunary 2021- Device will be Trivor if Colour inserts exist and stock 16,20,24
    if ((($stock == '20lb Bond' && ($impressions >= $curve_map['20lb_Bond_TRIVOR_QTY_Level1'] || $count_color_inserts > 0)) 
    || ($stock == '24lb Bond' && ($impressions >= $curve_map['24lb_Bond_TRIVOR_QTY_Level1'] || $count_color_inserts > 0)) 
    || (in_array($stock, ['40lb Offset', '16lb Bond', '11lb Bond']) && ($impressions >= $curve_map['40lb_Offset_TRIVOR_QTY_Level1'] || $count_color_inserts > 0))
    ) && ($is_digital_only === false)) {
      $device = 'TRIVOR';
    } elseif ($device_type == 'Colour' || $stock == '6 pg cover on 8pt Ctd Cover') {
      $device = '800';
    } else {
      $device = '144';
    }
     return (string) $device;
  }

  // Determines the sheet size to print
  static function runShtSqIn(
    string $component_type, float $product_sqin, int $bleed, string $document_finish, string $device, string $stock
  ): float {
    if ($component_type == 'Text') {
      if ($device == 'TRIVOR') {
        $run_sheet_sqin = 216;
      } elseif ($product_sqin == 93.5 && $bleed == false && in_array($document_finish, ['4pg Folder', '4pg Folder-1/4 Fold', '4pg Folder-Letter Fold', 'Perfect Bind', 'Saddlestitch'])) {
        $run_sheet_sqin = 187;
      } elseif ($product_sqin == 93.5 && $bleed == false) {
        $run_sheet_sqin = 93.5;
      } elseif ($bleed and $product_sqin == 93.5 && in_array($document_finish, ['4pg Folder', '4pg Folder-1/4 Fold', '4pg Folder-Letter Fold', 'Perfect Bind', 'Saddlestitch'])) {
        $run_sheet_sqin = 216;
      } elseif (boolval($bleed)) {
       $run_sheet_sqin = 108;
      } else {
        $run_sheet_sqin = 93.5;
      }
    } else {
      // cover - New TRIVOR logic October 2020
      if ($device == 'TRIVOR') {
        $run_sheet_sqin = 216;
      } elseif ($stock == '6 pg cover on 8pt Ctd Cover' && $product_sqin == 93.5) {
        $run_sheet_sqin = 278;
      } elseif ($stock == '6 pg cover on 8pt Ctd Cover') {
        $run_sheet_sqin = 140;
      } elseif ($document_finish == 'Perfect Bind' || ($product_sqin == 93.5 && $document_finish == 'Saddlestitch' && boolval($bleed))) {
        $run_sheet_sqin = 216;
      } elseif ($product_sqin == 93.5 && $document_finish == 'Saddlestitch'){
        $run_sheet_sqin = 187;
      } elseif (boolval($bleed)) {
        // document `bleed`: whether ink should run outside document margins
        $run_sheet_sqin = 108;
      }  else{
        $run_sheet_sqin = 93.5;
      }
    }
    return (float) $run_sheet_sqin;
  }

  // determines number of printed pages per run sheet
  static function pgsPerRunSht(string $run_type, float $run_sheet_sqin, float $product_sqin): float {
    if ($run_type == 'Simplex') {
      $pages_per_run_sheet = round($run_sheet_sqin / $product_sqin, 0);
    } else {
      $pages_per_run_sheet = 2 * round($run_sheet_sqin / $product_sqin, 0);
    }
    return round($pages_per_run_sheet, 0);
  }

  static function bindWaste(string $document_finish, int $document_quantity, array $curve_map): float {
    if ($document_finish == 'Perfect Bind') {
      if ($document_quantity < $curve_map['PB_Waste_Run_Qty_Level1']) {
        $bind_waste = $curve_map['PB_Waste_Books_Needed_Level1'];
      } else {
        $bind_waste = $curve_map['PB_Waste_Books_Needed_Level2'];
      }
    } else {
      $bind_waste = 0;
    }
    return (float) $bind_waste;
  }

  static function runSheets(int $pages, int $pages_per_run_sheet, int $document_quantity, float $bind_waste): float {
    $run_sheets = round($pages / $pages_per_run_sheet * ($document_quantity + $bind_waste), 0);
    return (float) $run_sheets;
  }

  static function blackClicks(
    string $device, int $run_sheets, string $device_type, string $run_type,
    string $print_side1, string $print_side2, float $run_sheet_sqin
  ): int {
    if ($device_type == 'Black' and $device == '495') {
      $black_clicks = $run_sheets * round($run_sheet_sqin / 93.5, 0);
    } elseif ($device_type == 'Black' and $run_type == 'Duplex') {
      $black_clicks = $run_sheets * 2;
    } elseif ($device_type == 'Colour' and $run_type== 'Duplex' and ($print_side1=='Black' or $print_side2=='Black')){
      $black_clicks = $run_sheets;
    } elseif ($device_type == 'Black' and $run_type == 'Simplex') {
      $black_clicks = $run_sheets;
    } else {
      $black_clicks = 0;
    }
    return (int) $black_clicks;
  }

  static function colourClicks(
    int $run_sheets, string $device_type, string $run_type, string $print_side1, string $print_side2
  ): int {
    if ($device_type == 'Colour' and $run_type == 'Duplex' and ($print_side1 =='Black' or $print_side2 =='Black')) {
      $colour_clicks = $run_sheets;
    } elseif ($device_type == 'Colour' and $run_type == 'Duplex') {
      $colour_clicks = $run_sheets * 2;
    } elseif ($device_type == 'Colour' and $run_type == 'Simplex') {
      $colour_clicks = $run_sheets;
    } else {
      $colour_clicks = 0;
    }
    return (int) $colour_clicks;
  }

  static function cutRequired(
    string $document_finish, string $device, float $product_sqin, int $pages_per_run_sheet,
    float $run_sheet_sqin, int $pages, int $bleed
  ): bool {
    if ($document_finish == 'Perfect Bind') {
      $cut_required = 0;
    } elseif (in_array($device, ['495', 'TRIVOR'])) {
      $cut_required = 0;
    } elseif ($pages / $pages_per_run_sheet < 0 || ($run_sheet_sqin - $product_sqin * 2) > 0 || $bleed == 1) {
      $cut_required = 1;
    } else {
      $cut_required = 0;
    }
    return (bool) $cut_required;
  }

  static function offLineBind(string $document_finish, string $component_type, int $pages, string $device): int {
    if ($component_type == 'Text' and ($document_finish == 'Perfect Bind' or ($document_finish == 'Saddlestitch' and $pages > 48) or $document_finish == 'Fold(1/2)' or $document_finish == '4pg Folder' or $document_finish == '4pg Folder-1/4 Fold' or $document_finish == '4pg Folder-Letter Fold' or $document_finish == 'Letter Fold' or $device == '495')) {
      $offline_bind = 1;
    }
    else {
      $offline_bind = 0;
    }
    return (int) $offline_bind;
  }

  static function lbsPerUnit(string $run_type, float $product_sqin, int $pages, float $basis_wt, float $basis_sz): float {
    if ($run_type == 'Simplex') {
      $unit_weight = round(($product_sqin / $basis_sz * $basis_wt * $pages) / 1000, 4);
    } else {
      $unit_weight = round(($product_sqin / $basis_sz * $basis_wt * $pages / 2) / 1000, 4);
    }
    return (float) $unit_weight;
  }

  static function caliper(float $caliper, string $run_type, int $pages): float {
    if ($run_type == 'Simplex') {
      $component_caliper = round(($caliper * $pages) / 1000, 4);
    } else {
      $component_caliper = round(($caliper * $pages / 2) / 1000, 4);
    }
    return (float) $component_caliper;
  }

  // Added TRIVOR logic October 2020
  static function sellItemDesc(
    string $stock, float $run_sheet_sqin, string $print_side1, string $print_side2,
    string $device, float $impressions, array $curve_map
  ): string {
    if ($print_side1 == 'Colour') {
      $colours_side1 = 4;
    } else {
      $colours_side1 = 1;
    }

    if ($print_side2 == 'none') {
      $colours_side2 = 0;
    } elseif ($print_side2 == 'Colour') {
      $colours_side2 = 4;
    } else {
      $colours_side2 = 1;
    }

    if ($colours_side1 >= $colours_side2) {
      $colour = $colours_side1 . '/' . $colours_side2;
    } else {
      $colour = $colours_side2 . '/' . $colours_side1;
    }

    if ($device == 'TRIVOR') {
      $stock_underscored = preg_replace("/\s/", "_", $stock);
      switch ($stock) {
        case '20lb Bond':
          if ($impressions < $curve_map['20lb_Bond_TRIVOR_QTY_Level2']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level1';
          } elseif ($impressions < $curve_map['20lb_Bond_TRIVOR_QTY_Level3']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level2';
          } elseif ($impressions < $curve_map['20lb_Bond_TRIVOR_QTY_Level4']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level3';
          } else {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level4';
          }
          break;
        case '24lb Bond':
          if ($impressions < $curve_map['24lb_Bond_TRIVOR_QTY_Level2']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level1';
          } elseif ($impressions < $curve_map['24lb_Bond_TRIVOR_QTY_Level3']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level2';
          } elseif ($impressions < $curve_map['24lb_Bond_TRIVOR_QTY_Level4']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level3';
          } else {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level4';
          }
          break;
        case '40lb Offset':
          if ($impressions < $curve_map['40lb_Offset_TRIVOR_QTY_Level2']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level1';
          } elseif ($impressions < $curve_map['40lb_Offset_TRIVOR_QTY_Level3']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level2';
          } elseif ($impressions < $curve_map['40lb_Offset_TRIVOR_QTY_Level4']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level3';
          } else {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level4';
          }
          break;
        case '16lb Bond':
          if ($impressions < $curve_map['40lb_Offset_TRIVOR_QTY_Level2']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level1';
          } elseif ($impressions < $curve_map['40lb_Offset_TRIVOR_QTY_Level3']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level2';
          } elseif ($impressions < $curve_map['40lb_Offset_TRIVOR_QTY_Level4']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level3';
          } else {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level4';
          }
          break;
        case '11lb Bond':
          if ($impressions < $curve_map['40lb_Offset_TRIVOR_QTY_Level2']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level1';
          } elseif ($impressions < $curve_map['40lb_Offset_TRIVOR_QTY_Level3']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level2';
          } elseif ($impressions < $curve_map['40lb_Offset_TRIVOR_QTY_Level4']) {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level3';
          } else {
            $sell_item_description = $stock_underscored . '_' . $colour . '_' . $device . '_IMP_Level4';
          }
          break;
      }
    } else {
      $sell_item_description = "{$stock}_{$run_sheet_sqin}_{$colour}";
    }
    return (string) $sell_item_description;
  }
}
