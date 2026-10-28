<?php

namespace App\Objects;

class ProductionHours {

    static function hourRange($print_hours, $cut_hours, $fold_hours, $saddlestitch_hours, $perfect_bind_hours){
        $net_hours = $print_hours + $fold_hours + $cut_hours *.5 + $saddlestitch_hours*.5 + $perfect_bind_hours *.8;

        $low_end = round(max(6,$net_hours*1.75),0);
        $high_end = round(max($low_end+4,$low_end*1.5),0);
        $hour_range = $low_end.' to '.$high_end;

        return $hour_range;
    }

}
