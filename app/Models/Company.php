<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

#[Table('companies')]
class Company extends Model
{
    protected $fillable = [
        'name', 'is_active',
    ];

    public function users() {
    	return $this->hasMany(User::class);
    }

    public function estimates() {
        return $this->hasMany(Estimate::class);
    }

    public static function getScopePermissible(User $user, array $params = []): \Illuminate\Database\Eloquent\Collection {
      $params = array_merge([
        'filter' => []
      ], $params);
      if ($user->isAdmin()) {
        $companies = Company::all();
      } else {
        $companies = Company::where('id', $user->company_id)->get();
      }
      if (count($params['filter'])) {
        $companies = $companies->filter(function(Company $company) use ($params) {
          return !(in_array($company->id, $params['filter']) ?: in_array($company->name, $params['filter']));
        });
      }
      return $companies;
    }
}
