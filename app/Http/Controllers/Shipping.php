<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Head\Facades\Head;
use App\Services\ShippingService;
use App\Models\Estimate;
use App\Models\Document;
use App\Models\DropInstance;

class Shipping extends Controller
{
    public function __construct(
        protected ShippingService $shippingService
    )
    {

    }
    
    public function drops(Request $request, $estimate_id) 
    {
        $estimate = Estimate::where('id', $estimate_id)->first();
        $documents = Document::where('estimate_id', $estimate_id)->orderBy('created_at', 'ASC')->get();
        $request_user = $request->user();
        $is_estimate_admin = (bool) ($request_user->isAdmin() && $request_user->hasPermission('estimate_admin'));
        Head::title("Shipping {$estimate->client_reference_number} #{$estimate->id} - Taylor-Demers Estimating");

        if ($estimate->company_id === $request_user->company->id || $is_estimate_admin) {
            $drop_instances = DropInstance::where('estimate_id', $estimate_id)->orderBy('created_at', 'asc')->get();
        } else {
            $drop_instances = [];
            $init_notification = [
            'message' => "Shipping instances for Estimate #{$estimate_id} not available for {$request_user->email}/{$request_user->company->name}. If you believe this to be an error, please contact Taylor-Demers.",
            'blockInteraction' => 1
        ];
        }

        if (empty($estimate)) {
            exit("No estimate could be found for ID '{$estimate_id}'. <a href='javascript:history.go(-1)'>Back</a>");
        }

        return view('estimates.shipping.drops', [
            'estimate' => $estimate,
            'documents' => $documents,
            'shiptos' => $this->shippingService->getShiptos($estimate->company_id, ['is_active' => 1]),
            'zones' => $this->shippingService->getZones(),
            'drop_instances' => $drop_instances,
            'stat_holidays_db' => ShippingService::getStatHolidaysDb(),
            'init_notification' => ($init_notification ?? [])
        ]);
    }
}
