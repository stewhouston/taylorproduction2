<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('estimates')]
class Estimate extends Model
{
    protected $fillable = [
        'client_reference_number', 'client_reference_description', 'client_reference_mail_date',
        'created_by', 'company_id', 'has_rush_fee', 'has_alert', 'alerts', 'files_required_by',
        'status', 'is_print_order', 'marked_print_order_at', 'marked_print_order_by',
        'uses_custom_addresses', 'active_rate_set_id', 'last_modified_by',
        'print_sell_adjust_reason', 'print_sell_adjustment', 'drop_sell_adjust_reason', 'drop_sell_adjustment',
        'estimate_comment', 'file_require_adjust_reason', 'files_require_adjustment', 'cusip'
    ];

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function documents() {
        return $this->hasMany(Document::class);
    }

    public function components() {
        return $this->hasManyThrough(Component::class, Document::class);
    }

    public function createdBy() {
      return $this->belongsTo(User::class, 'created_by');
    }

    public function dropInstances() {
      return $this->hasMany(DropInstance::class);
    }

    public function getLastModifiedBy(): string {
        if (!empty($this->last_modified_by)) {
            $estimate_last_updated_by_user = User::find($this->last_modified_by);
            $estimate_last_updated_by = (!empty($estimate_last_updated_by_user))
                ? $estimate_last_updated_by_user->name : "Unknown";
        } else {
            $estimate_last_updated_by = '—';
        }

        $estimate_last_updated_at = $this->updated_at;

      if (empty($this->documents) || !is_object($this->documents) || $this->documents->count() === 0) {
        return $estimate_last_updated_by;
      }

        $last_modified_document = Document::where('estimate_id', $this->id)->orderBy('updated_at', 'DESC')->first();

        if ($estimate_last_updated_at >= $last_modified_document->updated_at && $estimate_last_updated_by != '—') {
            return $estimate_last_updated_by;
        } else { // Otherwise, whoever edited a document last
            $last_modified_user_id = $last_modified_document->last_modified_by;
        }

      if (empty($last_modified_user_id)) {
        return $estimate_last_updated_by;
      }

      $last_modified_by_user = User::find($last_modified_user_id);

      if (empty($last_modified_by_user) || !is_object($last_modified_by_user)) {
          return $estimate_last_updated_by;
      }

      return "{$last_modified_by_user->name}";
    }

    public function getLastModifiedAt() {
		$estimate_last_updated_at = $this->updated_at;

        if (empty($this->documents) || !is_object($this->documents) || $this->documents->count() === 0) {
            return $estimate_last_updated_at;
        }

      $recent_document_modified_at = $this->documents->sortByDesc('updated_at')->first()->updated_at;

        // Adjustment date gets mirrored onto estimate; in this case use time of last document edit
        if ($estimate_last_updated_at->equalTo(new Carbon($this->adjustment_date))) {
            return $recent_document_modified_at;
        }

        return ($estimate_last_updated_at > $recent_document_modified_at) ? $estimate_last_updated_at : $recent_document_modified_at;
    }

    public function getAlerts(): array {
        if (empty($this->alerts)) {
            return [];
        }

        return json_decode($this->alerts, 1);
    }

    public function hasAlerts(): bool {
        return (bool) !empty($this->alerts);
    }

    public function hasAlert(string $alert_msg): bool {
        if (empty($this->alerts)) return false;
        return (bool) strpos($this->alerts, $alert_msg) !== false;
    }

    public function customDocuments() 
    {
        return array_filter($this->documents->toArray(), function($document) {
            return $document['is_custom'];
        });
    }

    public function getAllCosts(): array {
      $curve_fields = Curve::getFields(['Delivery_Rush_Fee_Dollars', 'Tax_HST_Percent'], $this->company_id)->toArray();
      $shipping_cost = $this->getShippingCost();

      return [
        'has_rush_fee' => $this->has_rush_fee,
        'shipping_cost' => $shipping_cost,
        'sub_total' => $this->getSubTotal([], $curve_fields),
        'taxes' => $this->getTaxes(['shipping_cost' => $shipping_cost], $curve_fields),
        'total' => $this->getTotal(['shipping_cost' => $shipping_cost], $curve_fields),
        'sub_total_with_rush_fee' => $this->getSubTotal([
          'with_rush_fee' => true,
        ], $curve_fields),
        'print_sub_total' => $this->getSubTotal([
          'without_print_sell_adjustment' => true,
        ], $curve_fields),
        'ship_sub_total' => $shipping_cost - ($this->drop_sell_adjustment ?? 0),
        'sub_total_with_ship' => $this->getSubTotalWithShip($curve_fields)
      ];
    }

    private function getTotal(array $opts, array $curve_fields): float {
      $total = $this->getSubtotal([], $curve_fields);
      if ($this->has_rush_fee) {
        $total += $curve_fields['Delivery_Rush_Fee_Dollars'];
      }
      $total += $opts['shipping_cost'] ?? 0;
      $total += $this->getTaxes($opts, $curve_fields);
      return $total;
    }

    private function getSubtotal(array $opts, array $curve_fields): float {
      $subtotal = (float) $this->documents->reduce(function($carry, $document) {
            return $carry + ($document->production->sell ?? 0);
      }, 0.0);
      if (empty($opts['without_print_sell_adjustment'])) {
        $subtotal += $this->print_sell_adjustment ?? 0;
      }
      if (!empty($opts['with_rush_fee']) && $this->has_rush_fee) {
        $subtotal += $curve_fields['Delivery_Rush_Fee_Dollars'];
      }
      return $subtotal;
    }

    private function getSubTotalWithShip(array $curve_fields): float {
      $sub_total_without_ship = $this->getSubTotal([], $curve_fields);
      $shipping_cost = $this->getShippingCost();
      $rush_fee = ($this->has_rush_fee) ? $curve_fields['Delivery_Rush_Fee_Dollars'] : 0;
      return $sub_total_without_ship + $shipping_cost + $rush_fee;
    }

    private function getShippingCost(): float {
        $shipping_cost = $this->dropInstances->reduce(function(float $carry, DropInstance $drop_instance): float {
            $instance_ship_cost = floatval($drop_instance->drop_cost);

            if ($instance_ship_cost === 0.0) {
                return $carry;
            }

            if (is_numeric($drop_instance->drop_handling_cost)) {
                $handling_cost = $drop_instance->drop_handling_cost;
            } else {
                $handling_zone_cost_modifier = (float) $drop_instance->shipto->zoneRow->handling;
                $handling_cost = $instance_ship_cost * $handling_zone_cost_modifier;
            }

            return $carry + $instance_ship_cost + $handling_cost;
        }, 0.0);

      // adjust override
      $shipping_cost += $this->drop_sell_adjustment ?? 0;

      return (float) $shipping_cost;
    }

    private function getTaxes(array $opts, array $curve_fields): float {
      $total = $this->getSubtotal([], $curve_fields);
      if ($this->has_rush_fee) {
        $total += $curve_fields['Delivery_Rush_Fee_Dollars'];
      }
      $total += $opts['shipping_cost'] ?? 0;

			$tax = $total * ($curve_fields['Tax_HST_Percent'] / 100);
      return $tax;
    }
}
