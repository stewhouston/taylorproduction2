<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionComponent extends Model
{
    protected $fillable = [
        'run_type', 'device', 'run_sheet_sqin', 'run_sheets', 'stock_cost', 'impressions', 'black_clicks', 'colour_clicks', 
        'click_cost', 'component_weight', 'component_caliper', 'component_sell', 'print_run_speed', 'labour_cost', 'print_hours', 
        'print_cost', 'cut_required', 'offline_bind_required', 'sell_item_description', 'component_id',
    ];

    public function component() {
        return $this->belongsTo(Component::class, 'component_id');
    }
}
