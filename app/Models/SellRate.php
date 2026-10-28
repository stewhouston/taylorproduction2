<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class SellRate extends Model
{
    protected $fillable = [
        'rate_set_id', 'rate_schedule', 'sell_item_type', 'sell_item_description', 'sell_item_rate'
    ];

  public function rateSet() {
    return $this->hasOne(PricingRateSet::class, 'rate_set_id', 'id');
  }

	public static function getItemRate(string $sell_item_description, int $active_rate_set_id, $default_value = null): float {
		$q = SellRate::where('rate_set_id', $active_rate_set_id)->where('sell_item_description', $sell_item_description);
		$sell_rate_row = $q->first();
		if (empty($sell_rate_row)) {
			if ($default_value !== null) {
				return $default_value;
			}
			throw new \Exception("SellRate::getItemRate Could not determine value for \$sell_item_description: '$sell_item_description' (technical reference: RSID_$active_rate_set_id)");
		}
		return $sell_rate_row->sell_item_rate;
	}

	public static function getTypeRatesCollection(string $sell_item_type, int $rate_set_id): Collection {
		$q = SellRate::where('rate_set_id', $rate_set_id)->where('sell_item_type', $sell_item_type);
		return $q->get()->groupBy('sell_item_description');
	}

	public static function getTypeRate(Collection $type_rates_collection, string $sell_item_description): float {
		return $type_rates_collection->get($sell_item_description)->first()->sell_item_rate;
	}
}
