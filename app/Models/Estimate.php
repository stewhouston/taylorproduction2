<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function documents() {
        return $this->hasMany(Document::class);
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
}
