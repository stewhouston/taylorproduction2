<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingRateSet extends Model
{
    protected $primaryKey = 'id';
	public $incrementing = false;
    
    protected $fillable = [
        'id', 'company_id', 'is_active', 'name', 'note', 'created_by', 'activated_at'
    ];

    public function company() {
		return $this->belongsTo(Company::class);
	}

	public function sellRates() {
        return $this->hasMany(SellRate::class, 'id', 'rate_set_id');
    }

	public static function companyActive(int $company_id, string $field = "") {
		$eloquent = PricingRateSet::where('company_id', $company_id)->where('is_active', 1);
		if (empty($field)) return $eloquent;
		$rate_set = $eloquent->first();
		return $rate_set->{$field};
	}

	public static function active() {
		return PricingRateSet::where('is_active', 1);
	}

	public static function getEstimateRateSet(Estimate $estimate): PricingRateSet  {
		$estimate_rate_set = PricingRateSet::where('id', $estimate->active_rate_set_id)->first();
		if (empty($estimate_rate_set)) {
			/**
			*	@NOTE Commented by request 2021-06-14
			*/
			// $estimate->addAlert("Estimate rates automatically updated to present. Please contact Taylor-Demers. (technical reference: RSID_{$estimate->active_rate_set_id})", ['disables_estimate_interact' => 1]);
			return PricingRateSet::companyActive($estimate->company_id)->first();
		}
		return $estimate_rate_set;
	}

	public static function isActiveRateSet(/*PricingRateSet*/ $rate_set, int $company_id): bool {
		if (empty($rate_set)) return false;
		return $rate_set->id === PricingRateSet::companyActive($company_id, 'id');
	}
}
