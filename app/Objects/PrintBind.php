<?php

namespace App\Objects;

class PrintBind
{
   static function cutHours($text_cut_required,$text_run_sheets,$cover_cut_required,$cover_run_sheets)
   {
      $cut_setup_hours =.5;
      $text_sheets_per_lift = 750;
      $cover_sheets_per_lift = 500;
      $text_lifts_per_hour = 25;
      $cover_lifts_per_hour=20;
      $cut_minimum_run_hours =.5;

      if($text_cut_required){
          $cut_hours = round($text_run_sheets / $text_sheets_per_lift / $text_lifts_per_hour, 2);
          if($cut_hours<$cut_minimum_run_hours){
              $cut_hours=$cut_minimum_run_hours;
          }
          $cut_hours=$cut_hours+$cut_setup_hours;
      }

      else {
          $cut_hours = 0;
      }
      if($cover_cut_required){
          $cut_hours = round($cut_hours + $cover_run_sheets / $cover_sheets_per_lift / $cover_lifts_per_hour, 2);
          if($cut_hours<$cut_minimum_run_hours){
              $cut_hours=$cut_minimum_run_hours;
          }
          $cut_hours = $cut_hours+$cut_setup_hours;
      }


      return $cut_hours;
  }

  static function cutCost($cut_hours)
  {
      $cut_op=18;//pull rate from cost rate table?
      $cut_cost = round($cut_hours * $cut_op,2);

      return $cut_cost;
  }

  static function cutSell($document_quantity, $cover_cut_required, $text_cut_required, $pricing = [])
  {
      $cut_setup = $pricing['cut_setup'];
      $cut_run_rate = $pricing['cut_run_rate'];

      $cut_sell = $cut_setup + round($cut_run_rate * $document_quantity, 2);

      if ($cover_cut_required && $text_cut_required) {
          $cut_sell = $cut_setup + round($cut_run_rate * $document_quantity, 2) * 2;
      }

      return $cut_sell;
  }

  static function foldHours($document_finish, $run_sheet_sqin, $document_quantity)
  {
      if ($document_finish == '4pg Folder-1/4 Fold' or $document_finish == '4pg Folder-Letter Fold') {
          $fold_setup_hours = 0.5;
      }
      else {
          $fold_setup_hours = 0.25;
      }

      $fold_minimum_hours = 0.5;

      if ($run_sheet_sqin >= 187 && ($document_finish == '4pg Folder-1/4 Fold' or $document_finish == '4pg Folder-Letter Fold')) {
          $fold_speed = 5000;
      }
      else if ($run_sheet_sqin >= 187) {
          $fold_speed = 6000;
      }
      else {
          $fold_speed = 9000;
      }

      $fold_hours = $fold_setup_hours + round($document_quantity/$fold_speed,2);

      if ($fold_hours < $fold_setup_hours + $fold_minimum_hours){
          $fold_hours = $fold_setup_hours + $fold_minimum_hours;
      }

      return $fold_hours;
  }

  static function foldCost($fold_hours)
  {
      $fold_op=18;//pull rate from cost rate table?
      $fold_cost = round($fold_hours * $fold_op,2);
      return $fold_cost;
  }

  static function foldSell($document_finish, $document_quantity, $pricing = [])
  {
      $fold_setup = $pricing['fold_setup'];
      $fold_run_rate = $pricing['fold_run_rate'];
      $fold_sell = $fold_setup + round($fold_run_rate * $document_quantity, 2);
      return $fold_sell;
  }

  static function sellItem_foldSellRunRate($document_finish) {
    $sell_item_description = 'fold_run';
    if ($document_finish == '4pg Folder-1/4 Fold') {
        $sell_item_description = 'fold_run-1/4';
    }
    elseif ($document_finish == '4pg Folder-Letter Fold') {
        $sell_item_description = 'fold_run-Letter';
    }
    return $sell_item_description;
  }

  static function stitchHours($pages,$document_type,$product_sqin,$document_quantity) {
    $stitch_setup_hours = .5;
    $stitch_pages = $pages;

    if ($document_type == 'Plus Cover Book') {
      $stitch_pages = $pages + 4;
    }

    if ($pages < 40){
      $stitch_run_speed = round(3600 / ($stitch_pages/4 *.81 + (5 - ($stitch_pages / 4-2) * .625)), 0);
    } else {
      $stitch_run_speed = round(3600 / ($stitch_pages / 4 * .81), 0);
    }
    if ($product_sqin == 46.75) {
      if ($stitch_pages > 40) {
        $stitch_run_speed = round($stitch_run_speed * 1.2, 0);
      }
      elseif ($stitch_pages > 30) {
        $stitch_run_speed = round($stitch_run_speed * 1.1, 0);
      }
      elseif ($stitch_pages > 16) {
        $stitch_run_speed = round($stitch_run_speed * .98, 0);
      }
      else {
        $stitch_run_speed = round($stitch_run_speed * .95, 0);
      }
    }
    $stitch_hours = $stitch_setup_hours + round($document_quantity/$stitch_run_speed,2);
    return $stitch_hours;
  }

  static function stitchCost($stitch_hours) {
    $stitch_op = 22;
    $ghw_op = 15;
    $stitch_setup_hours = .5;
    $stitch_cost = round($stitch_hours * $stitch_op, 2);
    $stitch_cost += round(($stitch_hours-$stitch_setup_hours) * $ghw_op, 2);
    return $stitch_cost;
  }

