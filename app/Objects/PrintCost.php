<?php

namespace App\Objects;

use App\Models\Stock;


class PrintCost {
    //Print cost related Functions (except Stock)

    static function clickCost($black_clicks,$colour_clicks,$device) : float {
            
        $click_144_black=.0057;//move to cost table?
        $click_495_black=.0028;//move to cost table?
        $click_800_colour=.065;//move to cost table?
        $click_800_black=.0125;//move to cost table?
        
        if ($device=='144'){
            $click_cost = round($black_clicks *$click_144_black,2);
        }
        elseif($device=='495'){
            $click_cost = round($black_clicks *$click_495_black,2);
        }
        else{
            $click_cost = round($black_clicks *$click_800_black,2);
            $click_cost = $click_cost + round($colour_clicks *$click_800_colour,2);
        }
        return $click_cost;
    }

    static function labourCost($print_hours):float {
        $print_op=22;//move to cost table?
        
        $labour_cost = round($print_hours * $print_op);
        return $labour_cost;
    }
}