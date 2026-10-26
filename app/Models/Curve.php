<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('curve')]
class Curve extends Model
{
  protected $fillable = [
    'name', 'value', 'company_id', 'last_modified_by'
  ];

  public function company() {
    return $this->belongsTo(Company::class);
  }

  public static function getFieldValue(string $name, int $company_id): ?float {
    $curve_entry = Curve::where('company_id', $company_id)->where('name', $name)->first();
    if (empty($curve_entry)) return null;
    return (float) $curve_entry->value;
  }

  public static function getFields(array $names, int $company_id, array $params = []): Collection {
    $params = array_merge([
      'map' => true
    ], $params);

    $curve_entries = Curve::where('company_id', $company_id)->whereIn('name', $names)->get();

    if (empty($params['map'])) return $curve_entries;
    else {
      return $curve_entries->keyBy('name')->map(function(Curve $entry) {
        return (float) $entry->value;
      });
    }
  }

  public static function getNamedMap(int $company_id, array $params = []): Collection {
    $params = array_merge([
      'extended' => (bool) 0,
      'like' => '',
    ], $params);
    $curve_query = Curve::where('company_id', $company_id);
    if (!empty($params['like'])) {
      $curve_query = Curve::where('name', 'like', "%{$params['like']}%");
    }
    $curve_entries = $curve_query->get();
    return $curve_entries->keyBy('name')->map(function(Curve $entry, string $name) use ($params) {
      return ($params['extended']) ? $entry : (float) $entry->value;
    });
  }
}