  static function stitchSell($document_quantity, $pages, $pricing = []) {
    $stitch_run_rate = $pricing['stitch_run_rate'];
    $stitch_sell = round($stitch_run_rate * $document_quantity, 2);
    return $stitch_sell;
  }

  static function sellItem_stitchSellRunRate(int $pages, string $document_finish, array $curve_map): string {
    if ($document_finish == 'Saddlestitch' && $pages < $curve_map['Saddlestitch_Pages_Level1']) {
      $sell_item_description = 'Saddlestitch_Rate_Level1';
    } 
    else if ($document_finish == 'Saddlestitch' && $pages < $curve_map['Saddlestitch_Pages_Level2']) {
      $sell_item_description = 'Saddlestitch_Rate_Level2';
    }
    else if ($document_finish == 'Saddlestitch' && $pages >= $curve_map['Saddlestitch_Pages_Level2']) {
      $sell_item_description = 'Saddlestitch_Rate_Level3';
    } 
    else {
      $sell_item_description = 'cornerstitch_inline';
    }
    return $sell_item_description;
  }

  static function pbCutCoverHours()
  {
    $pb_cover_cut_setup = .5;
    $pb_cover_cut_run = .75;
    $pb_cut_cover_hours = $pb_cover_cut_setup + $pb_cover_cut_run;
    return $pb_cut_cover_hours;
  }

  static function pbCutBlockHours($document_quantity)
  {
      return 0;
      /*
      $cut_block_setup=.5;
      $cut_block_speed=150;
      $cut_block_hours=round($document_quantity/$cut_block_speed +$cut_block_setup,2);
      return $cut_block_hours;*/
  }

  static function pbSeparateBlockHours($document_quantity)
  {
      return 0;
      /*
      $pb_separate_block_speed=200;
      $pb_separate_block_hours = round($document_quantity/$pb_separate_block_speed,2);
      return $pb_separate_block_hours;*/
  }

  static function pbRunHours($document_quantity, $pages, $pb_cut_cover_hours)
  {
    $pb_setup_hours=1;

    $pb_run_speed500=125;
    $pb_run_speed850=100;
    $pb_run_speed1200=50;

    $pb_minimum_run_hours = .78;

    if ($document_quantity < 200) {
      $pb_run_hours= round($pb_minimum_run_hours+$pb_setup_hours,2);
    } elseif ($pages <= 500) {
      $pb_run_hours = round(($document_quantity/$pb_run_speed500)+$pb_setup_hours,2);
    } elseif ($pages < 850) {
      $pb_run_hours = round(($document_quantity/$pb_run_speed850)+$pb_setup_hours,2);
    } else {
      $pb_run_hours = round(($document_quantity/$pb_run_speed1200)+$pb_setup_hours,2);
    }

    $pb_run_hours += $pb_cut_cover_hours;

    return $pb_run_hours;
  }

  static function gluecost($pages, $document_quantity) {
    $glue_cost_per50 = .02258;
    $glue_cost = round(($pages * $document_quantity) / 50 * $glue_cost_per50, 2);

    return $glue_cost;
  }

  static function perfectBindCost($pb_run_hours, $pb_cut_cover_hours, $pages, $document_quantity)
  {
    $pb_setup_hours=1;
    $pb_cut_op=25;//hourly rate for cutter operator
    /*$cut_op=25;*/
    $pb_op= 25;//hourly rate for binder operator
    $ghw_op1=20;//New variable/rate
    $ghw_op2=15;//New variable/rate
    $carton = .3740;//carton cost

    $perfect_bind_cost_1 = ($pb_run_hours - $pb_cut_cover_hours - $pb_setup_hours) * ($ghw_op1 + $ghw_op2) + $pb_op * $pb_setup_hours; //1 = binder set up New

    // Dave requested a 9.8% markup on binding May 26 2025
    $perfect_bind_cost_1 += ($perfect_bind_cost_1 * .098);

    $perfect_bind_cost_2 = $perfect_bind_cost_1 + ($pb_cut_cover_hours * $pb_cut_op);//add cover cutting cost

    /*$perfect_bind_cost=$perfect_bind_cost+$pb_cut_block_hours*$cut_op+($pb_cut_block_hours-.5)*$ghw_op;//add block cutting cost*/

    /*$perfect_bind_cost=$perfect_bind_cost+$pb_separate_block_hours*$ghw_op;//add separating block cost*/

    $perfect_bind_cost_3 = $perfect_bind_cost_2 + round(($document_quantity * $pages / 5000 * $carton), 2);//add carton cost

    $perfect_bind_cost = $perfect_bind_cost_3;

    return $perfect_bind_cost;
  }

  static function perfectBindSell($perfect_bind_cost, $document_quantity, $pages, $pricing = [])
  {
    $markup200=2.4931;
    $markup300=2.3605;
    $markup300plus=1.8884;
    $glue_cost = PrintBind::gluecost($pages, $document_quantity);

    if ($document_quantity < 201) {
      $perfect_bind_sell = round($perfect_bind_cost * $markup200, 2);
    } elseif ($document_quantity < 301) {
      $perfect_bind_sell = round($perfect_bind_cost * $markup300, 2);
    } else {
      $perfect_bind_sell = round($perfect_bind_cost * $markup300plus, 2);
    }

    $taylor_markup_rate = $pricing['pb_taylor_mu'];
    $perfect_bind_sell += $glue_cost;
    $perfect_bind_sell = round($perfect_bind_sell * $taylor_markup_rate, 2);

    return $perfect_bind_sell;
  }
}
