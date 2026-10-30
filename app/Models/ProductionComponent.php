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

  /**
  *  Stew: Takes an unnormalized array of data and intersects it with all columns defined in the $fillables property. This is important as there are inconsistencies in variable/column names, e.g. 'text_cut_required' and 'cut_required' are incompatibility and will pitch errors until unified.
  */
  public function intersectFillables(array $data): array {
    $row = [];
    foreach($this->fillable as $column_name) {
      if (empty($data[$column_name])) {
        continue;
      }
      $row[$column_name] = $data[$column_name];
    }
    return $row;
  }
}
