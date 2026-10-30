<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Head\Facades\Head;
use App\Services\ShippingService;
use App\Services\EstimateService;
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

  // Handles both creating and updating drop instances; invoked through ajax
  public function processDropInstance(Request $request) {
    $drop_data = $request->input('dropData');
    $estimate_id = intval($request->input('estimateId'));
    $drop_instance_id = intval($request->input('dropInstanceId')) ?? 0;
    $shipto_id = intval($request->input('shiptoId'));

    if (empty($drop_data)) {
      return response()->json([
        'error' => '400/dropData parameter is required.'
      ], 400);
    }

    $estimate = Estimate::find($estimate_id);
    $drop_instance = $this->shippingService->processDropInstanceProcedure(
      $estimate_id, $shipto_id, $drop_instance_id, $drop_data, $estimate->company_id, $request->user()->id, [
        'updated_directly' => true
      ]
    );

    return response()->json($drop_instance->toArray(), 200);
  }

  public function setCustomDropAddress(Request $request) 
  {
    $drop_instance_id = intval($request->input('dropInstanceId')) ?? 0;
    $estimate_id = intval($request->input('estimateId'));
    $shipto_id = intval($request->input('shiptoId'));

    $address = $request->input('address');

    // Update drop instance
    $drop_instance = DropInstance::find($drop_instance_id);
    $drop_instance->custom_ship_address = $address;

    $drop_instance->save();

    return response()->json($drop_instance->toArray(), 200);
  }

  public function deleteDropInstance(Request $request) 
  {
    $estimate_id = $request->input('estimateId');
    $drop_instance_id = $request->input('dropInstanceId');
    $shipto_id = $request->input('shiptoId');
    $drop_instance = DropInstance::find($drop_instance_id);
    if (empty($drop_instance)) {
      return response()->json([], 204);
    }
    if ($drop_instance->estimate_id == $estimate_id) {
      // When a drop with a custom address is deleted, the 'Custom Zone Delivery' alert needs to be re-prompted for the current estimate.
      if (!empty($drop_instance->custom_ship_address)) {
        $estimate = Estimate::find($estimate_id);
        ShippingService::addCustomShippingPendingAlert($estimate);
      }
      $drop_instance->delete();
      EstimateService::updateFilesReqDate($estimate_id);
      return 'ok';
    } else {
      return response()->json(['error' => '409/$estimate_id does not for drop instance (' . $drop_instance_id .  ')'], 409);
    }
  }

  public function setDropInstanceDeliveryDate(Request $request) 
  {
    $estimate_id = $request->input('estimateId');
    $drop_instance_id = $request->input('dropInstanceId');
    $shipto_id = $request->input('shiptoId');
    if (empty($drop_instance_id)) {
      return '401/No drop instance provided. @todo handle this';
    }
    // deliveryDate comes in as a valid PHP unix timestamp (without milliseconds)
    $delivery_date = date("Y-m-d H:i:s", $request->input('deliveryDate'));
    $drop_instance = $this->shippingService->updateDropInstance(
        $drop_instance_id, ['delivery_date' => $delivery_date]
    );
    return $drop_instance;
  }

  public function updateEstimateShipState(Request $request, int $estimate_id) 
  {
    $estimate = Estimate::find($estimate_id);

    if ($request->input('usesCustomAddresses')) {
      $estimate->uses_custom_addresses = 1;
      $message = 'OK: `uses_custom_addresses` set to 1. No alert issued.';
      if ($request->input('customAddressWasChanged')) {
        ShippingService::addCustomShippingPendingAlert($estimate);
        $message = 'OK: New custom address alert prompted for user.' . gettype($request->input('customAddressWasChanged'));
      }
    } else {
      $estimate->uses_custom_addresses = 0;
      $message = 'OK: `uses_custom_addresses` set to 0.';
    }

    $estimate->save();
    return response()->json($message, 200);
  }
}
